<?php
session_start();
include 'config.php'; // Your DB connection

// Get token from URL
$token = $_GET['token'] ?? '';
if (!$token) {
    die("Invalid token.");
}

// Fetch reset request from database
$stmt = $conn->prepare("
    SELECT * FROM password_resets 
    WHERE token = ? AND used = 0 AND expires_at > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("This link is invalid or expired.");
}

$row = $result->fetch_assoc();
$_SESSION['reset_email'] = $row['email'];
$_SESSION['reset_user_type'] = $row['user_type'];
$_SESSION['reset_token'] = $token;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | Attendance Management</title>
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
        h2 { text-align: center; color: #343a40; }
        input[type=password] { 
            width: 100%; padding: 10px; margin-top: 15px; 
            border-radius: 5px; border: 1px solid #ced4da; 
        }
        button { 
            width: 100%; padding: 10px; background: #28a745; 
            border: none; color: white; margin-top: 20px; 
            border-radius: 5px; cursor: pointer; 
        }
        button:hover { background: #218838; }
        .message { margin-top: 15px; text-align: center; padding: 10px; border-radius: 5px; }
        .error { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body>
<div class="container">
    <h2>Reset Password</h2>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['success'])): ?>
        <div class="message success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form action="reset_password_process.php" method="POST">
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
</div>
</body>
</html>
