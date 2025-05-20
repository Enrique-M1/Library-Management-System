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
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

header("Content-Type: application/json; charset=UTF-8");
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD']!=='POST') {
  http_response_code(405);
  exit(json_encode(['success'=>false,'error'=>'Method Not Allowed']));
}
if (!isset($_SESSION['role']) || $_SESSION['role']!=='Admin') {
  http_response_code(403);
  exit(json_encode(['success'=>false,'error'=>'Forbidden']));
}

$data   = json_decode(file_get_contents('php://input'), true);
$resId  = intval($data['reservation_id'] ?? 0);
$status = strtolower(trim($data['status'] ?? ''));

if (!$resId || !in_array($status, ['approve','deny'])) {
  http_response_code(400);
  exit(json_encode(['success'=>false,'error'=>'Invalid input']));
}

$newStatus = $status === 'approve' ? 'Approved' : 'Denied';
$stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
$stmt->bind_param('si', $newStatus, $resId);
if (!$stmt->execute()) {
  http_response_code(500);
  exit(json_encode(['success'=>false,'error'=>$stmt->error]));
}

if ($status === 'approve') {
  $q = $conn->prepare("
    SELECT u.email,u.username,b.title
      FROM reservations r
      JOIN users u ON u.user_id=r.user_id
      JOIN books b ON b.book_id=r.book_id
     WHERE r.reservation_id = ?
  ");
  $q->bind_param('i',$resId);
  $q->execute();
  $info = $q->get_result()->fetch_assoc();

  if ($info) {
    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = 'email';
      $mail->Password   = 'password';
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = 587;

      $mail->setFrom('email','Library LMS');
      $mail->addAddress($info['email'],$info['username']);
      $mail->Subject = 'Your reservation is approved';
      $mail->Body    = "Hello {$info['username']},\n\n"
                     ."Your hold on “{$info['title']}” has been approved.\n"
                     ."We’ll notify you when it’s available.\n";

      $mail->send();
    } catch (Exception $e) {
      error_log("PHPMailer error for reservation #{$resId}: " . $mail->ErrorInfo);
    }
  }
}

echo json_encode(['success'=>true]);
