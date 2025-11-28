<?php
require_once '../src/Config/Database.php';

use App\Config\Database;

$db = new Database();
$conn = $db->connect();

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password_hash = :hash WHERE username = 'admin'");
$stmt->bindParam(':hash', $hash);

if ($stmt->execute()) {
    echo "Password reset successfully. New hash: " . $hash;
} else {
    echo "Error resetting password.";
}
