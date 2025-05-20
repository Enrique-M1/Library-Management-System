<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
  header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
  header("Access-Control-Allow-Credentials: true");
}
header("Content-Type: application/json; charset=UTF-8");
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role']!=='Admin') {
  http_response_code(403);
  echo json_encode(['success'=>false,'error'=>'Forbidden']);
  exit;
}

$sql = "
  SELECT n.notification_id,
         n.user_id,
         u.username,
         n.book_id,
         b.title      AS book_title,
         n.message,
         n.notification_date
    FROM notifications n
    JOIN users u ON n.user_id = u.user_id
    JOIN books b ON n.book_id = b.book_id
   ORDER BY n.notification_date DESC
";
$result = $conn->query($sql);
$out = [];
while ($row = $result->fetch_assoc()) {
  $out[] = $row;
}

echo json_encode(['success'=>true,'data'=>$out]);
