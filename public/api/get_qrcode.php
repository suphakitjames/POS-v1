<?php
session_start();

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

use App\Config\Database;
use App\Helpers\PromptPayQR;

try {
    // Define absolute path to src
    $srcPath = dirname(__DIR__, 2) . '/src';

    if (!file_exists($srcPath . '/Config/Database.php')) {
        throw new Exception("Database.php not found");
    }

    require_once $srcPath . '/Config/Database.php';
    require_once $srcPath . '/Helpers/PromptPayQR.php';

    // 1. Check Authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access', 401);
    }

    // 2. Validate Input
    $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

    if ($amount <= 0) {
        throw new Exception('Invalid amount', 400);
    }

    // 3. Connect to Database
    $db = new Database();
    $conn = $db->connect();

    // 4. Fetch PromptPay ID
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'promptpay_id' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $promptpayId = $result['setting_value'] ?? '0812345678'; // Default if not set

    // 5. Generate Payload
    $payload = PromptPayQR::generate($promptpayId, $amount);

    // 6. Response
    echo json_encode([
        'status' => 'success',
        'payload' => $payload,
        'amount' => $amount,
        'promptpay_id' => $promptpayId
    ]);
} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    if ($code < 100 || $code > 599) $code = 500;

    http_response_code($code);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
