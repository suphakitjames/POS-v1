<?php
// Debug POS Search
require_once '../src/Config/Database.php';
require_once '../src/Controllers/POSController.php';

use App\Config\Database;
use App\Controllers\POSController;

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug POS Search</h1>";

// Test 1: Check database connection
echo "<h2>1. Database Connection</h2>";
try {
    $db = new Database();
    $conn = $db->connect();
    echo "✅ Database connected successfully<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check if products table exists and has data
echo "<h2>2. Products in Database</h2>";
$query = "SELECT id, sku, barcode, name, stock_quantity FROM products LIMIT 5";
$stmt = $conn->query($query);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo "⚠️ No products found in database!<br>";
} else {
    echo "✅ Found " . count($products) . " products:<br>";
    echo "<pre>";
    print_r($products);
    echo "</pre>";
}

// Test 3: Search for "coke"
echo "<h2>3. Search for 'coke'</h2>";
$searchQuery = "SELECT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE (p.sku LIKE :keyword 
                   OR p.barcode LIKE :keyword 
                   OR p.name LIKE :keyword)
                AND p.stock_quantity > 0
                LIMIT 20";

$searchTerm = "%coke%";
$stmt = $conn->prepare($searchQuery);
$stmt->bindParam(':keyword', $searchTerm);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "⚠️ No results found for 'coke'<br>";

    // Try without stock filter
    echo "<h3>Trying without stock filter:</h3>";
    $queryNoStock = "SELECT p.*, c.name as category_name 
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE (p.sku LIKE :keyword 
                        OR p.barcode LIKE :keyword 
                        OR p.name LIKE :keyword)
                     LIMIT 20";
    $stmt2 = $conn->prepare($queryNoStock);
    $stmt2->bindParam(':keyword', $searchTerm);
    $stmt2->execute();
    $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results2)) {
        echo "❌ Still no results. Product doesn't exist.<br>";
    } else {
        echo "✅ Found products but they might have stock_quantity = 0:<br>";
        echo "<pre>";
        print_r($results2);
        echo "</pre>";
    }
} else {
    echo "✅ Found " . count($results) . " products with stock > 0:<br>";
    echo "<pre>";
    print_r($results);
    echo "</pre>";
}

// Test 4: Use POSController
echo "<h2>4. POSController Search</h2>";
$controller = new POSController();
$controllerResults = $controller->searchProducts('coke');

if (empty($controllerResults)) {
    echo "❌ POSController returned no results<br>";
} else {
    echo "✅ POSController found " . count($controllerResults) . " products:<br>";
    echo "<pre>";
    print_r($controllerResults);
    echo "</pre>";
}

echo "<h2>5. JavaScript JSON Output (for POS frontend)</h2>";
echo "<pre>";
echo json_encode($controllerResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";
