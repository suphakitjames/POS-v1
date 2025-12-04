<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../src/Config/Database.php';
require_once '../src/Helpers/functions.php';
require_once '../src/Helpers/PromptPayQR.php';

use App\Config\Database;
use App\Helpers\PromptPayQR;

echo "<h1>Debug QR Generation</h1>";

try {
    echo "<p>Loading files... Done.</p>";

    echo "<p>Connecting to Database...</p>";
    $db = new Database();
    $conn = $db->connect();
    echo "<p>Connected.</p>";

    echo "<p>Fetching PromptPay ID...</p>";
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'promptpay_id'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $promptpayId = $result['setting_value'] ?? 'Not Found';
    echo "<p>PromptPay ID: " . htmlspecialchars($promptpayId) . "</p>";

    echo "<p>Generating QR for 100.00 THB...</p>";
    $payload = PromptPayQR::generate($promptpayId, 100.00);
    echo "<p>Payload: " . htmlspecialchars($payload) . "</p>";

    echo "<p><strong>Success!</strong></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
