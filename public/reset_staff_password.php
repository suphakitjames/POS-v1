<?php
require_once '../src/Config/Database.php';

use App\Config\Database;

$db = new Database();
$conn = $db->connect();

$username = 'staff01';
$password = 'password123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
$stmt->execute([$hashed_password, $username]);

echo "Password for $username reset to $password";
