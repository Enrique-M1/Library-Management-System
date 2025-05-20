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

header("Content-Type: application/json; charset=UTF-8");
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}

$uid = intval($_GET['user_id'] ?? $_SESSION['user_id']);
if ($uid < 1) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Invalid user_id']);
  exit;
}

$stmt = $conn->prepare("SELECT user_id, username, email, phone, role FROM users WHERE user_id = ?");
$stmt->bind_param('i',$uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
  http_response_code(404);
  echo json_encode(['success'=>false,'error'=>'User not found']);
  exit;
}

$co = $conn->prepare("
  SELECT c.checkout_id, b.book_id, b.title  AS book_title,
         c.borrowed_date, c.due_date, c.returned_date
    FROM checkouts c
    JOIN books b ON c.book_id = b.book_id
   WHERE c.user_id = ?
   ORDER BY c.borrowed_date DESC
");
$co->bind_param('i',$uid);
$co->execute();
$res1 = $co->get_result();
$checkouts = [];
while ($r = $res1->fetch_assoc()) $checkouts[] = $r;
$rv = $conn->prepare("
  SELECT r.reservation_id, b.book_id, b.title AS book_title,
         r.reservation_date, r.status
    FROM reservations r
    JOIN books b ON r.book_id = b.book_id
   WHERE r.user_id = ?
   ORDER BY r.reservation_date DESC
");
$rv->bind_param('i',$uid);
$rv->execute();
$res2 = $rv->get_result();
$reservations = [];
while ($r = $res2->fetch_assoc()) $reservations[] = $r;

echo json_encode([
  'success'      => true,
  'data'         => [
    'user'         => $user,
    'checkouts'    => $checkouts,
    'reservations' => $reservations
  ]
]);
exit;
