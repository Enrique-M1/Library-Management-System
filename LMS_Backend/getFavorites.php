<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
require __DIR__ . "/db.php";

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}

$uid = $_SESSION['user_id'];
$sql = "
  SELECT b.book_id, b.title, b.author, b.image_url
    FROM favorites f
    JOIN books b ON f.book_id = b.book_id
   WHERE f.user_id = ?
   ORDER BY f.favorite_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i',$uid);
$stmt->execute();
$res = $stmt->get_result();

$books = [];
while ($row = $res->fetch_assoc()) {
  $books[] = $row;
}

echo json_encode(['success'=>true,'data'=>$books]);
