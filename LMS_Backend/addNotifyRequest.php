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
  echo json_encode(['success'=>false,'error'=>'Method Not Allowed']);
  exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$book_id = intval($payload['book_id'] ?? 0);
if ($book_id < 1) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Invalid book_id']);
  exit;
}

$stmt = $conn->prepare("
  INSERT INTO reservations (user_id, book_id, status)
       VALUES (?, ?, 'Notify')
");
$stmt->bind_param('ii', $_SESSION['user_id'], $book_id);
if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$stmt->error]);
  exit;
}

echo json_encode(['success'=>true,'notify_id'=>$stmt->insert_id]);
