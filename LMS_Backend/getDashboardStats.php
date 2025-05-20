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
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
  http_response_code(403);
  echo json_encode(['success'=>false,'error'=>'Forbidden']);
  exit;
}

$data = [];

$res = $conn->query("SELECT COUNT(*) AS cnt FROM books");
$data['totalBooks'] = ($r = $res->fetch_assoc()) ? intval($r['cnt']) : 0;

$res = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$data['totalUsers'] = ($r = $res->fetch_assoc()) ? intval($r['cnt']) : 0;

$res = $conn->query("
  SELECT COUNT(*) AS cnt
    FROM checkouts
   WHERE due_date < CURDATE()
     AND returned_date IS NULL
");
$data['overdueLoans'] = ($r = $res->fetch_assoc()) ? intval($r['cnt']) : 0;

$res = $conn->query("
  SELECT COUNT(*) AS cnt
    FROM reservations
   WHERE status = 'Reserved'
");
$data['pendingReservations'] = ($r = $res->fetch_assoc()) ? intval($r['cnt']) : 0;

echo json_encode(['success'=>true,'data'=>$data]);
