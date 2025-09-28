<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "facultrack_db";

function initializeDatabase() {
    global $servername, $username, $password, $dbname;
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function validateUserSession($allowed_roles, $redirect_url = "login.php") {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: $redirect_url");
        exit();
    }
    
    $user_role = $_SESSION['role'];
    if (is_array($allowed_roles)) {
        if (!in_array($user_role, $allowed_roles)) {
            header("Location: $redirect_url");
            exit();
        }
    } else {
        if ($user_role !== $allowed_roles) {
            header("Location: $redirect_url");
            exit();
        }
    }
    return true;
}

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

function formatTime($time) {
    return date('g:i A', strtotime($time));
}

function getFacultyStatusSQL() {
    return "CASE 
        WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'available'
        WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'busy'
        ELSE 'offline'
    END as status";
}

function getTimeAgoSQL() {
    return "CASE 
        WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, f.last_location_update, NOW()), ' minutes ago')
        WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, f.last_location_update, NOW()), ' hours ago')
        ELSE CONCAT(TIMESTAMPDIFF(DAY, f.last_location_update, NOW()), ' days ago')
    END as last_updated";
}

function getTimeAgo($timestamp) {
    if (!$timestamp) return 'Never';
    $time_diff = time() - strtotime($timestamp);
    if ($time_diff < 3600) {
        return floor($time_diff / 60) . ' minutes ago';
    } elseif ($time_diff < 86400) {
        return floor($time_diff / 3600) . ' hours ago';
    } else {
        return floor($time_diff / 86400) . ' days ago';
    }
}

function sendJsonResponse($data, $http_code = 200) {
    http_response_code($http_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function validateInput($input, $max_length = 255) {
    if (empty($input)) return '';
    $input = trim($input);
    if (strlen($input) > $max_length) {
        throw new InvalidArgumentException("Input exceeds maximum length");
    }
    return $input;
}

function initializeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function buildInClause($values) {
    if (empty($values)) return ['placeholders' => '', 'values' => []];
    return [
        'placeholders' => implode(',', array_fill(0, count($values), '?')),
        'values' => $values
    ];
}
?>