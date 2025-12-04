<?php
session_start();
require_once '../../src/Helpers/functions.php';
require_once '../../src/Config/Database.php';
require_once '../../src/Controllers/POSController.php';

use App\Controllers\POSController;

// ตรวจสอบ Authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

header('Content-Type: application/json');

// รับข้อมูล JSON จาก Request Body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

// ตรวจสอบว่ามีกะเปิดอยู่หรือไม่
if (!isset($_SESSION['shift_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'กรุณาเปิดกะก่อนทำการขาย']);
    exit;
}

$controller = new POSController();

$items = $input['items'] ?? [];
$paymentMethod = $input['payment_method'] ?? 'cash';

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ไม่มีรายการสินค้า']);
    exit;
}

// บันทึกการขาย
$result = $controller->checkout(
    $_SESSION['user_id'],
    $_SESSION['shift_id'],
    $items,
    $paymentMethod
);

echo json_encode($result);
