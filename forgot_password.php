<?php
session_start();
include 'config.php'; // Database connection
date_default_timezone_set("Asia/Kolkata"); // IST

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("
        SELECT 'students' AS type, email FROM students WHERE email = ?
        UNION 
        SELECT 'staff' AS type, email FROM staff WHERE email = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_type = $row['type'];

        $token = bin2hex(random_bytes(32)); // 64-character token
        $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour")); // 1 hour expiry

        $stmt = $conn->prepare("
            INSERT INTO password_resets (email, token, user_type, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $email, $token, $user_type, $expires_at);
        $stmt->execute();



        // Mail setup
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'www.mr.comp@gmail.com'; // Your Gmail address
            $mail->Password = 'yddc poyt zqod lmbl'; // Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('www.mr.comp@gmail.com', 'Attendance Management System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = ' Reset Your Attendance System Password';
            $reset_link = "http://localhost/Attendance_Management_System/reset_password.php?token=$token";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 10px;'>
                    <h2 style='color: #007bff;'>Password Reset Request</h2>
                    <p>Dear user,</p>
                    <p>We received a request to reset your password. You can reset it using the link below:</p>
                    <p><a href='$reset_link' style='color: #28a745; font-weight: bold;'>Click here to reset your password</a></p>
                    <p><strong>Note:</strong> This link will expire in 1 hour for your security.</p>
                    <p>If you didn’t request this, please ignore this email. Your account remains secure.</p>
                    <br>
                    <p style='color: #6c757d;'>— Attendance Management System</p>
                </div>";

            $mail->send();
            $_SESSION['success'] = "✅ A password reset link has been sent to your email.";
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Email could not be sent. Error: " . $mail->ErrorInfo;
        }
    } else {
        $_SESSION['error'] = "❌ Email not found in our records!";
    }

    header("Location: forgot_password.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Attendance Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 {
            text-align: center;
            color: #343a40;
        }
        input[type=email] {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            border: none;
            color: white;
            margin-top: 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .message {
            margin-top: 15px;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['success'])): ?>
        <div class="message success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <form action="" method="POST">
        <input type="email" name="email" placeholder="Enter your registered email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</div>
</body>
</html>
