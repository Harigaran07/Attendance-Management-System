<?php
include 'config.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['register_no'])) {
    $register_no = $_GET['register_no'];
    
    $query = "SELECT * FROM students WHERE register_no=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $register_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    if (!$student) {
        $_SESSION['error'] = "Student not found!";
        header("Location: staff_dashboard.php");
        exit();
    }
} else {
    $_SESSION['error'] = "No student selected!";
    header("Location: staff_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_register_no = $_POST['register_no'];
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $department = $_POST['department'];
    $year = $_POST['year'];

    $update_query = "UPDATE students SET register_no=?, name=?, mobile=?, department=?, year=? WHERE register_no=?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssss", $new_register_no, $name, $mobile, $department, $year, $register_no);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Student updated successfully!";
        header("Location: staff_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating student.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Student</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .edit-form-box {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            max-width: 600px;
            width: 100%;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #343a40;
        }
        .form-label {
            font-weight: 600;
        }
        .btn-primary {
            width: 100%;
            font-size: 16px;
            padding: 10px;
        }
    </style>
</head>
<body>

    <div class="edit-form-box">
        <h2>Edit Student</h2>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Register Number</label>
                <input type="text" class="form-control" name="register_no" value="<?php echo htmlspecialchars($student['register_no']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Mobile Number</label>
                <input type="text" class="form-control" name="mobile" value="<?php echo htmlspecialchars($student['mobile']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Department</label>
                <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($student['department']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Year</label>
                <select class="form-select" name="year" required>
                    <option value="1st Year" <?php if ($student['year'] == '1st Year') echo 'selected'; ?>>1st Year</option>
                    <option value="2nd Year" <?php if ($student['year'] == '2nd Year') echo 'selected'; ?>>2nd Year</option>
                    <option value="3rd Year" <?php if ($student['year'] == '3rd Year') echo 'selected'; ?>>3rd Year</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>

</body>
</html>
