<?php
// Test database connection
require_once '../src/Config/Database.php';

use App\Config\Database;

echo "<pre>";
echo "Testing Database Connection...\n\n";

try {
    $db = new Database();
    $conn = $db->connect();

    if ($conn) {
        echo "✅ Database connection: SUCCESS\n";

        // Test users table
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Users table: {$result['count']} users found\n";

        // Test products table
        $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Products table: {$result['count']} products found\n";

        // Test POS tables
        $stmt = $conn->query("SELECT COUNT(*) as count FROM shifts");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Shifts table: {$result['count']} shifts found\n";

        $stmt = $conn->query("SELECT COUNT(*) as count FROM sales");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Sales table: {$result['count']} sales found\n";

        $stmt = $conn->query("SELECT COUNT(*) as count FROM settings");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Settings table: {$result['count']} settings found\n";

        // Check admin user
        $stmt = $conn->prepare("SELECT username, role FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            echo "\n✅ Admin user exists: {$admin['username']} (role: {$admin['role']})\n";
        } else {
            echo "\n❌ Admin user NOT found!\n";
        }
    } else {
        echo "❌ Database connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n</pre>";
