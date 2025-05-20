<?php
session_start();
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
  header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
  header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}

$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("
  SELECT username, email, phone
    FROM users
   WHERE user_id = ?
   LIMIT 1
");
$stmt->bind_param('i',$uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo json_encode(['success'=>true,'data'=>$user]);
