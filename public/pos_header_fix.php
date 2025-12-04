<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Controllers/ShiftController.php';
require_once '../src/Controllers/POSController.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Controllers\ShiftController;
use App\Controllers\POSController;
use App\Middleware\AuthMiddleware;

// Check Authentication
AuthMiddleware::check();

// POS เฉพาะ Staff เท่านั้น - Admin ไม่สามารถเข้าได้
if ($_SESSION['role'] !== 'staff') {
    $_SESSION['error'] = 'ระบบ POS สำหรับพนักงานเท่านั้น';
    redirect('index.php');
    exit;
}

// Check if user has open shift
$shiftController = new ShiftController();
$openShift = $shiftController->getOpenShift($_SESSION['user_id']);

// Ensure shift_id is set in session if open shift exists
if ($openShift) {
    $_SESSION['shift_id'] = $openShift['id'];
}

$page_title = 'ระบบขายหน้าร้าน (POS)';
require_once '../templates/layouts/header.php';
