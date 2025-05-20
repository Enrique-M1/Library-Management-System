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

date_default_timezone_set('America/Chicago');

$today = date('Y-m-d');
$sql = "
  SELECT c.checkout_id, c.user_id, u.email, u.username, b.title, c.due_date
    FROM checkouts c
    JOIN users u ON c.user_id = u.user_id
    JOIN books b ON c.book_id = b.book_id
   WHERE c.returned_date IS NULL
     AND c.due_date < ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $today);
$stmt->execute();
$res = $stmt->get_result();

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
        $mail->Subject = 'Overdue Book Reminder';
        $mail->Body    = "Hi {$row['username']},\n\n"
                       ."Our records show “{$row['title']}” was due on {$row['due_date']} and is now overdue.\n"
                       ."Please return it as soon as possible to avoid further fines.\n\n"
                       ."Thank you!";
        $mail->send();
        echo "Notified {$row['email']} about checkout {$row['checkout_id']}\n";
    } catch (Exception $e) {
        error_log("Failed to email {$row['email']}: {$mail->ErrorInfo}");
    }
}
