<?php
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') {
    exit;
}

header("Content-Type: application/json; charset=UTF-8");

session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}
$uid = $_SESSION['user_id'];

$sql = "
  SELECT r.reservation_id,
         b.book_id,
         b.title,
         r.reservation_date,
         r.status
    FROM reservations r
    JOIN books b ON r.book_id = b.book_id
   WHERE r.user_id = ?
   ORDER BY r.reservation_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i',$uid);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
  $reservations[] = $row;
}

echo json_encode(['success'=>true,'data'=>$reservations]);
