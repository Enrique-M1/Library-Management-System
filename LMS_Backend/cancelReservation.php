<?php
session_start();
require __DIR__ . '/db.php';
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'error'=>'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$resId = intval($data['reservation_id'] ?? 0);
$uid   = $_SESSION['user_id'];

if ($resId <= 0) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Invalid reservation id']);
  exit;
}

$stmt = $conn->prepare("
  DELETE FROM reservations
   WHERE reservation_id = ?
     AND user_id        = ?
");
$stmt->bind_param('ii', $resId, $uid);
if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$stmt->error]);
  exit;
}

echo json_encode(['success'=>true]);
