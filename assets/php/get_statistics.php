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
        $faculty_query = "
            SELECT COUNT(*) as total_faculty 
            FROM faculty f 
            INNER JOIN users u ON f.user_id = u.user_id 
            WHERE u.is_active = TRUE
        ";
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
            FROM faculty f
            INNER JOIN users u ON f.user_id = u.user_id 
            WHERE u.is_active = TRUE AND f.is_active = 1
        ";
        $stmt = $pdo->prepare($available_faculty_query);
        $stmt->execute();
        $stats['available_faculty'] = $stmt->fetch(PDO::FETCH_ASSOC)['available_faculty'];
        
    } elseif ($user_role === 'program_chair') {
        $user_id = $_SESSION['user_id'];
        
        $program_info_query = "SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($program_info_query);
        $stmt->execute([$user_id]);
        $program_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($program_info) {
            $program = $program_info['program'];
            
            $program_faculty_query = "
                SELECT COUNT(*) as program_faculty 
                FROM faculty f
                INNER JOIN users u ON f.user_id = u.user_id 
                WHERE f.program = ? AND u.is_active = TRUE
            ";
            $stmt = $pdo->prepare($program_faculty_query);
            $stmt->execute([$program]);
            $stats['total_faculty'] = $stmt->fetch(PDO::FETCH_ASSOC)['program_faculty'];
            
            $program_classes_query = "
                SELECT COUNT(*) as program_classes 
                FROM classes c
                WHERE c.program_chair_id = ? AND c.is_active = TRUE
            ";
            $stmt = $pdo->prepare($program_classes_query);
            $stmt->execute([$user_id]);
            $stats['total_classes'] = $stmt->fetch(PDO::FETCH_ASSOC)['program_classes'];
            
            $scheduled_courses_query = "
                SELECT COUNT(DISTINCT s.course_code) as scheduled_courses
                FROM schedules s
                JOIN classes c ON s.class_id = c.class_id
                WHERE c.program_chair_id = ? AND s.is_active = TRUE
            ";
            $stmt = $pdo->prepare($scheduled_courses_query);
            $stmt->execute([$user_id]);
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
            
            // Add available faculty count for program chair
            $available_faculty_query = "
                SELECT COUNT(*) as available_faculty 
                FROM faculty f
                INNER JOIN users u ON f.user_id = u.user_id 
                WHERE f.program = ? AND u.is_active = TRUE AND f.is_active = 1
            ";
            $stmt = $pdo->prepare($available_faculty_query);
            $stmt->execute([$program]);
            $stats['available_faculty'] = $stmt->fetch(PDO::FETCH_ASSOC)['available_faculty'];
            
            // Add total subjects (courses) count for program chair
            $subjects_query = "
                SELECT COUNT(DISTINCT curr.course_code) as total_subjects
                FROM curriculum curr
                JOIN classes c ON curr.year_level = c.year_level 
                              AND curr.semester = c.semester 
                              AND curr.academic_year = c.academic_year
                WHERE c.program_chair_id = ? AND curr.is_active = TRUE AND c.is_active = TRUE
            ";
            $stmt = $pdo->prepare($subjects_query);
            $stmt->execute([$user_id]);
            $stats['total_subjects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_subjects'];
        }
    }
    
    sendJsonResponse(['success' => true, 'data' => $stats]);
    
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Error fetching statistics: ' . $e->getMessage()], 500);
}
?>