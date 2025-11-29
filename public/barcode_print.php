<?php
require_once '../vendor/autoload.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/Product.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Config\Database;
use App\Models\Product;
use App\Middleware\AuthMiddleware;
use Picqer\Barcode\BarcodeGeneratorPNG;

// Check Authentication
AuthMiddleware::check();

$product_id = $_GET['product_id'] ?? null;
$qty = $_GET['qty'] ?? 1;

if (!$product_id) {
    die("Product ID is required.");
}

// Database connection
$database = new Database();
$db = $database->connect();
$product = new Product($db);
$product->id = $product_id;
$product_data = $product->read_single();

if (!$product_data) {
    die("Product not found.");
}

$generator = new BarcodeGeneratorPNG();
$barcode_data = $generator->getBarcode($product_data['sku'], $generator::TYPE_CODE_128, 2, 50);
$base64_barcode = base64_encode($barcode_data);

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode - <?= htmlspecialchars($product_data['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                margin: 0;
                size: auto;
            }

            body {
                margin: 1cm;
                background: white;
            }

            .no-print {
                display: none;
            }

            .print-container {
                box-shadow: none;
                border: none;
            }
        }

        .sticker {
            width: 5cm;
            height: 3cm;
            border: 1px dashed #ccc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 5px;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        @media print {
            .sticker {
                border: 1px solid #eee;
                /* Light border for cutting guide if needed, or remove */
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-5xl mx-auto mb-6 no-print flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Print Preview</h1>
        <div class="space-x-4">
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-blue-700 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print
            </button>
            <button onclick="window.close()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                Close
            </button>
        </div>
    </div>

    <div class="print-container bg-white p-8 rounded-xl shadow-sm mx-auto max-w-5xl">
        <div class="grid grid-cols-3 md:grid-cols-4 gap-4">
            <?php for ($i = 0; $i < $qty; $i++): ?>
                <div class="sticker bg-white rounded-md">
                    <div class="text-xs font-bold text-gray-800 text-center mb-1 truncate w-full px-1">
                        <?= htmlspecialchars($product_data['name']) ?>
                    </div>
                    <img src="data:image/png;base64,<?= $base64_barcode ?>" alt="Barcode" class="max-w-full h-auto">
                    <div class="text-xs text-gray-600 mt-1 font-mono">
                        <?= htmlspecialchars($product_data['sku']) ?>
                    </div>
                    <div class="text-sm font-bold text-gray-900 mt-1">
                        <?= number_format($product_data['selling_price'], 2) ?> ฿
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

</body>

</html>