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
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/db.php';
use PHPMailer\PHPMailer\PHPMailer;

$sql = "
  SELECT r.reservation_id, r.user_id, u.email, u.username, b.title
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN books b ON r.book_id = b.book_id
   WHERE r.status = 'Reserved'
     AND b.available_copies > 0
";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->Port       = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth   = true;
        $mail->Username   = 'email';
        $mail->Password   = 'password';
        $mail->setFrom('your@gmail.com','Library LMS');
        $mail->addAddress($row['email'],$row['username']);
        $mail->Subject = 'Your Reserved Book Is Now Available';
        $mail->Body    = "Hello {$row['username']},\n\n"
                       ."Good news! “{$row['title']}”, which you reserved, is now available for you to check out.\n"
                       ."Please visit the library or use the online portal to complete your checkout.\n\n"
                       ."Thanks!";
        $mail->send();

        $upd = $conn->prepare("UPDATE reservations SET status = 'Notified' WHERE reservation_id = ?");
        $upd->bind_param('i', $row['reservation_id']);
        $upd->execute();

        echo "Notified {$row['email']} for reservation {$row['reservation_id']}\n";
    } catch (Exception $e) {
        error_log("Failed to email {$row['email']}: {$mail->ErrorInfo}");
    }
}
