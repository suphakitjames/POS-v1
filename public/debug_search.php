<?php
// Debug search API
session_start();
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Debug POS Search</h2>";
echo "<h3>Session Info:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

require_once '../src/Config/Database.php';
require_once '../src/Controllers/POSController.php';

use App\Config\Database;
use App\Controllers\POSController;

echo "<h3>Test Direct Search:</h3>";

try {
    $controller = new POSController();
    $keyword = 'ELEC';

    echo "Searching for: " . $keyword . "<br>";

    $products = $controller->searchProducts($keyword);

    echo "Found " . count($products) . " products:<br>";
    echo "<pre>";
    print_r($products);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<h3>Test API Call:</h3>";
echo "<a href='api/pos_search.php?keyword=ELEC' target='_blank'>Click to test API</a>";
