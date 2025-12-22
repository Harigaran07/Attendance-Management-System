<?php 
include 'config.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_department = $_SESSION['department'] ?? '';

date_default_timezone_set('Asia/Kolkata');
$current_time = date('H:i');
$current_date = date('Y-m-d');

$start_time = "09:00";
$end_time = "17:00";
$attendance_allowed = ($current_time >= $start_time && $current_time <= $end_time);

$staff_query = mysqli_query($conn, "SELECT year FROM staff WHERE staff_id = '$staff_id' AND department = '$staff_department'");
$staff_role = mysqli_fetch_assoc($staff_query)['year'] ?? '';

if ($staff_role === '1st Year') {
    $allowed_years = ['1st Year'];
} elseif (in_array($staff_role, ['2nd Year', '3rd Year', '4th Year'])) {
    $allowed_years = ['2nd Year', '3rd Year', '4th Year'];
}

$selected_year = $_GET['year'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');

$students = [];
if ($selected_year && in_array($selected_year, $allowed_years)) {
    $students_result = mysqli_query($conn, "SELECT * FROM students WHERE department='$staff_department' AND year='$selected_year' AND status='approved'");
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}

$attendance_data = [];
$attendance_result = mysqli_query($conn, "SELECT * FROM attendance WHERE department='$staff_department' AND year='$selected_year' AND date='$selected_date'");
while ($row = mysqli_fetch_assoc($attendance_result)) {
    $attendance_data[$row['register_no']][$row['period']] = $row['status'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance - Staff Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <style>
        body {
            background-color: #eef2f6;
            font-family: "Poppins", sans-serif;
        }
        .header-bar {
            background: linear-gradient(90deg, #007bff, #0056d6);
            color: #fff;
            padding: 15px 30px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }
        .header-bar h3 {
            margin: 0;
            font-weight: 600;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-top: 40px;
            background: #fff;
        }
        .form-select, .form-control {
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #005ad6;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        thead th {
            background-color: #007bff;
            color: white;
            font-weight: 500;
        }
        tbody tr:hover {
            background-color: #f1f5ff;
        }
        .mark-all {
            margin: 0 3px;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            padding: 0;
        }
        .footer-note {
            margin-top: 20px;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="header-bar d-flex justify-content-between align-items-center">
    <h3><i class="fas fa-chalkboard-teacher me-2"></i>Mark Attendance</h3>
    <a href="staff_dashboard.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="container">
    <div class="card p-4">
        <?php if (isset($_SESSION['error'])) { ?>
            <div class="alert alert-danger text-center fw-bold"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php } ?>

        <!-- Selection Form -->
        <form method="GET" action="">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <label for="year" class="form-label">Select Year</label>
                    <select name="year" id="year" class="form-select" required>
                        <option value="">-- Select Year --</option>
                        <?php foreach ($allowed_years as $year) { ?>
                            <option value="<?php echo $year; ?>" <?php if ($year == $selected_year) echo 'selected'; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="date" class="form-label">Select Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                           value="<?php echo $selected_date; ?>" max="<?php echo $current_date; ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="period" class="form-label">Select Period</label>
                    <select name="period" id="period" class="form-select" required>
                        <option value="">-- Select Period --</option>
                        <?php for ($p = 1; $p <= 8; $p++) { ?>
                            <option value="<?php echo $p; ?>" <?php if (($p) == ($_GET['period'] ?? '')) echo 'selected'; ?>>
                                Period <?php echo $p; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="subject" class="form-label">Select Subject</label>
                    <select name="subject" id="subject" class="form-select" required>
                        <option value="">-- Select Subject --</option>
                    </select>
                </div>

                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary px-4 mt-2">
                        <i class="fas fa-search"></i> Fetch Students
                    </button>
                </div>
            </div>
        </form>

        <!-- Attendance Table -->
        <?php if ($attendance_allowed && $selected_year && !empty($students) && isset($_GET['period'])) { 
            $selected_period = $_GET['period'];
            $selected_subject = $_GET['subject'];
        ?>
            <form method="POST" action="save_attendance.php">
                <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                <input type="hidden" name="period" value="<?php echo $selected_period; ?>">
                <input type="hidden" name="subject" value="<?php echo $selected_subject; ?>">

                <div class="table-responsive">
                    <table class="table table-bordered text-center mt-4">
                        <thead>
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Status (Period <?php echo $selected_period; ?>)</th>
                                <th>Quick Mark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student) {
                                $saved_status = $attendance_data[$student['register_no']][$selected_period] ?? '';
                            ?>
                                <tr>
                                    <td><strong><?php echo $student['register_no']; ?></strong></td>
                                    <td><?php echo $student['name']; ?></td>
                                    <td>
                                        <label class="me-3"><input type="radio" name="attendance[<?php echo $student['register_no']; ?>]" value="Present" <?php echo ($saved_status === 'Present') ? 'checked' : ''; ?>> Present</label>
                                        <label class="me-3"><input type="radio" name="attendance[<?php echo $student['register_no']; ?>]" value="Absent" <?php echo ($saved_status === 'Absent') ? 'checked' : ''; ?>> Absent</label>
                                       <label><input type="radio" name="attendance[<?php echo $student['register_no']; ?>]" value="OD" <?php echo ($saved_status === 'OD') ? 'checked' : ''; ?>> OD</label>

                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-success btn-sm mark-all" data-status="Present" data-student="<?php echo $student['register_no']; ?>">P</button>
                                        <button type="button" class="btn btn-danger btn-sm mark-all" data-status="Absent" data-student="<?php echo $student['register_no']; ?>">A</button>
                                        <button type="button" class="btn btn-warning btn-sm mark-all" data-status="OD" data-student="<?php echo $student['register_no']; ?>">OD</button>

                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" name="mark_attendance" class="btn btn-success px-5 py-2">
                        <i class="fas fa-save"></i> Submit Attendance
                    </button>
                </div>
            </form>
        <?php } ?>
    </div>

    <p class="text-center footer-note">© <?php echo date('Y'); ?> College Attendance Management System</p>
</div>

<script>
    document.querySelectorAll(".mark-all").forEach(button => {
        button.addEventListener("click", function () {
            const studentId = this.getAttribute("data-student");
            const status = this.getAttribute("data-status");
            document.querySelectorAll(`input[name^="attendance[${studentId}]"]`).forEach(input => {
                if (input.value === status) input.checked = true;
            });
        });
    });

    document.getElementById('year').addEventListener('change', function() {
        const year = this.value;
        const subjectSelect = document.getElementById('subject');
        if (!year) {
            subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
            return;
        }
        fetch(`fetch_subjects.php?year=${encodeURIComponent(year)}`)
            .then(response => response.json())
            .then(data => {
                subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
                data.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.subject_code;
                    option.textContent = `${sub.subject_name} `;
                    subjectSelect.appendChild(option);
                });
            });
    });
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('saved') && urlParams.get('saved') === 'true') {
        // Show success notification
        const toast = document.createElement('div');
        toast.textContent = "✅ Attendance saved successfully!";
        toast.style.position = 'fixed';
        toast.style.bottom = '30px';
        toast.style.right = '30px';
        toast.style.background = '#4CAF50';
        toast.style.color = 'white';
        toast.style.padding = '15px 25px';
        toast.style.borderRadius = '8px';
        toast.style.boxShadow = '0 4px 10px rgba(0,0,0,0.2)';
        toast.style.fontSize = '16px';
        toast.style.zIndex = '1000';
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 2500);
    }
});
</script>


</body>
</html>
