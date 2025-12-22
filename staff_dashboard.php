<?php
include 'config.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_department = $_SESSION['department'];
$staff_year = $_SESSION['staff_year'];
$today = date('Y-m-d');

// ----------------------
// Helper functions
// ----------------------
function getStudentCount($conn, $department, $year) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE department=? AND year=? AND status='approved'");
    $stmt->bind_param("ss", $department, $year);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

function getPresentCount($conn, $department, $year, $today) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT register_no) AS present FROM attendance 
                            WHERE department=? AND year=? AND date=? AND status='Present'");
    $stmt->bind_param("sss", $department, $year, $today);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['present'] ?? 0;
}

// ----------------------
// Year Visibility Logic
// ----------------------
if ($staff_year === '1st Year') {
    $years = ['1st Year'];
} elseif (in_array($staff_year, ['2nd Year', '3rd Year', '4th Year'])) {
    $years = ['2nd Year', '3rd Year', '4th Year'];
}

// ----------------------
// Fetch stats
// ----------------------
$stats = [];
foreach ($years as $year) {
    $stats[$year] = [
        'total' => getStudentCount($conn, $staff_department, $year),
        'present' => getPresentCount($conn, $staff_department, $year, $today)
    ];
}

// ----------------------
// Pending Approvals
// ----------------------
$stmt = $conn->prepare("SELECT register_no, name, year FROM students WHERE department=? AND status='pending'");
$stmt->bind_param("s", $staff_department);
$stmt->execute();
$students_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard - <?php echo htmlspecialchars($staff_department); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<style>
body {
    background-color: #f5f6fa;
    font-family: 'Poppins', sans-serif;
}
.container-fluid {
    padding: 0;
}
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #1e3c72, #2a5298);
    color: white;
    padding: 30px 20px;
}
.sidebar h4 {
    font-weight: 600;
    margin-bottom: 40px;
    text-align: center;
}
.sidebar a, .sidebar button {
    display: block;
    width: 100%;
    padding: 12px;
    margin-bottom: 12px;
    color: white;
    text-decoration: none;
    background: rgba(255,255,255,0.15);
    border: none;
    border-radius: 10px;
    transition: 0.3s;
    font-weight: 500;
    text-align: left;
}
.sidebar a:hover, .sidebar button:hover {
    background: rgba(255,255,255,0.35);
    transform: translateX(5px);
}
.sidebar .logout {
    background: #e63946;
    text-align: center;
}
.main {
    background: #f8f9fa;
    padding: 40px 50px;
}
.dashboard-header {
    text-align: center;
    margin-bottom: 40px;
}
.dashboard-header h2 {
    font-weight: 700;
}
.stats-row {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 30px;
}
.card {
    border: none;
    border-radius: 15px;
    padding: 25px;
    width: 260px;
    text-align: center;
    background: linear-gradient(145deg, #ffffff, #e3f2fd);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}
.card h4 {
    color: #0d6efd;
    font-weight: 600;
}
.pending-approvals {
    margin-top: 60px;
    background: #fff;
    padding: 35px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.student-card {
    background: linear-gradient(145deg, #f9f9f9, #ffffff);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: 0.3s ease;
}
.student-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.avatar {
    background: #0d6efd;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 22px;
}
.btn-sm {
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
}
#success-message {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(90deg, #4CAF50, #81C784);
    color: white;
    padding: 12px 25px;
    border-radius: 30px;
    font-size: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    z-index: 1050;
    opacity: 1;
    transition: opacity 0.8s ease-out, transform 0.5s ease;
}
</style>
</head>

<body>
<div class="container-fluid">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar d-flex flex-column justify-content-between">
            <div>
                <h4><i class="fas fa-chalkboard-teacher"></i> Staff Panel</h4>
                <form action="mark_attendance.php" method="POST">
                    <input type="hidden" name="year" value="<?php echo $staff_year; ?>">
                    <button type="submit"><i class="fas fa-check-circle"></i> Mark Attendance</button>
                </form>
                <a href="generate_report.php"><i class="fas fa-chart-bar"></i> Generate Report</a>
                <a href="manage_subjects.php"><i class="fas fa-book"></i> Manage Subjects</a>
            </div>
            <div>
                <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 main">
            <div class="dashboard-header">
                <h2><i class="fas fa-user-tie text-primary"></i> Staff Dashboard - 
                <span class="text-danger"><?php echo htmlspecialchars($staff_department); ?></span></h2>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <?php foreach ($stats as $year => $data): ?>
                    <div class="card">
                        <h4><i class="fas fa-user-graduate"></i> <?php echo $year; ?></h4>
                        <p>Total Students: <b><?php echo $data['total']; ?></b></p>
                        <p>Present Today: <b class="text-success"><?php echo $data['present']; ?></b></p>
                        <p>Absent Today: <b class="text-danger"><?php echo $data['total'] - $data['present']; ?></b></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pending Students -->
            <div class="pending-approvals mt-5">
                <h3 class="text-center text-primary mb-4"><i class="fas fa-user-clock"></i> Pending Student Approvals</h3>
                <?php if ($students_result->num_rows > 0): ?>
                    <div class="row g-4">
                        <?php while ($row = $students_result->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="student-card">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar"><i class="fas fa-user-graduate"></i></div>
                                        <div class="ms-3">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($row['name']); ?></h5>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($row['register_no']); ?></p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($row['year']); ?></span>
                                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($staff_department); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <a href="student_action.php?action=approve&register_no=<?php echo $row['register_no']; ?>" class="btn btn-success btn-sm w-25">Approve</a>
                                        <a href="student_action.php?action=reject&register_no=<?php echo $row['register_no']; ?>" class="btn btn-danger btn-sm w-25">Reject</a>
                                        <a href="edit_student.php?register_no=<?php echo $row['register_no']; ?>" class="btn btn-warning btn-sm w-25">Edit</a>
                                        <a href="student_action.php?action=delete&register_no=<?php echo $row['register_no']; ?>" class="btn btn-secondary btn-sm w-25" onclick="return confirm('Are you sure?');">Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center mt-4">
                        <img src="https://cdn-icons-png.flaticon.com/512/4076/4076549.png" width="100" class="mb-3">
                        <p class="text-muted">No pending student approvals.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
setTimeout(() => {
    const msg = document.getElementById('success-message');
    if (msg) {
        msg.style.opacity = '0';
        msg.style.transform = 'translateY(-20px)';
        setTimeout(() => msg.remove(), 800);
    }
}, 2000);
</script>
</body>
</html>
