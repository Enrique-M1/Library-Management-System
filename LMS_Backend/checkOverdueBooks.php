<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/db.php';

$sql = "
  SELECT c.checkout_id, c.user_id, c.book_id, c.due_date,
         u.username, u.email, b.title
    FROM checkouts c
    JOIN users u ON c.user_id = u.user_id
    JOIN books b ON c.book_id = b.book_id
   WHERE c.returned_date IS NULL
     AND c.due_date < CURDATE()
";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $checkoutId = intval($row['checkout_id']);
    $userId     = intval($row['user_id']);
    $bookId     = intval($row['book_id']);
    $dueDate    = $row['due_date'];
    $username   = $row['username'];
    $email      = $row['email'];
    $title      = $row['title'];
    $chk = $conn->prepare("
      SELECT COUNT(*) FROM notifications
       WHERE user_id = ? AND book_id = ?
         AND message LIKE 'Overdue%'
    ");
    $chk->bind_param('ii', $userId, $bookId);
    $chk->execute();
    $already = $chk->get_result()->fetch_row()[0];
    if ($already) continue;
    $msg = "The book “{$title}” was due on {$dueDate} and is now overdue.";
    $ins = $conn->prepare(
      "INSERT INTO notifications (user_id, book_id, message) VALUES (?, ?, ?)"
    );
    $ins->bind_param('iis', $userId, $bookId, $msg);
    $ins->execute();
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
      $mail->addAddress($email,$username);
      $mail->Subject = 'Overdue Book Reminder';
      $mail->Body    = $msg;
      $mail->send();
    } catch (Exception $e) {
      error_log("Overdue mail failed for checkout #{$checkoutId}: " . $mail->ErrorInfo);
    }
}

echo "Done checking overdues.\n";
