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
    
    sendJsonResponse(['success' => true, 'data' => $stats]);
    
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Error fetching statistics: ' . $e->getMessage()], 500);
}
?>