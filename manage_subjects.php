<?php
include 'config.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$department = $_SESSION['department'];
$message = "";

// Handle Add Subject
if (isset($_POST['add_subject'])) {
    $subject_code = mysqli_real_escape_string($conn, $_POST['subject_code']);
    $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $year = $_POST['year'];
    $department_input = mysqli_real_escape_string($conn, $_POST['department']);

    $query = "INSERT INTO subjects (subject_code, subject_name, department, year)
              VALUES ('$subject_code', '$subject_name', '$department_input', '$year')";
    if (mysqli_query($conn, $query)) {
        $message = "✅ Subject added successfully!";
    } else {
        $message = "❌ Error adding subject.";
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM subjects WHERE id='$id' AND department='$department'");
    $message = "🗑️ Subject deleted successfully!";
}

// Fetch Subjects
$result = mysqli_query($conn, "SELECT * FROM subjects WHERE department='$department' ORDER BY year, subject_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Subjects</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<style>
    body {
        background: #eef2f7;
        font-family: 'Poppins', sans-serif;
    }

    .header-bar {
        background: linear-gradient(90deg, #007bff, #0048b5);
        color: white;
        padding: 15px 30px;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    }

    .header-bar h3 {
        margin: 0;
        font-weight: 600;
    }

    .container {
        margin-top: 50px;
    }

    .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }

    .card h4 {
        font-weight: 600;
        color: #333;
    }

    .btn-success {
        background: linear-gradient(90deg, #28a745, #218838);
        border: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .btn-success:hover {
        transform: scale(1.05);
        background: linear-gradient(90deg, #218838, #1e7e34);
    }

    .table {
        border-radius: 12px;
        overflow: hidden;
    }

    thead th {
        background-color: #007bff;
        color: white;
    }

    tbody tr:hover {
        background-color: #f2f7ff;
        transition: 0.3s;
    }

    .footer-note {
        text-align: center;
        color: #666;
        font-size: 0.9rem;
        margin-top: 30px;
    }

    .form-control, .form-select {
        border-radius: 8px;
    }

    .back-btn {
        background: #fff;
        color: #007bff;
        border: 1px solid #007bff;
        border-radius: 8px;
        font-weight: 500;
    }

    .back-btn:hover {
        background: #007bff;
        color: #fff;
    }
</style>
</head>
<body>

<div class="header-bar d-flex justify-content-between align-items-center">
    <h3><i class="fas fa-book me-2"></i>Manage Subjects</h3>
    <a href="staff_dashboard.php" class="btn back-btn"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
</div>

<div class="container">
    <div class="card p-4">
        <h4 class="text-center mb-4">📘 Add or Manage Department Subjects</h4>

        <?php if ($message): ?>
            <div class="alert alert-info text-center fw-bold"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Add Subject Form -->
        <form method="POST" class="row g-3 align-items-center">
            <div class="col-md-2">
                <input type="text" name="subject_code" class="form-control" placeholder="Code" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="subject_name" class="form-control" placeholder="Subject Name" required>
            </div>
            <div class="col-md-2">
                <select name="year" class="form-select" required>
                    <option value="">Year</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>" class="form-control" readonly>
            </div>
            <div class="col-md-2 text-center">
                <button type="submit" name="add_subject" class="btn btn-success w-100"><i class="fas fa-plus-circle me-1"></i>Add</button>
            </div>
        </form>

        <hr>

        <!-- Subjects Table -->
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Subject Name</th>
                        <th>Year</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo $row['year']; ?></td>
                            <td><?php echo $row['department']; ?></td>
                            <td>
                                <a href="?delete_id=<?php echo $row['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this subject?')">
                                   <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr><td colspan="6" class="text-muted">No subjects found for your department.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="footer-note">© <?php echo date('Y'); ?> College Attendance Management System</p>
</div>

</body>
</html>
