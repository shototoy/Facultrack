<?php
require_once 'common_utilities.php';

initializeSession();
header('Content-Type: application/json');

$pdo = initializeDatabase();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['program_chair', 'campus_director'])) {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
}

try {
    $stats = [];
    $user_role = $_SESSION['role'];
    
    if ($user_role === 'campus_director') {
        $faculty_query = "SELECT COUNT(*) as total_faculty FROM faculty WHERE is_active = TRUE";
        $stmt = $pdo->prepare($faculty_query);
        $stmt->execute();
        $stats['total_faculty'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_faculty'];
        
        $classes_query = "SELECT COUNT(*) as total_classes FROM classes WHERE is_active = TRUE";
        $stmt = $pdo->prepare($classes_query);
        $stmt->execute();
        $stats['total_classes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_classes'];
        
        $courses_query = "SELECT COUNT(*) as total_courses FROM courses WHERE is_active = TRUE";
        $stmt = $pdo->prepare($courses_query);
        $stmt->execute();
        $stats['total_courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_courses'];
        
        $announcements_query = "SELECT COUNT(*) as active_announcements FROM announcements WHERE is_active = TRUE";
        $stmt = $pdo->prepare($announcements_query);
        $stmt->execute();
        $stats['active_announcements'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_announcements'];
        
        $available_faculty_query = "
            SELECT COUNT(*) as available_faculty 
            FROM faculty 
            WHERE is_active = 1
            AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ";
        $stmt = $pdo->prepare($available_faculty_query);
        $stmt->execute();
        $stats['available_faculty'] = $stmt->fetch(PDO::FETCH_ASSOC)['available_faculty'];
        
    } elseif ($user_role === 'program_chair') {
        $user_id = $_SESSION['user_id'];
        
        $program_info_query = "SELECT program FROM program_chairs WHERE user_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($program_info_query);
        $stmt->execute([$user_id]);
        $program_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($program_info) {
            $program = $program_info['program'];
            
            $program_faculty_query = "
                SELECT COUNT(*) as program_faculty 
                FROM faculty 
                WHERE program = ? AND is_active = TRUE
            ";
            $stmt = $pdo->prepare($program_faculty_query);
            $stmt->execute([$program]);
            $stats['total_faculty'] = $stmt->fetch(PDO::FETCH_ASSOC)['program_faculty'];
            
            $program_classes_query = "
                SELECT COUNT(*) as program_classes 
                FROM classes c
                JOIN program_chairs pc ON c.program_chair_id = pc.program_chair_id
                WHERE pc.program = ? AND c.is_active = TRUE
            ";
            $stmt = $pdo->prepare($program_classes_query);
            $stmt->execute([$program]);
            $stats['total_classes'] = $stmt->fetch(PDO::FETCH_ASSOC)['program_classes'];
            
            $scheduled_courses_query = "
                SELECT COUNT(DISTINCT s.course_code) as scheduled_courses
                FROM schedules s
                JOIN classes c ON s.class_id = c.class_id
                JOIN program_chairs pc ON c.program_chair_id = pc.program_chair_id
                WHERE pc.program = ? AND s.is_active = TRUE
            ";
            $stmt = $pdo->prepare($scheduled_courses_query);
            $stmt->execute([$program]);
            $stats['total_courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['scheduled_courses'];
            
            $program_announcements_query = "
                SELECT COUNT(*) as program_announcements
                FROM announcements 
                WHERE (target_audience = 'all' OR target_audience = 'program_chair') 
                AND is_active = TRUE
            ";
            $stmt = $pdo->prepare($program_announcements_query);
            $stmt->execute();
            $stats['active_announcements'] = $stmt->fetch(PDO::FETCH_ASSOC)['program_announcements'];
        }
    }
    
    sendJsonResponse(['success' => true, 'data' => $stats]);
    
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Error fetching statistics: ' . $e->getMessage()], 500);
}
?>