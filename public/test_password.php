<?php
// Test and fix password hash for all users
require_once '../src/Config/Database.php';

use App\Config\Database;

echo "<pre>";
$db = new Database();
$conn = $db->connect();

$password = 'admin123'; // Default password for all users

// Get all users
$stmt = $conn->prepare("SELECT username, password_hash FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Testing Password for All Users:\n";
echo "================================\n\n";

$needUpdate = [];

foreach ($users as $user) {
    echo "User: {$user['username']}\n";
    echo "Current Hash: {$user['password_hash']}\n";

    if (password_verify($password, $user['password_hash'])) {
        echo "✅ Password 'admin123' is CORRECT!\n\n";
    } else {
        echo "❌ Password 'admin123' is WRONG!\n";
        $needUpdate[] = $user['username'];
        echo "\n";
    }
}

if (!empty($needUpdate)) {
    echo "=====================================\n";
    echo "Updating passwords for: " . implode(', ', $needUpdate) . "\n";
    echo "=====================================\n\n";

    $newHash = password_hash($password, PASSWORD_DEFAULT);

    foreach ($needUpdate as $username) {
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        if ($updateStmt->execute([$newHash, $username])) {
            echo "✅ Password for '$username' UPDATED!\n";
        } else {
            echo "❌ Failed to update password for '$username'\n";
        }
    }

    echo "\n================================\n";
    echo "✅ All passwords fixed!\n";
    echo "You can now login with:\n";
    echo "  - admin / admin123 (Admin)\n";
    echo "  - staff01 / admin123 (Staff)\n";
    echo "================================\n";
} else {
    echo "✅ All passwords are already correct!\n";
}

echo "</pre>";
