<?php
include 'config.php';

$department = $_POST['department'] ?? '';
$year = $_POST['year'] ?? '';

if ($department && $year) {
    $stmt = $conn->prepare("SELECT DISTINCT subject FROM subjects WHERE department = ? AND year = ? ORDER BY subject ASC");
    $stmt->bind_param("ss", $department, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Subject</option>';
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['subject']}'>{$row['subject']}</option>";
    }
} else {
    echo '<option value="">Select Subject</option>';
}
?>
