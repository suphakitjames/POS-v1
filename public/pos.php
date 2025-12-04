<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Controllers/ShiftController.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Controllers\ShiftController;
use App\Middleware\AuthMiddleware;

// ตรวจสอบสิทธิ์การเข้าใช้งาน
AuthMiddleware::check();

$shiftController = new ShiftController();
$openShift = $shiftController->getOpenShift($_SESSION['user_id']);

$page_title = 'POS - ขายหน้าร้าน';
require_once '../templates/layouts/header.php';
?>

<!-- Main Container -->
<?php if (!$openShift): ?>
    <!-- Open Shift Modal (Always visible if no shift) -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-5 rounded-t-2xl">
                <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    เปิดกะการทำงาน
                </h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">กรุณาระบุจำนวนเงินทอนเริ่มต้นในลิ้นชักก่อนเริ่มทำงาน</p>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">เงินทอนเริ่มต้น (บาท)</label>
                    <input type="number" step="0.01" id="startCashInput" class="w-full px-4 py-3 text-lg font-bold text-center rounded-lg border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all" value="1000.00" placeholder="0.00">
                </div>
                <button onclick="openShift()" class="w-full py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition-all shadow-lg text-lg">
                    เริ่มทำงาน
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- POS Main Interface -->
    <div class="flex flex-col h-screen bg-gray-50">
        <!-- Header Bar -->
        <div class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">POS - ขายหน้าร้าน</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        พนักงาน: <span class="font-semibold"><?= h($_SESSION['username']) ?></span> |
                        กะเริ่ม: <span class="font-semibold"><?= date('H:i น.', strtotime($openShift['start_time'])) ?></span> |
                        เวลาปัจจุบัน: <span id="realTimeClock" class="font-bold text-blue-600"></span>
                    </p>
                    <script>
                        function updateClock() {
                            const now = new Date();
                            const timeString = now.toLocaleTimeString('th-TH', {
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit'
                            });
                            document.getElementById('realTimeClock').textContent = timeString + ' น.';
                        }
                        setInterval(updateClock, 1000);
                        setTimeout(updateClock, 0);
                    </script>
                </div>
                <div class="flex gap-2">
                    <!-- <button onclick="clearCart()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all font-semibold">
                        ยกเลิกรายการ
                    </button> -->
                    <button onclick="showCloseShiftModal()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all font-semibold">
                        ปิดกะ
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Left: Product Search & Grid -->
            <div class="flex-1 flex flex-col p-6 overflow-auto">
                <!-- Search Box -->
                <div class="mb-4">
                    <input type="text" id="productSearch" placeholder="ค้นหาสินค้า (ชื่อ, SKU, Barcode)..." class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-lg" autofocus>
                </div>

                <!-- Product Grid -->
                <div id="productGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 overflow-auto">
                    <p class="col-span-full text-center text-gray-500 py-8">ค้นหาสินค้าหรือยิงบาร์โค้ดเพื่อเพิ่มลงตะกร้า</p>
                </div>
            </div>

            <!-- Right: Cart & Checkout -->
            <div class="w-full md:w-[450px] lg:w-[500px] bg-white border-l border-gray-200 flex flex-col">
                <!-- Cart Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 text-white">
                    <h2 class="text-xl font-bold">รายการสินค้า</h2>
                    <p class="text-sm opacity-90 mt-1">จำนวน: <span id="cartCount">0</span> รายการ</p>
                </div>

                <!-- Cart Items -->
                <div id="cartItems" class="flex-1 overflow-auto p-4 space-y-2">
                    <div class="text-center text-gray-400 py-12">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p class="font-medium">ตะกร้าว่างเปล่า</p>
                    </div>
                </div>

                <!-- Total & Checkout -->
                <div class="border-t border-gray-200 p-6 space-y-4 bg-gray-50">
                    <div class="flex justify-between items-center text-2xl font-bold">
                        <span class="text-gray-700">ยอดรวม:</span>
                        <span class="text-blue-600" id="totalAmount">0.00 ฿</span>
                    </div>
                    <button id="checkoutBtn" onclick="showPaymentModal()" disabled class="w-full py-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-all shadow-lg text-lg disabled:bg-gray-300 disabled:cursor-not-allowed">
                        ชำระเงิน
                    </button>
                    <button onclick="clearCart()" class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all font-semibold">
                        ยกเลิกรายการ
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-70 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold text-white">ชำระเงิน</h3>
        </div>
        <div class="p-6 space-y-4">
            <!-- Payment Method Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">เลือกวิธีชำระเงิน</label>
                <div class="grid grid-cols-3 gap-2">
                    <button onclick="selectPaymentMethod('cash')" class="payment-method-btn active px-4 py-3 border-2 border-blue-500 bg-blue-50 text-blue-700 rounded-lg font-semibold hover:bg-blue-100 transition-all" data-method="cash">
                        💵 เงินสด
                    </button>
                    <button onclick="selectPaymentMethod('qr')" class="payment-method-btn px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-all" data-method="qr">
                        📱 QR Code
                    </button>
                    <button onclick="selectPaymentMethod('credit')" class="payment-method-btn px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-all" data-method="credit">
                        💳 บัตร
                    </button>
                </div>
            </div>

            <!-- Total -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex justify-between items-center text-xl font-bold">
                    <span class="text-gray-700">ยอดรวม:</span>
                    <span class="text-blue-600" id="paymentTotal">0.00 ฿</span>
                </div>
            </div>

            <!-- Cash Payment Fields -->
            <div id="cashFields">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">รับเงินมา</label>
                    <input type="number" step="0.01" id="receivedAmount" class="w-full px-4 py-3 text-lg font-bold text-center rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all">
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <span class="text-gray-700">เงินทอน:</span>
                        <span class="text-green-600" id="changeAmount">0.00 ฿</span>
                    </div>
                </div>
            </div>

            <!-- QR Code Display -->
            <div id="qrFields" class="hidden text-center">
                <div class="bg-white p-6 rounded-lg border-2 border-dashed border-gray-300">
                    <div id="qrcode" class="flex justify-center items-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                        <p class="text-gray-400">QR Code จะปรากฏที่นี่</p>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">สแกน QR Code เพื่อชำระเงิน</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4 border-t">
                <button id="cancelPaymentBtn" class="flex-1 px-5 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all">
                    ยกเลิก
                </button>
                <button id="confirmPaymentBtn" class="flex-1 px-5 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-all shadow-lg">
                    ยืนยันการชำระเงิน
                </button>
                <button onclick="clearCart()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all font-semibold">
                    ยกเลิกรายการ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Close Shift Modal -->
