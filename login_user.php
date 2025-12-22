<?php
session_start();
include 'config.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // 1️⃣ **Admin Login Check**
    $query = "SELECT * FROM admin WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['name']; // use correct column name (like 'name' or 'fullname')

            $_SESSION['role'] = 'admin';

            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // 2️⃣ **Staff Login Check**
    $query = "SELECT * FROM staff WHERE email = ? AND status = 'approved' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $staff = $result->fetch_assoc();

        if (password_verify($password, $staff['password'])) {
            $_SESSION['staff_id'] = $staff['staff_id'];
            $_SESSION['email'] = $staff['email'];
            $_SESSION['department'] = $staff['department'];
            $_SESSION['staff_year'] = $staff['year']; // Ensure this field exists
            $_SESSION['role'] = "staff";

            header("Location: staff_dashboard.php");
            exit();
        }
    }

    // 3️⃣ **Student Login Check**
    $query = "SELECT * FROM students WHERE email = ? AND status = 'approved' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $student = $result->fetch_assoc();

        if (password_verify($password, $student['password'])) {
            $_SESSION['student_id'] = $student['register_no']; // Use register_no as student_id
            $_SESSION['email'] = $student['email'];
            $_SESSION['department'] = $student['department'];
            $_SESSION['year'] = $student['year'];
            $_SESSION['role'] = "student";

            header("Location: student_dashboard.php");
            exit();
        }
    }

    // ❌ **If all login attempts fail**
    $_SESSION['error'] = "Invalid email or password!";
    header("Location: login.php");
    exit();
}

$stmt->close();
$conn->close();
?>
