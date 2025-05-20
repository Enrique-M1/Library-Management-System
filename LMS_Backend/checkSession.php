<?php
$allowed = 'http://localhost:3000';
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowed) {
    header("Access-Control-Allow-Origin: $allowed");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['loggedIn' => false]);
    exit;
}

echo json_encode([
  'loggedIn' => true,
  'role'     => $_SESSION['role']
]);
exit;
