<?php
require_once 'common_utilities.php';
initializeSession();
header('Content-Type: application/json');
$pdo = get_db_connection();
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
}
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
try {
    $update_user = "UPDATE users SET updated_at = NOW() WHERE user_id = ?";
    $stmt = $pdo->prepare($update_user);
    $stmt->execute([$user_id]);
    if ($role === 'faculty' || $role === 'program_chair') {
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
    } elseif ($role === 'campus_director') {
        $check_director_faculty = "
            INSERT INTO faculty (user_id, employee_id, program, current_location, is_active, last_location_update)
            SELECT ?, CONCAT('DIR-', user_id), 'Administration', 'Director Office', 1, NOW()
            FROM users
            WHERE user_id = ?
            ON DUPLICATE KEY UPDATE is_active = 1, last_location_update = NOW()
        ";
        $stmt = $pdo->prepare($check_director_faculty);
        $stmt->execute([$user_id, $user_id]);
        $offline_directors = "
            UPDATE faculty f
            JOIN users u ON f.user_id = u.user_id
            SET f.is_active = 0
            WHERE u.role = 'campus_director'
            AND f.last_location_update < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ";
        $stmt = $pdo->prepare($offline_directors);
        $stmt->execute();
    }
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

