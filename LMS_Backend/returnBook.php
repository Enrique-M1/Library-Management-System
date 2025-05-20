<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

$data = json_decode(file_get_contents('php://input'), true);
$checkout_id = intval($data['checkout_id'] ?? 0);
if ($checkout_id < 1) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid checkout_id']);
    exit;
}

$returned = date('Y-m-d');
$stmt = $conn->prepare("
  UPDATE checkouts
     SET returned_date = ?
   WHERE checkout_id = ?
     AND user_id = ?
");
$stmt->bind_param('sii', $returned, $checkout_id, $_SESSION['user_id']);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

$stmt = $conn->prepare("
  UPDATE books
     SET available_copies = LEAST(available_copies + 1, total_copies)
   WHERE book_id = (
     SELECT book_id FROM checkouts WHERE checkout_id = ?
   )
");
$stmt->bind_param('i', $checkout_id);
$stmt->execute();

$stmt = $conn->prepare("
  SELECT r.reservation_id, r.user_id, u.email, u.username, b.book_id, b.title
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN books b ON r.book_id = b.book_id
   WHERE r.book_id = (
       SELECT book_id FROM checkouts WHERE checkout_id = ?
     )
     AND r.status = 'Reserved'
   ORDER BY r.reservation_date
   LIMIT 1
");
$stmt->bind_param('i', $checkout_id);
$stmt->execute();
$next = $stmt->get_result()->fetch_assoc();

if ($next) {
    $upd = $conn->prepare(
      "UPDATE reservations SET status='Avaliable' WHERE reservation_id=?"
    );
    $upd->bind_param('i', $next['reservation_id']);
    $upd->execute();
    $msg = "“{$next['title']}” is now available for pickup.";

    $ins = $conn->prepare(
      "INSERT INTO notifications (user_id, book_id, message) VALUES (?, ?, ?)"
    );
    $ins->bind_param('iis', $next['user_id'], $next['book_id'], $msg);
    if (!$ins->execute()) {
      error_log("Notification INSERT failed: " . $ins->error);
    }

    try {
      $mail = new PHPMailer(true);
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = 'email';
      $mail->Password   = 'password';
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = 587;
      $mail->setFrom('your@gmail.com','Library LMS');
      $mail->addAddress($next['email'],$next['username']);
      $mail->Subject = 'Your reserved book is now available';
      $mail->Body    = $msg;
      $mail->send();
    } catch (Exception $e) {
      error_log("Mail error: " . $mail->ErrorInfo);
    }
}

echo json_encode(['success'=>true]);
