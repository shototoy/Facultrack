<?php
require_once 'common_utilities.php';

initializeSession();
header('Content-Type: application/json');

$pdo = initializeDatabase();

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // Update last activity for all users
    $update_user = "UPDATE users SET updated_at = NOW() WHERE user_id = ?";
    $stmt = $pdo->prepare($update_user);
    $stmt->execute([$user_id]);
    
    if ($role === 'faculty') {
        $update_faculty = "
            UPDATE faculty 
            SET is_active = 1, last_location_update = NOW()
            WHERE user_id = ?
        ";
        $stmt = $pdo->prepare($update_faculty);
        $stmt->execute([$user_id]);
        
        $offline_faculty = "
            UPDATE faculty 
            SET is_active = 0 
            WHERE last_location_update < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ";
        $stmt = $pdo->prepare($offline_faculty);
        $stmt->execute();
    }
    
    // Update class representative activity if class role
    if ($role === 'class') {
        $update_class = "UPDATE classes SET updated_at = NOW() WHERE user_id = ?";
        $stmt = $pdo->prepare($update_class);
        $stmt->execute([$user_id]);
    }
    
    sendJsonResponse([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'user_activity_updated' => true
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Heartbeat update failed'], 500);
}
?>