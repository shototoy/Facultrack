<?php
require_once 'common_utilities.php';

initializeSession();
header('Content-Type: application/json');

$pdo = initializeDatabase();

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

try {
    if ($user_role === 'class') {
        $class_query = "SELECT class_id FROM classes WHERE user_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($class_query);
        $stmt->execute([$user_id]);
        $class_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class_info) {
            sendJsonResponse(['success' => false, 'message' => 'Class not found'], 404);
        }

        $class_id = $class_info['class_id'];
        
        $faculty_query = "
            SELECT DISTINCT
                f.faculty_id,
                u.full_name as faculty_name,
                f.current_location,
                CASE 
                    WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'available'
                    WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'busy'
                    ELSE 'offline'
                END as status,
                CASE 
                    WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, f.last_location_update, NOW()), ' minutes ago')
                    WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, f.last_location_update, NOW()), ' hours ago')
                    ELSE CONCAT(TIMESTAMPDIFF(DAY, f.last_location_update, NOW()), ' days ago')
                END as last_updated
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            JOIN schedules s ON f.faculty_id = s.faculty_id
            WHERE f.is_active = TRUE 
            AND s.is_active = TRUE 
            AND s.class_id = ?
        ";
        
        $stmt = $pdo->prepare($faculty_query);
        $stmt->execute([$class_id]);
        $faculty_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse([
            'success' => true,
            'faculty' => $faculty_data
        ]);
        
    } elseif ($user_role === 'faculty') {
        $faculty_query = "
            SELECT 
                f.current_location,
                f.last_location_update,
                CASE 
                    WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'available'
                    WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'busy'
                    ELSE 'offline'
                END as status,
                CASE 
                    WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, f.last_location_update, NOW()), ' minutes ago')
                    WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, f.last_location_update, NOW()), ' hours ago')
                    ELSE CONCAT(TIMESTAMPDIFF(DAY, f.last_location_update, NOW()), ' days ago')
                END as last_updated
            FROM faculty f
            WHERE f.user_id = ? AND f.is_active = TRUE
        ";
        
        $stmt = $pdo->prepare($faculty_query);
        $stmt->execute([$user_id]);
        $faculty_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($faculty_data) {
            sendJsonResponse([
                'success' => true,
                'current_location' => $faculty_data['current_location'],
                'status' => $faculty_data['status'],
                'last_updated' => $faculty_data['last_updated']
            ]);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Faculty not found'], 404);
        }
        
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized role'], 403);
    }
    
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Error fetching data'], 500);
}
?>