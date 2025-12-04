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

try {
    $controller = new POSController();
    $keyword = $_GET['keyword'] ?? '';

    if (empty($keyword)) {
        echo json_encode([]);
        exit;
    }

    // ค้นหาสินค้า
    $products = $controller->searchProducts($keyword);
    echo json_encode($products);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
