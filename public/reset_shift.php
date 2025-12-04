<?php
require_once '../src/Config/Database.php';

use App\Config\Database;

$db = new Database();
$conn = $db->connect();

// Get user id for staff_test
$stmt = $conn->prepare("SELECT id FROM users WHERE username = 'staff_test'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Close all shifts for this user
    $stmt = $conn->prepare("UPDATE shifts SET end_time = NOW(), status = 'closed' WHERE user_id = ? AND status = 'open'");
    $stmt->execute([$user['id']]);
    echo "All shifts closed for staff_test";
} else {
    echo "User staff_test not found";
}
