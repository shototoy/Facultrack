<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "facultrack_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'class') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get class info
$class_query = "SELECT class_id FROM classes WHERE user_id = ? AND is_active = TRUE";
$stmt = $pdo->prepare($class_query);
$stmt->execute([$user_id]);
$class_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class_info) {
    echo json_encode(['success' => false, 'message' => 'Class not found']);
    exit();
}

$class_id = $class_info['class_id'];

try {
    $faculty_query = "
        SELECT DISTINCT
            f.faculty_id,
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
        JOIN schedules s ON f.faculty_id = s.faculty_id
        WHERE f.is_active = TRUE 
        AND s.is_active = TRUE 
        AND s.class_id = ?
    ";
    
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$class_id]);
    $faculty_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'faculty' => $faculty_data
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching data']);
}
?>