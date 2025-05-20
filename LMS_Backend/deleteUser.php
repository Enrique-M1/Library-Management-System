<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

header('Content-Type: application/json; charset=UTF-8');
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = intval($data['user_id'] ?? 0);
if ($user_id < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user_id']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit;
}

echo json_encode(['success' => true]);
exit;
?>
