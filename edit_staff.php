<?php
include 'config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['staff_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$staff_id = $_GET['staff_id'];
$query = "SELECT * FROM staff WHERE staff_id = '$staff_id'";
$result = mysqli_query($conn, $query);
$staff = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $mobile= $_POST['mobile'];
    
    $update_query = "UPDATE staff SET name='$name', email='$email', department='$department' , mobile='$mobile' WHERE staff_id='$staff_id'";
    mysqli_query($conn, $update_query);
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Edit Staff</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" value="<?php echo $staff['name']; ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo $staff['email']; ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" class="form-control" name="department" value="<?php echo $staff['department']; ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Mobile</label>
            <input type="text" class="form-control" name="mobile" value="<?php echo $staff['mobile']; ?>" required>
        </div>
        <button type="submit" name="update" class="btn btn-primary">Update</button>
    </form>
</div>

</body>
</html>
