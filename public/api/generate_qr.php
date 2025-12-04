<?php
session_start();

// Custom Debug Log
function debug_log($message)
{
    file_put_contents('../qr_debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

debug_log("Request received: " . $_SERVER['REQUEST_URI']);

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

use App\Config\Database;
use App\Helpers\PromptPayQR;

// Start output buffering
ob_start();

try {
    // Define absolute path to src
    $srcPath = dirname(__DIR__, 2) . '/src';
    debug_log("Src Path: " . $srcPath);

    if (!file_exists($srcPath . '/Config/Database.php')) {
        throw new Exception("Database.php not found at " . $srcPath . '/Config/Database.php');
    }

    require_once $srcPath . '/Config/Database.php';
    require_once $srcPath . '/Helpers/PromptPayQR.php';

    // 1. Check Authentication
    if (!isset($_SESSION['user_id'])) {
        debug_log("Session user_id not set. Session: " . print_r($_SESSION, true));
        throw new Exception('Unauthorized access - Please login again', 401);
    }

    // 2. Validate Input
    $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
    debug_log("Amount: " . $amount);

    if ($amount <= 0) {
        throw new Exception('Invalid amount', 400);
    }

    // 3. Connect to Database
    debug_log("Connecting to database...");
    $db = new Database();
    $conn = $db->connect();
    debug_log("Connected.");

    // 4. Fetch PromptPay ID
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'promptpay_id' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $promptpayId = $result['setting_value'] ?? '0812345678';
    debug_log("PromptPay ID: " . $promptpayId);

    // 5. Generate Payload
    $payload = PromptPayQR::generate($promptpayId, $amount);
    debug_log("Payload generated.");

    // 6. Response
    ob_clean();
    echo json_encode([
        'success' => true,
        'payload' => $payload,
        'amount' => $amount,
        'promptpay_id' => $promptpayId
    ]);
} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());

    $code = $e->getCode() ?: 500;
    if ($code < 100 || $code > 599) $code = 500;

    http_response_code($code);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
