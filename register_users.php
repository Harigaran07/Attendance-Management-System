<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $year = mysqli_real_escape_string($conn, $_POST['year']);

    if ($role == "staff") {
        $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);

        // Check for duplicate staff_id or email
        $check_query = "SELECT * FROM staff WHERE staff_id = '$staff_id' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            echo "<script>alert('Staff ID or Email already exists!'); window.history.back();</script>";
            exit();
        }

        // Insert new staff
        $query = "INSERT INTO staff (staff_id, name, email, department, mobile, year, password, status) 
                  VALUES ('$staff_id', '$name', '$email', '$department', '$mobile', '$year', '$password', 'pending')";

    } else {
        $register_no = mysqli_real_escape_string($conn, $_POST['register_no']);
        $parent_email = mysqli_real_escape_string($conn, $_POST['parent_email']);  // ✅ new line

        // Check for duplicate register_no or email
        $check_query = "SELECT * FROM students WHERE register_no = '$register_no' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            echo "<script>alert('Register Number or Email already exists!'); window.history.back();</script>";
            exit();
        }

        // Insert new student including parent_email
        $query = "INSERT INTO students (register_no, name, email, parent_email, department, mobile, year, password, status) 
                  VALUES ('$register_no', '$name', '$email', '$parent_email', '$department', '$mobile', '$year', '$password', 'pending')";
    }

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Registration successful. Wait for approval.'); window.location.href='login.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
