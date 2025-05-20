<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$allowed = [
    'http://localhost:3000',
    'http://localhost:5173'
  ];
  if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
      header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
      header("Access-Control-Allow-Credentials: true");
  }
  header("Access-Control-Allow-Methods: POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
  header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not logged in']);
  exit;
}

$uid = $_SESSION['user_id'];
require 'db.php';

// favorite count
$stmt = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$favCount = $stmt->get_result()->fetch_row()[0];

// borrowed count
$stmt = $conn->prepare("SELECT COUNT(*) FROM checkouts WHERE user_id = ? AND returned_date IS NULL");
$stmt->bind_param('i', $uid);
$stmt->execute();
$borrowedCount = $stmt->get_result()->fetch_row()[0];

// returned count
$stmt = $conn->prepare("SELECT COUNT(*) FROM checkouts WHERE user_id = ? AND returned_date IS NOT NULL");
$stmt->bind_param('i', $uid);
$stmt->execute();
$returnedCount = $stmt->get_result()->fetch_row()[0];

// reserve count
$stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status != 'Denied'");
$stmt->bind_param('i', $uid);
$stmt->execute();
$reservationCount = $stmt->get_result()->fetch_row()[0];

echo json_encode([
  'favorites' => (int)$favCount,
  'borrowed'  => (int)$borrowedCount,
  'returned'  => (int)$returnedCount,
  'reserved'  => (int)$reservationCount
]);
//