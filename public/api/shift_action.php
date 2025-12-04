<?php
session_start();
require_once '../../src/Helpers/functions.php';
require_once '../../src/Config/Database.php';
require_once '../../src/Controllers/ShiftController.php';

use App\Controllers\ShiftController;

// ตรวจสอบ Authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

header('Content-Type: application/json');

$controller = new ShiftController();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'open':
        // เปิดกะ
        $startCash = floatval($_POST['start_cash'] ?? 0);
        $result = $controller->openShift($_SESSION['user_id'], $startCash);

        if ($result['success']) {
            $_SESSION['shift_id'] = $result['shift_id'];
        }

        echo json_encode($result);
        break;

    case 'close':
        // ปิดกะ
        $shiftId = intval($_POST['shift_id'] ?? $_SESSION['shift_id'] ?? 0);
        $endCash = floatval($_POST['end_cash'] ?? 0);

        $result = $controller->closeShift($shiftId, $endCash);

        if ($result['success']) {
            unset($_SESSION['shift_id']);
        }

        echo json_encode($result);
        break;

    case 'check':
        // ตรวจสอบกะที่เปิดอยู่
        $shift = $controller->getOpenShift($_SESSION['user_id']);

        if ($shift) {
            $_SESSION['shift_id'] = $shift['id'];
            echo json_encode([
                'success' => true,
                'shift' => $shift
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่มีกะที่เปิดอยู่'
            ]);
        }
        break;

    case 'summary':
        // ดึงสรุปยอดขายในกะ
        $shiftId = intval($_GET['shift_id'] ?? $_SESSION['shift_id'] ?? 0);
        $summary = $controller->getShiftSummary($shiftId);

        echo json_encode([
            'success' => true,
            'summary' => $summary
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
