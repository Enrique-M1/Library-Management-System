<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

header('Content-Type: application/json;charset=UTF-8');
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role']!=='Admin') {
  http_response_code(403);
  echo json_encode(['success'=>false,'error'=>'Forbidden']);
  exit;
}

$sql = "
 SELECT r.reservation_id, r.user_id, u.username,
        r.book_id, b.title,b.available_copies,
        r.reservation_date, r.status
   FROM reservations r
   JOIN users u ON r.user_id = u.user_id
   JOIN books b ON r.book_id = b.book_id
  ORDER BY r.reservation_date DESC
";
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

echo json_encode(['success'=>true,'data'=>$data]);

