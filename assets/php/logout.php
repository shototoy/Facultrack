<?php
session_start();

$was_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$user_role = $_SESSION['role'] ?? null;
$user_name = $_SESSION['full_name'] ?? null;

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