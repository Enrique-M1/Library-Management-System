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
require __DIR__ . "/db.php";

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$book_id = intval($data['book_id'] ?? 0);
if ($book_id < 1) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Invalid book_id']);
  exit;
}
$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("
  SELECT 1 FROM favorites
   WHERE user_id = ? AND book_id = ?
");
$stmt->bind_param('ii',$uid,$book_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
  $stmt = $conn->prepare("
    DELETE FROM favorites
     WHERE user_id = ? AND book_id = ?
  ");
  $stmt->bind_param('ii',$uid,$book_id);
  $stmt->execute();
  echo json_encode(['success'=>true,'favorited'=>false]);
  exit;
}

$stmt = $conn->prepare("
  INSERT INTO favorites (user_id, book_id)
       VALUES (?, ?)
");
$stmt->bind_param('ii',$uid,$book_id);
if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$stmt->error]);
  exit;
}
echo json_encode(['success'=>true,'favorited'=>true]);
