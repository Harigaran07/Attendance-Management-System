<?php
include 'config.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$staff_department = $_SESSION['department'];

if (isset($_GET['action']) && isset($_GET['register_no'])) {
    $action = $_GET['action'];
    $register_no = $_GET['register_no'];

    if ($action == 'approve') {
        // Approve student (Change status to 'approved')
        $query = "UPDATE students SET status='approved' WHERE register_no=? AND department=?";
    } elseif ($action == 'reject') {
        // Reject student (Change status to 'rejected')
        $query = "UPDATE students SET status='rejected' WHERE register_no=? AND department=?";
    } elseif ($action == 'delete') {
        // Delete student from database
        $query = "DELETE FROM students WHERE register_no=? AND department=?";
    } else {
        $_SESSION['error'] = "Invalid action!";
        header("Location: staff_dashboard.php");
        exit();
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $register_no, $staff_department);

    if ($stmt->execute()) {
        $_SESSION['success'] = ucfirst($action) . "d student successfully!";
    } else {
        $_SESSION['error'] = "Error while processing request.";
    }
}

// Redirect back to staff dashboard
header("Location: staff_dashboard.php");
exit();
?>
