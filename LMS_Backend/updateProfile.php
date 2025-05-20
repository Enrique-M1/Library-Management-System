<?php
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
  header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
  header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;
header("Content-Type: application/json; charset=UTF-8");

session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'error'=>'Method not allowed']);
  exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$username = trim($payload['username'] ?? '');
$email    = trim($payload['email']    ?? '');
$phone    = trim($payload['phone']    ?? '');

if (!$username || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Invalid input']);
  exit;
}

$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("
  UPDATE users
     SET username = ?, email = ?, phone = ?
   WHERE user_id = ?
");
$stmt->bind_param('sssi',$username,$email,$phone,$uid);
$stmt->execute();

echo json_encode(['success'=>true]);
