<?php
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'www.mr.comp@gmail.com';
    $mail->Password = 'yddc poyt zqod lmbl';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('mr.comp@gmail.com', 'College Attendance System');
    $mail->addAddress('harigaransakthivel@gmail.com'); // test recipient

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email from PmHPMailer.';

    $mail->send();
    echo "Email sent snuccessfully!";
} catch (Exception $e) {
    echo "Mailer Error: " . $mail->ErrorInfo;
}
