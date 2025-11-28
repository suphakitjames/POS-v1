<?php
require_once '../src/Config/Database.php';
require_once '../src/Controllers/AuthController.php';

use App\Controllers\AuthController;

$auth = new AuthController();
$auth->logout();
