<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Controllers/POSController.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Controllers\POSController;
use App\Middleware\AuthMiddleware;

// Check Authentication
AuthMiddleware::check();

$saleId = $_GET['id'] ?? null;
if (!$saleId) {
    die('ไม่พบข้อมูลการขาย');
}

$controller = new POSController();
$sale = $controller->getSale($saleId);

if (!$sale) {
    die('ไม่พบข้อมูลการขาย');
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ - <?= h($sale['receipt_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }

        /* Thermal Receipt Styles */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 10mm;
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .receipt {
                width: 100%;
                max-width: none;
            }
        }

        .receipt {
            max-width: 80mm;
            margin: 0 auto;
            background: white;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Print Button (Hidden on Print) -->
    <div class="no-print fixed top-4 right-4 z-50">
        <button onclick="window.print()" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 shadow-lg">
            พิมพ์ใบเสร็จ
        </button>
        <button onclick="window.close()" class="ml-2 px-6 py-3 bg-gray-600 text-white rounded-lg font-semibold hover:bg-gray-700 shadow-lg">
            ปิด
        </button>
    </div>

    <div class="receipt p-4" style="padding-top: 20px;">
        <!-- Header -->
        <div class="text-center mb-4 border-b-2 border-dashed border-gray-400 pb-4">
            <h1 class="text-2xl font-bold mb-1">ร้านค้า ABC</h1>
            <p class="text-xs text-gray-600">123 ถนนสุขุมวิท กรุงเทพฯ 10110</p>
            <p class="text-xs text-gray-600">โทร: 02-123-4567</p>
            <p class="text-xs text-gray-600 mt-2">เลขประจำตัวผู้เสียภาษี: 0123456789012</p>
        </div>

        <!-- Receipt Info -->
        <div class="mb-4 border-b border-dashed border-gray-400 pb-3">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">เลขที่:</span>
                <span class="font-semibold"><?= h($sale['receipt_number']) ?></span>
            </div>
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">วันที่:</span>
                <span><?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?> น.</span>
            </div>
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">พนักงาน:</span>
                <span><?= h($sale['username']) ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">ชำระด้วย:</span>
                <span class="font-semibold">
                    <?php
                    $paymentText = match ($sale['payment_method']) {
                        'cash' => '💵 เงินสด',
                        'qr' => '📱 QR Code',
                        'credit' => '💳 บัตร',
                        default => $sale['payment_method']
                    };
                    echo $paymentText;
                    ?>
                </span>
            </div>
        </div>

        <!-- Items -->
        <div class="mb-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-dashed border-gray-400">
                        <th class="text-left py-2">รายการ</th>
                        <th class="text-center py-2">จำนวน</th>
                        <th class="text-right py-2">ราคา</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sale['items'] as $item): ?>
                        <tr class="border-b border-dotted border-gray-300">
                            <td class="py-2">
                                <div class="font-semibold"><?= h($item['product_name']) ?></div>
                                <div class="text-xs text-gray-600">@<?= number_format($item['price'], 2) ?> ฿</div>
                            </td>
                            <td class="text-center"><?= h($item['quantity']) ?></td>
                            <td class="text-right font-semibold"><?= number_format($item['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Total -->
        <div class="border-t-2 border-gray-800 pt-3 mb-4">
            <div class="flex justify-between items-center text-xl font-bold">
                <span>ยอดรวมทั้งสิ้น:</span>
                <span><?= number_format($sale['total_amount'], 2) ?> ฿</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-600 mt-6 border-t border-dashed border-gray-400 pt-4">
            <p class="mb-2">*** ขอบคุณที่ใช้บริการ ***</p>
            <p class="mb-1">สงวนสิทธิ์การเปลี่ยน/คืนสินค้า ภายใน 7 วัน</p>
            <p>(พร้อมใบเสร็จและสภาพสินค้าสมบูรณ์)</p>
            <div class="mt-4">
                <p class="mb-2">สแกนเพื่อติดตามโปรโมชั่น</p>
                <div class="flex justify-center">
                    <!-- QR Code Placeholder (optional) -->
                    <div class="w-24 h-24 bg-gray-200 rounded border border-gray-300 flex items-center justify-center text-xs text-gray-500">
                        QR Code
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto print when page loads
        window.addEventListener('load', function() {
            // Wait a bit for rendering
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>

</html>