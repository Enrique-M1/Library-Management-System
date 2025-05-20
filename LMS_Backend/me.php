<?php
$allowed = [
    'http://localhost:3000',
    'http://localhost:5173'
  ];
  if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
      header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
      header("Access-Control-Allow-Credentials: true");
  }
  header("Access-Control-Allow-Methods: GET, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      exit;
  }

  header("Content-Type: application/json; charset=UTF-8");
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

echo json_encode([
  'success'  => true,
  'username' => $_SESSION['username'],
  'role'     => $_SESSION['role']
]);
