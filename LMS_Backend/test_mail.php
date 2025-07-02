<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';
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
    $mail->addAddress('recipientEmail@gmail.com', 'recipientName');
    $mail->isHTML(false);
    $mail->Subject = 'PHPmailer Test';
    $mail->Body    = "This is a test of PHPMailer SMTP.\n";
    $mail->send();
    echo 'Mail sent successfully';
} catch (Exception $e) {
    echo "Mail failed: {$mail->ErrorInfo}";
}
