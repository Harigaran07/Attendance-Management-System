<?php
include 'config.php'; // Database connection

if (isset($_POST['approve_staff'])) {
    $staff_id = $_POST['staff_id']                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          A;
    $query = "UPDATE staff SET status='approved' WHERE staff_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
}

if (isset($_POST['reject_staff'])) {
    $staff_id = $_POST['staff_id'];
    $query = "UPDATE staff SET status='rejected' WHERE staff_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
}
?>
