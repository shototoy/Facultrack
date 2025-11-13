<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
header('Content-Type: application/json');

try {
    $pdo = initializeDatabase();
    validateUserSession(['program_chair', 'campus_director']);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $course_id = $_POST['course_id'] ?? null;
    
    if (!$course_id) {
        throw new Exception('Course ID is required');
    }
    
    error_log("DIRECT DELETE: Attempting to delete course_id: " . $course_id);
    
    $pdo->beginTransaction();
    
    // Get course code first
    $stmt = $pdo->prepare("SELECT course_code FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $course_code = $stmt->fetchColumn();
    
    if (!$course_code) {
        throw new Exception('Course not found');
    }
    
    // Delete related schedules
    $stmt = $pdo->prepare("DELETE FROM schedules WHERE course_code = ?");
    $stmt->execute([$course_code]);
    
    // Delete related curriculum
    $stmt = $pdo->prepare("DELETE FROM curriculum WHERE course_code = ?");
    $stmt->execute([$course_code]);
    
    // Delete course
    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Course deleted successfully',
        'course_id' => $course_id
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    error_log("DIRECT DELETE ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>