<?php 
include 'config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$today = date('Y-m-d');

// Get all departments
$departments_query = "SELECT DISTINCT department FROM students";
$departments_result = mysqli_query($conn, $departments_query);

// Prepare department-wise + year-wise data
$department_stats = [];

while ($dept = mysqli_fetch_assoc($departments_result)) {
    $department = $dept['department'];

    // For each department, loop through years
    $years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    $yearly_data = [];

    foreach ($years as $year) {
        // Total students
        $total_query = "SELECT COUNT(*) AS total_students FROM students 
                        WHERE department='$department' AND year='$year'";
        $total_result = mysqli_fetch_assoc(mysqli_query($conn, $total_query));
        $total_students = $total_result['total_students'] ?? 0;

        // Present students
        $present_query = "SELECT COUNT(DISTINCT register_no) AS total_present 
                          FROM attendance 
                          WHERE department='$department' AND year='$year' 
                          AND date='$today' AND status='Present'";
        $present_result = mysqli_fetch_assoc(mysqli_query($conn, $present_query));
        $total_present = $present_result['total_present'] ?? 0;

        // Absent count
        $total_absent = $total_students - $total_present;

        $yearly_data[$year] = [
            'total_students' => $total_students,
            'total_present'  => $total_present,
            'total_absent'   => $total_absent
        ];
    }

    $department_stats[] = [
        'department' => $department,
        'years'      => $yearly_data
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Department & Year Stats</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            margin: 0;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            width: 260px;
            height: 100%;
            background: linear-gradient(180deg, #002f4b, #005792);
            color: #fff;
            padding: 25px 15px;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar h3 {
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: #fff;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 35px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 18px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .header h4 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }

        /* Department Cards */
        .dept-card {
            border: none;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            transition: 0.3s ease;
        }

        .dept-card:hover {
            transform: translateY(-6px);
        }

        .dept-card h5 {
            font-weight: 700;
            color: #005792;
            text-align: center;
            margin-bottom: 15px;
        }

        .year-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .year-section h6 {
            font-weight: 600;
            color: #333;
        }

        .year-section .stat {
            font-size: 14px;
            margin: 2px 0;
        }

        .stat .label { font-weight: 500; }
        .stat .value { font-weight: 600; }

        .text-total { color: #0d6efd; }
        .text-present { color: #198754; }
        .text-absent { color: #dc3545; }
    </style>
</head>

<body>
    <div class="sidebar">
        <h3><i class="fas fa-user-shield me-2"></i>Admin Panel</h3>
        <a href="#" class="active"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="admin_generate_report.php"><i class="fas fa-file-alt me-2"></i> Reports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h4>Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?> 👋</h4>
            <a href="logout.php" class="btn btn-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <h4 class="mb-4 text-primary">
            <i class="fas fa-chart-pie me-2"></i>Department & Year-wise Attendance (<?= $today ?>)
        </h4>

        <div class="row">
            <?php foreach ($department_stats as $dept): ?>
                <div class="col-md-6 mb-4">
                    <div class="dept-card">
                        <h5><?= htmlspecialchars($dept['department']) ?></h5>
                        <?php foreach ($dept['years'] as $year => $stats): ?>
                            <?php if ($stats['total_students'] > 0): ?>
                                <div class="year-section">
                                    <h6><?= $year ?></h6>
                                    <div class="stat text-total">
                                        <span class="label">Total:</span>
                                        <span class="value"><?= $stats['total_students'] ?></span>
                                    </div>
                                    <div class="stat text-present">
                                        <span class="label">Present:</span>
                                        <span class="value"><?= $stats['total_present'] ?></span>
                                    </div>
                                    <div class="stat text-absent">
                                        <span class="label">Absent:</span>
                                        <span class="value"><?= $stats['total_absent'] ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr class="my-5">

        <h4 class="mb-3 text-primary">
            <i class="fas fa-users-cog me-2"></i>Staff Management
        </h4>

        <ul class="nav nav-tabs mb-3" id="staffTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" role="tab">Pending</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" role="tab">Approved</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" role="tab">Rejected</button>
            </li>
        </ul>

        <div class="tab-content" id="staffTabsContent">
            <!-- Pending -->
            <div class="tab-pane fade show active" id="pending">
                <?php
                $pending_staff = $conn->query("SELECT * FROM staff WHERE status='pending'");
                if ($pending_staff->num_rows > 0) {
                    echo "<table class='table table-striped align-middle'>
                            <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Actions</th></tr></thead><tbody>";
                    while ($row = $pending_staff->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['department']}</td>
                                <td>
                                    <form method='POST' action='staff_actions.php' class='d-flex gap-2'>
                                        <input type='hidden' name='staff_id' value='{$row['staff_id']}'>
                                        <button name='approve' class='btn btn-success btn-sm'>
                                            <i class='fas fa-check'></i> Approve
                                        </button>
                                        <button name='reject' class='btn btn-danger btn-sm'>
                                            <i class='fas fa-times'></i> Reject
                                        </button>
                                    </form>
                                </td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<div class='alert alert-info text-center'>🎉 No pending approvals</div>";
                }
                ?>
            </div>

            <!-- Approved -->
            <div class="tab-pane fade" id="approved">
                <?php
                $approved = $conn->query("SELECT * FROM staff WHERE status='approved'");
                if ($approved->num_rows > 0) {
                    echo "<table class='table table-hover align-middle'>
                            <thead><tr><th>Name</th><th>Email</th><th>Department</th></tr></thead><tbody>";
                    while ($row = $approved->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['department']}</td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<div class='alert alert-secondary text-center'>No approved staff yet.</div>";
                }
                ?>
            </div>

            <!-- Rejected -->
            <div class="tab-pane fade" id="rejected">
                <?php
                $rejected = $conn->query("SELECT * FROM staff WHERE status='rejected'");
                if ($rejected->num_rows > 0) {
                    echo "<table class='table table-bordered align-middle'>
                            <thead><tr><th>Name</th><th>Email</th><th>Department</th></tr></thead><tbody>";
                    while ($row = $rejected->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['department']}</td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<div class='alert alert-secondary text-center'>No rejected staff.</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
