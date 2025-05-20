<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

$allowed = 'http://localhost:3000';
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowed) {
    header("Access-Control-Allow-Origin: $allowed");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method Not Allowed']);
    exit;
}

$payload  = json_decode(file_get_contents("php://input"), true);
$username = trim($payload["username"] ?? '');
$password = $payload["password"]    ?? '';
$email    = trim($payload["email"]    ?? '');
$phone    = trim($payload["phone"]    ?? '');
$role     = trim($payload["role"]     ?? '');

if (!$username || !$password || !$email) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Username, password, and email are required']);
    exit;
}

$stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success'=>false,'error'=>'Username already taken, please choose another']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins  = $conn->prepare("
    INSERT INTO users (username, password, email, phone, role)
    VALUES (?, ?, ?, ?, ?)
");
$ins->bind_param("sssss", $username, $hash, $email, $phone, $role);

if (!$ins->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$ins->error]);
    exit;
}

echo json_encode([
    'success' => true,
    'user_id' => $ins->insert_id
]);
