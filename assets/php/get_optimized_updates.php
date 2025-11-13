<?php
require_once 'common_utilities.php';

initializeSession();
header('Content-Type: application/json');

$pdo = initializeDatabase();

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
}

$tab = $_GET['tab'] ?? 'faculty';
$role = $_GET['role'] ?? $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$last_update = $_GET['last_update'] ?? '1970-01-01 00:00:00';

try {
    $updates = [];
    $total_count = 0;
    $has_changes = false;
    
    switch($tab) {
        case 'faculty':
            if ($role === 'campus_director') {
                $query = "
                    SELECT f.faculty_id as id, f.current_location as location, f.employee_id,
                    u.full_name, f.program, f.updated_at, f.last_location_update,
                    CASE 
                        WHEN f.is_active = 1 THEN 'available'
                        ELSE 'offline'
                    END as status
                    FROM faculty f 
                    INNER JOIN users u ON f.user_id = u.user_id
                    WHERE u.is_active = TRUE
                    ORDER BY f.last_location_update DESC
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($last_update === '1970-01-01 00:00:00') {
                    $has_changes = !empty($updates);
                } else {
                    // Only include records with actual visible data changes, not just is_active changes
                    $recent_updates = array_filter($updates, function($update) use ($last_update) {
                        // Only count as change if location was updated (visible data)
                        // is_active changes are for statistics only, not table display
                        return $update['last_location_update'] > $last_update;
                    });
                    $has_changes = !empty($recent_updates);
                    $updates = $recent_updates;
                }
                
                $count_query = "SELECT COUNT(*) as total FROM faculty f INNER JOIN users u ON f.user_id = u.user_id WHERE u.is_active = TRUE";
                $count_stmt = $pdo->prepare($count_query);
                $count_stmt->execute();
                $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            }
            break;
            
        case 'classes':
            if ($role === 'campus_director') {
                $query = "
                    SELECT c.class_id as id, c.class_name, c.year_level, c.class_code,
                    c.semester, c.academic_year, c.updated_at,
                    pc.full_name as program_chair_name,
                    COALESCE(subject_counts.total_subjects, 0) as total_subjects
                    FROM classes c
                    LEFT JOIN users pc ON c.program_chair_id = pc.user_id
                    LEFT JOIN (
                        SELECT class_id, COUNT(*) as total_subjects
                        FROM schedules 
                        WHERE is_active = TRUE 
                        GROUP BY class_id
                    ) subject_counts ON c.class_id = subject_counts.class_id
                    WHERE c.is_active = TRUE AND c.updated_at > ?
                    ORDER BY c.updated_at DESC
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([$last_update]);
                $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($updates)) {
                    $has_changes = true;
                }
                
                $count_query = "SELECT COUNT(*) as total FROM classes WHERE is_active = TRUE";
                $count_stmt = $pdo->prepare($count_query);
                $count_stmt->execute();
                $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            }
            break;
            
        case 'announcements':
            $target_audience = $role === 'campus_director' ? 'all' : $role;
            
            $query = "
                SELECT a.announcement_id as id, a.title, a.priority, a.target_audience,
                a.created_at, a.updated_at, u.full_name as created_by_name,
                LEFT(a.content, 100) as content_preview
                FROM announcements a
                JOIN users u ON a.created_by = u.user_id
                WHERE a.is_active = TRUE 
                AND (a.target_audience = 'all' OR a.target_audience = ?)
                AND a.updated_at > ?
                ORDER BY a.created_at DESC
                LIMIT 20
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$target_audience, $last_update]);
            $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($updates)) {
                $has_changes = true;
            }
            
            $count_query = "
                SELECT COUNT(*) as total 
                FROM announcements 
                WHERE is_active = TRUE 
                AND (target_audience = 'all' OR target_audience = ?)
            ";
            $count_stmt = $pdo->prepare($count_query);
            $count_stmt->execute([$target_audience]);
            $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            break;
            
        case 'courses':
            if ($role === 'campus_director') {
                $query = "
                    SELECT c.course_id as id, c.course_code, c.course_description, 
                    c.units, c.updated_at, schedule_counts.times_scheduled
                    FROM courses c
                    LEFT JOIN (
                        SELECT course_code, COUNT(*) as times_scheduled
                        FROM schedules 
                        WHERE is_active = TRUE 
                        GROUP BY course_code
                    ) schedule_counts ON c.course_code = schedule_counts.course_code
                    WHERE c.is_active = TRUE AND c.updated_at > ?
                    ORDER BY c.updated_at DESC
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([$last_update]);
                $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($updates)) {
                    $has_changes = true;
                }
                
                $count_query = "SELECT COUNT(*) as total FROM courses WHERE is_active = TRUE";
                $count_stmt = $pdo->prepare($count_query);
                $count_stmt->execute();
                $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            }
            break;
    }
    
    sendJsonResponse([
        'success' => true,
        'has_changes' => $has_changes,
        'updates' => $updates,
        'total_count' => $total_count,
        'tab' => $tab,
        'timestamp' => date('Y-m-d H:i:s'),
        'server_time' => time()
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Error fetching updates: ' . $e->getMessage()], 500);
}
?>