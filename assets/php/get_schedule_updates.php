<?php
require_once 'common_utilities.php';
initializeSession();
$pdo = initializeDatabase();

// Allow both authenticated users and handle different user types
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Not authenticated']);
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

function getCurrentScheduleData($pdo, $user_id, $user_role) {
    $current_time = date('H:i:s');
    $current_day = date('w');
    $day_mapping = [0 => 'S', 1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'SAT'];
    $today_code = $day_mapping[$current_day];
    
    $schedules = [];
    
    if ($user_role === 'faculty') {
        // Get faculty's current schedule
        $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($faculty_query);
        $stmt->execute([$user_id]);
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($faculty) {
            $schedule_query = "
                SELECT s.*, c.course_description, cl.class_name, cl.class_code,
                    CASE 
                        WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end THEN 'ongoing'
                        WHEN TIME(NOW()) < s.time_start THEN 'upcoming'
                        ELSE 'finished'
                    END as status
                FROM schedules s
                JOIN courses c ON s.course_code = c.course_code
                JOIN classes cl ON s.class_id = cl.class_id
                WHERE s.faculty_id = ? AND s.is_active = TRUE 
                AND (s.days = ? OR 
                    (s.days = 'MW' AND ? IN ('M', 'W', 'MW')) OR
                    (s.days = 'MF' AND ? IN ('M', 'F', 'MF')) OR
                    (s.days = 'WF' AND ? IN ('W', 'F', 'WF')) OR
                    (s.days = 'MWF' AND ? IN ('M', 'W', 'F', 'MWF')) OR
                    (s.days = 'TTH' AND ? IN ('T', 'TH', 'TTH')) OR
                    (s.days = 'MTWTHF' AND ? IN ('M', 'T', 'W', 'TH', 'F', 'MTWTHF'))
                )
                ORDER BY s.time_start";
            
            $stmt = $pdo->prepare($schedule_query);
            $stmt->execute([
                $faculty['faculty_id'], 
                $today_code, $today_code, $today_code, $today_code, 
                $today_code, $today_code, $today_code, $today_code
            ]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else if ($user_role === 'class') {
        // Get class's current schedule
        $class_query = "
            SELECT s.*, c.course_description, u.full_name as faculty_name,
                CASE 
                    WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end THEN 'ongoing'
                    WHEN TIME(NOW()) < s.time_start THEN 'upcoming'
                    ELSE 'finished'
                END as status
            FROM schedules s
            JOIN courses c ON s.course_code = c.course_code
            JOIN faculty f ON s.faculty_id = f.faculty_id
            JOIN users u ON f.user_id = u.user_id
            JOIN classes cl ON s.class_id = cl.class_id
            WHERE cl.user_id = ? AND s.is_active = TRUE
            AND (s.days = ? OR 
                (s.days = 'MW' AND ? IN ('M', 'W', 'MW')) OR
                (s.days = 'MF' AND ? IN ('M', 'F', 'MF')) OR
                (s.days = 'WF' AND ? IN ('W', 'F', 'WF')) OR
                (s.days = 'MWF' AND ? IN ('M', 'W', 'F', 'MWF')) OR
                (s.days = 'TTH' AND ? IN ('T', 'TH', 'TTH')) OR
                (s.days = 'MTWTHF' AND ? IN ('M', 'T', 'W', 'TH', 'F', 'MTWTHF'))
            )
            ORDER BY s.time_start";
        
        $stmt = $pdo->prepare($schedule_query);
        $stmt->execute([
            $user_id,
            $today_code, $today_code, $today_code, $today_code, 
            $today_code, $today_code, $today_code, $today_code
        ]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $schedules;
}

try {
    $schedules = getCurrentScheduleData($pdo, $user_id, $user_role);
    
    sendJsonResponse([
        'success' => true,
        'schedules' => $schedules,
        'timestamp' => date('Y-m-d H:i:s'),
        'current_time' => date('H:i:s'),
        'user_role' => $user_role
    ]);
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>