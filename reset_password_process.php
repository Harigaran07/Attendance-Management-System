<?php
session_start();
include 'config.php'; // Your DB connection

// Check if session has token and email
if (!isset($_SESSION['reset_email'], $_SESSION['reset_user_type'], $_SESSION['reset_token'])) {
    $_SESSION['error'] = "Invalid session. Please try again.";
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$user_type = $_SESSION['reset_user_type']; // 'students' or 'staff'
$token = $_SESSION['reset_token'];

// Get form data
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (!$password || !$confirm_password) {
    $_SESSION['error'] = "Please fill all fields.";
    header("Location: reset_password.php?token=$token");
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: reset_password.php?token=$token");
    exit();
}

// Validate token again to ensure it hasn't expired or been used
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "This link is invalid or expired.";
    header("Location: forgot_password.php");
    exit();
}

// Hash the new password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update password in the correct table
if ($user_type === 'students') {
    $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE email = ?");
} elseif ($user_type === 'staff') {
    $update_stmt = $conn->prepare("UPDATE staff SET password = ? WHERE email = ?");
} else {
    $_SESSION['error'] = "Invalid user type.";
    header("Location: forgot_password.php");
    exit();
}

$update_stmt->bind_param("ss", $hashed_password, $email);
if ($update_stmt->execute()) {
    // Mark token as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    // Clear session
    unset($_SESSION['reset_email'], $_SESSION['reset_user_type'], $_SESSION['reset_token']);

    $_SESSION['success'] = "✅ Password has been reset successfully. You can now login.";
    header("Location: login.php");
    exit();
} else {
    $_SESSION['error'] = "Failed to reset password. Try again.";
    header("Location: reset_password.php?token=$token");
    exit();
}
?>
