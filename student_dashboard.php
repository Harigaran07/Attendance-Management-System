<?php
include 'config.php';
session_start();

if (isset($_SESSION['success'])) {
    echo "<div class='alert alert-success text-center'>{$_SESSION['success']}</div>";
    unset($_SESSION['success']); // Clear the message after displaying it
}



// Check if staff is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Get student details
$query = "SELECT * FROM students WHERE register_no = '$student_id'";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    echo "Student not found!";
    exit();
}

$name = $student['name'];
$department = $student['department'];
$year = $student['year'];

// Get today's date
$today = date('Y-m-d');

// Get total students in department
$total_q = "SELECT COUNT(*) AS total FROM students WHERE department = '$department'";
$total_r = mysqli_query($conn, $total_q);
$total_students = mysqli_fetch_assoc($total_r)['total'];

// Get number of present students today in department
$present_q = "SELECT COUNT(DISTINCT register_no) AS present FROM attendance WHERE department = '$department' AND date = '$today'";
$present_r = mysqli_query($conn, $present_q);
$present_count = mysqli_fetch_assoc($present_r)['present'];

$absent_count = $total_students - $present_count;
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a2e8f43b91.js" crossorigin="anonymous"></script>
    <style>
        body {
            background: linear-gradient(135deg, #e0f7fa, #f0f0f0);
            font-family: 'Segoe UI', sans-serif;
        }
        .dashboard-header {
            background: linear-gradient(90deg, #4b6cb7, #182848);
            color: white;
            padding: 50px 30px;
            text-align: center;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .card-custom {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease-in-out;
        }
        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
        }
        .btn-custom {
            border-radius: 30px;
            padding: 10px 20px;
            font-weight: 500;
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="dashboard-header">
    <h1><i class="fas fa-user-graduate"></i> Student Dashboard</h1>
    <h4 class="mt-3">Welcome, <?php echo htmlspecialchars($name); ?>!</h4>
    <p class="mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($department); ?> | <strong>Year:</strong> <?php echo htmlspecialchars($year); ?></p>
    <div class="mt-3">
        <a href="logout.php" class="btn btn-outline-light btn-custom me-2"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <a href="stu_generate_report.php" class="btn btn-light text-primary btn-custom"><i class="fas fa-file-download"></i> My Attendance Report</a>
    </div>
</div>

<div class="container my-5">
    <div class="row g-4 justify-content-center text-center">
        <div class="col-md-4">
            <div class="card card-custom">
                <div class="text-primary stat-icon"><i class="fas fa-users"></i></div>
                <h5>Total Students</h5>
                <h2><?php echo $total_students; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom">
                <div class="text-success stat-icon"><i class="fas fa-user-check"></i></div>
                <h5>Present Today</h5>
                <h2><?php echo $present_count; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom">
                <div class="text-danger stat-icon"><i class="fas fa-user-times"></i></div>
                <h5>Absent Today</h5>
                <h2><?php echo $absent_count; ?></h2>
            </div>
        </div>
    </div>
</div>

</body>
</html>
