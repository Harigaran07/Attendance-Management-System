<?php
include 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['department'])) {
    echo json_encode([]);
    exit;
}

$department = $_SESSION['department'];
$year = $_GET['year'] ?? '';

if ($year === '') {
    echo json_encode([]);
    exit;
}

$query = "SELECT subject_code, subject_name 
          FROM subjects 
          WHERE department = '$department' AND year = '$year'
          ORDER BY subject_name ASC";

$result = mysqli_query($conn, $query);

$subjects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subjects[] = $row;
}

echo json_encode($subjects);
?>