<div id="closeShiftModal" class="fixed inset-0 bg-black bg-opacity-70 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold text-white">ปิดกะการทำงาน</h3>
        </div>
        <div class="p-6">
            <div id="shiftSummary" class="mb-6 space-y-2 text-sm">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">เงินทอนเริ่มต้น:</span>
                    <span class="font-semibold" id="summaryStartCash">0.00 ฿</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">ยอดขายเงินสด:</span>
                    <span class="font-semibold" id="summaryCashSales">0.00 ฿</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">ยอดขาย QR:</span>
                    <span class="font-semibold" id="summaryQRSales">0.00 ฿</span>
                </div>
                <div class="flex justify-between py-2 border-b bg-blue-50 px-2 rounded">
                    <span class="text-gray-700 font-semibold">เงินสดที่ควรมี:</span>
                    <span class="font-bold text-blue-600" id="summaryExpectedCash">0.00 ฿</span>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">เงินสดที่นับได้จริง (บาท)</label>
                <input type="number" step="0.01" id="endCashInput" class="w-full px-4 py-3 text-lg font-bold text-center rounded-lg border-2 border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all" placeholder="0.00">
            </div>

            <div class="flex gap-3">
                <button id="cancelCloseShiftBtn" class="flex-1 px-5 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all">
                    ยกเลิก
                </button>
                <button id="confirmCloseShiftBtn" class="flex-1 px-5 py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition-all shadow-lg">
                    ยืนยันปิดกะ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include QRCode.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script src="js/pos.js?v=<?= time() ?>"></script>

<?php require_once '../templates/layouts/footer.php'; ?>