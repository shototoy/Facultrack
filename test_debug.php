<?php
header('Content-Type: application/json');

echo json_encode([
    'POST' => $_POST,
    'GET' => $_GET,
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'php_input' => file_get_contents('php://input')
]);
?>