<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
require 'db.php';
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'error'=>'Method Not Allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
if (!$username || !$password) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Username and password required']);
  exit;
}

$stmt = $conn->prepare("SELECT user_id, username, password, email, role FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user || !password_verify($password, $user['password'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Invalid credentials']);
  exit;
}

// Generate OTP
$otp = random_int(100000, 999999);
$_SESSION['otp'] = $otp;
$_SESSION['otp_user'] = $user['user_id'];
$_SESSION['otp_exp'] = time() + 300;  // 5 min expiry

// Send OTP via PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->Port       = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAuth   = true;
    $mail->Username   = 'email';
    $mail->Password   = 'password';
    $mail->setFrom('your@gmail.com', 'Library LMS');
    $mail->addAddress($user['email'], $user['username']);
    $mail->Subject = 'Your Library Login Code';
    $mail->Body    = "Hello {$user['username']},\n\nYour OTP is: {$otp}\nIt expires in 5 minutes.";
    $mail->send();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Could not send OTP: ' . $mail->ErrorInfo]);
    exit;
}

echo json_encode(['success'=>true,'pending_otp'=>true,'message'=>'OTP sent to email']);
exit;