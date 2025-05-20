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
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

header("Content-Type: application/json; charset=UTF-8");
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'Not authenticated']);
    exit;
}
$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("
  SELECT book_id
    FROM reservations
   WHERE user_id = ?
     AND status = 'Notify'
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$result = $stmt->get_result();

$bookIds = [];
while ($row = $result->fetch_assoc()) {
    $bookIds[] = (int)$row['book_id'];
}

echo json_encode(['success'=>true, 'data'=>$bookIds]);
exit;
