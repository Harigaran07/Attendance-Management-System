<?php
include 'config.php';

$admins = [
    ["24037177106210709", "sanjai","sanjairajadesingu@gmail.com", password_hash("Sanjai@2006", PASSWORD_DEFAULT)],
    ["24037177106210303", "Karthi","karisudha94@gmail.com", password_hash("Karthi@2004", PASSWORD_DEFAULT)]
];

foreach ($admins as $admin) {
    $query = "INSERT INTO admin (admin_id, name,email, password) VALUES (?, ?, ?,?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $admin[0],$admin[1], $admin[2], $admin[3]);
    $stmt->execute();
}             

echo "Admins inserted successfully!";
?>
