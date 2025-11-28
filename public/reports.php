<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../src/Helpers/functions.php';
require_once '../src/Middleware/AuthMiddleware.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/Transaction.php';

use App\Middleware\AuthMiddleware;
use App\Config\Database;
use App\Models\Transaction;

// Check Authentication
AuthMiddleware::check();

// Get Database Connection
$database = new Database();
$db = $database->connect();

$transactionModel = new Transaction($db);

// Handle Filters
$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'type' => $_GET['type'] ?? ''
];

$transactions = $transactionModel->getMovementHistory($filters);

$page_title = 'รายงานความเคลื่อนไหวสต็อก';
require_once '../templates/layouts/header.php';
require_once '../templates/reports/movement.php';
require_once '../templates/layouts/footer.php';
