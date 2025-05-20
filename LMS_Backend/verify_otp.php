<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header("Content-Type: application/json; charset=UTF-8");
session_start();

$data     = json_decode(file_get_contents('php://input'), true);
$inputOtp = intval($data['otp'] ?? 0);

if (!isset($_SESSION['otp'], $_SESSION['otp_user'], $_SESSION['otp_exp'])) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'No OTP in progress']);
  exit;
}
if (time() > $_SESSION['otp_exp']) {
  session_unset();
  http_response_code(403);
  echo json_encode(['success'=>false,'error'=>'OTP expired']);
  exit;
}
if ($inputOtp !== $_SESSION['otp']) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Invalid code']);
  exit;
}

require __DIR__ . '/db.php';
$userId = $_SESSION['otp_user'];
$_SESSION['user_id'] = $userId;

$stmt = $conn->prepare("SELECT username, role FROM users WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

unset($_SESSION['otp'], $_SESSION['otp_user'], $_SESSION['otp_exp']);

echo json_encode([
  'success' => true,
  'role'    => $user['role']
]);
exit;
