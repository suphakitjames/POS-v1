<?php
require_once '../src/Config/Database.php';

use App\Config\Database;

try {
    $db = new Database();
    $conn = $db->connect();

    $sql = "CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` varchar(50) NOT NULL,
        `setting_value` text,
        `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $conn->exec($sql);
    echo "Table 'settings' created successfully.<br>";

    // Insert default PromptPay ID if not exists
    $sqlInsert = "INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('promptpay_id', '0812345678')";
    $conn->exec($sqlInsert);
    echo "Default PromptPay ID inserted.<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
