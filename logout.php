<?php
session_start();
$was_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$user_role = $_SESSION['role'] ?? null;
$user_name = $_SESSION['full_name'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id && ($user_role === 'faculty' || $user_role === 'program_chair' || $user_role === 'campus_director')) {
    require_once 'assets/php/common_utilities.php';
    try {
        $pdo = initializeDatabase();
        $set_offline_query = "UPDATE faculty SET is_active = 0 WHERE user_id = ?";
        $stmt = $pdo->prepare($set_offline_query);
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Failed to set user offline status: " . $e->getMessage());
    }
}
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
session_start();
$_SESSION['logout_message'] = 'You have been successfully logged out.';
$_SESSION['logout_user'] = $user_name;
header("Location: index.php");
exit();
?>