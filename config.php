<?php

$host = "localhost"; // Database host
$username = "AMS";  // Database username
$password = "";      // Database password (leave empty if using XAMPP)
$database = "attendance_system"; // Your database name

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
