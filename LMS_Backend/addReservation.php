<?php
ini_set('display_errors', 1);
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
require __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}
$uid = $_SESSION['user_id'];

$payload = json_decode(file_get_contents('php://input'), true);
$book_id = intval($payload['book_id'] ?? 0);
if ($book_id < 1) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid book_id']);
    exit;
}

$stmt = $conn->prepare("
  SELECT COUNT(*)
    FROM reservations
   WHERE user_id=?
     AND book_id=?
     AND status='Reserved'
");
$stmt->bind_param('ii', $uid, $book_id);
$stmt->execute();
$already = $stmt->get_result()->fetch_row()[0];
if ($already > 0) {
    echo json_encode(['success'=>false,'error'=>'Already reserved']);
    exit;
}

$stmt = $conn->prepare("
  INSERT INTO reservations (user_id, book_id, status)
       VALUES (?, ?, 'Reserved')
");
$stmt->bind_param('ii', $uid, $book_id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}
$reservation_id = $stmt->insert_id;
$userStmt = $conn->prepare("
  SELECT username, email 
    FROM users 
   WHERE user_id = ?
");
$userStmt->bind_param('i', $uid);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$username = $user['username'];
$toEmail  = $user['email'];
date_default_timezone_set('America/Chicago');
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->Port       = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAuth   = true;
    $mail->Username   = 'email';
    $mail->Password   = 'password';
    $mail->setFrom('your@gmail.com', 'Your Library');
    $mail->addAddress($toEmail, $username);

    $mail->Subject = 'Your Reservation is Confirmed';
    $body  = "Hello {$username},\n\n";
    $body .= "You have successfully placed a hold on Book ID #{$book_id}.\n";
    $body .= "Reservation Date: " . date('m-d-Y H:i') . "\n\n";
    $body .= "We will notify you when it becomes available for checkout.\n\n";
    $body .= "Thank you,\nLibrary Team";

    $mail->Body = $body;
    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
}

echo json_encode([
    'success'        => true,
    'reservation_id' => $reservation_id
]);
exit;
