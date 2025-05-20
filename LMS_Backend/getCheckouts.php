<?php
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
$uid = $_SESSION['user_id'];

$sql = "
  SELECT c.checkout_id,
         b.book_id,
         b.title,
         c.borrowed_date,
         c.due_date
    FROM checkouts c
    JOIN books     b ON c.book_id = b.book_id
   WHERE c.user_id = ?
     AND c.returned_date IS NULL
   ORDER BY c.due_date ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i',$uid);
$stmt->execute();
$result = $stmt->get_result();

$loans = [];
while ($row = $result->fetch_assoc()) {
  $loans[] = $row;
}

echo json_encode(['success'=>true,'data'=>$loans]);
