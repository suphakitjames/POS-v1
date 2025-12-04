<?php
// Script to add staff-only check to pos.php
$posFile = '../public/pos.php';
$content = file_get_contents($posFile);

// Find the position after AuthMiddleware::check();
$searchPattern = "AuthMiddleware::check();\r\n\r\n// Check if";
$replacement = "AuthMiddleware::check();\r\n\r\n// POS เฉพาะ Staff เท่านั้น - Admin ไม่สามารถเข้าได้\r\nif (\$_SESSION['role'] !== 'staff') {\r\n    \$_SESSION['error'] = 'ระบบ POS สำหรับพนักงานเท่านั้น';\r\n    redirect('index.php');\r\n    exit;\r\n}\r\n\r\n// Check if";

$newContent = str_replace($searchPattern, $replacement, $content);

if ($newContent !== $content) {
    file_put_contents($posFile, $newContent);
    echo "<h2>✅ เพิ่ม Staff-Only Check สำเร็จ!</h2>";
    echo "<p>pos.php ถูกอัพเดทแล้ว</p>";
    echo "<p><a href='pos.php'>ไปที่ POS</a></p>";
} else {
    echo "<h2>❌ ไม่พบรูปแบบที่ต้องการแก้ไข</h2>";
    echo "<pre>";
    echo "Pattern: " . htmlspecialchars($searchPattern);
    echo "\n\nContent preview:\n";
    echo htmlspecialchars(substr($content, 0, 1000));
    echo "</pre>";
}
