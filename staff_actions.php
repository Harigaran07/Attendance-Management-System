<?php
include 'config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['staff_id'])) {
    $staff_id = $_POST['staff_id'];
    $msg = "";

    if (isset($_POST['approve'])) {
        $query = "UPDATE staff SET status = 'approved' WHERE staff_id = ?";
        $msg = "✅ Staff approved successfully!";
    } elseif (isset($_POST['reject'])) {
        $query = "UPDATE staff SET status = 'rejected' WHERE staff_id = ?";
        $msg = "❌ Staff rejected successfully!";
    } elseif (isset($_POST['delete'])) {
        $query = "DELETE FROM staff WHERE staff_id = ?";
        $msg = "🗑️ Staff deleted successfully!";
    } else {
        header("Location: admin_dashboard.php");
        exit();
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();

    $_SESSION['alert'] = $msg;

    header("Location: admin_dashboard.php");
    exit();
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>
