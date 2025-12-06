<?php
require_once '../src/Config/Database.php';
require_once '../src/Helpers/functions.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Config\Database;
use App\Middleware\AuthMiddleware;

// Check authentication
AuthMiddleware::check();

// Get database connection
$db = new Database();
$conn = $db->connect();

// Get all users for filter
$stmt = $conn->query("SELECT id, username, first_name, last_name FROM users ORDER BY first_name ASC, username ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "ประวัติการขาย";
require_once '../templates/layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8 border border-slate-200">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                ประวัติการขาย
            </h2>
        </div>

        <!-- Filters -->
        <div class="p-6 bg-slate-50 border-b border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">วันที่เริ่มต้น</label>
                    <input type="date" id="dateFrom" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">วันที่สิ้นสุด</label>
                    <input type="date" id="dateTo" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">วิธีชำระเงิน</label>
                    <select id="paymentMethod" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">ทั้งหมด</option>
                        <option value="cash">เงินสด</option>
                        <option value="qr">QR Code</option>
                        <option value="credit">บัตร</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">พนักงาน</label>
                    <select id="userId" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">ทั้งหมด</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button onclick="loadSales()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold">
                    ค้นหา
                </button>
                <button onclick="resetFilters()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-all font-semibold">
                    รีเซ็ต
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="p-6 bg-white border-b border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <div class="text-sm text-blue-600 font-medium">ยอดขายทั้งหมด</div>
                    <div class="text-2xl font-bold text-blue-700 mt-1" id="totalSales">0</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <div class="text-sm text-green-600 font-medium">รายได้รวม</div>
                    <div class="text-2xl font-bold text-green-700 mt-1" id="totalRevenue">0.00 ฿</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                    <div class="text-sm text-purple-600 font-medium">ค่าเฉลี่ย/บิล</div>
                    <div class="text-2xl font-bold text-purple-700 mt-1" id="avgPerBill">0.00 ฿</div>
                </div>
                <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                    <div class="text-sm text-orange-600 font-medium">สินค้า/บิล</div>
                    <div class="text-2xl font-bold text-orange-700 mt-1" id="avgItems">0</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="p-6 overflow-x-auto">
            <table id="salesTable" class="w-full display responsive nowrap" style="width:100%">
                <thead class="bg-slate-100 border-b border-slate-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">เลขที่ใบเสร็จ</th>
                        <th class="text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">วันที่-เวลา</th>
                        <th class="text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">พนักงาน</th>
                        <th class="text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">รายการ</th>
                        <th class="text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">ยอดรวม</th>
                        <th class="text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">ชำระด้วย</th>
                        <th class="text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">การกระทำ</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody" class="bg-white divide-y divide-slate-200">
                    <!-- Data will be loaded by DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sale Detail Modal -->
<div id="saleDetailModal" class="fixed inset-0 bg-black bg-opacity-70 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold text-white">รายละเอียดการขาย</h3>
        </div>
        <div id="saleDetailContent" class="p-6">
            <!-- Content will be loaded here -->
        </div>
        <div class="p-6 border-t border-slate-200 flex gap-3">
            <button onclick="closeSaleDetailModal()" class="flex-1 px-5 py-3 bg-slate-200 text-slate-700 rounded-lg font-semibold hover:bg-slate-300 transition-all">
                ปิด
            </button>
            <button onclick="printReceipt()" class="flex-1 px-5 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-all">
                พิมพ์ใบเสร็จ
            </button>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    let currentSaleId = null;
    let table = null;

    $(document).ready(function() {
        loadSales();
    });

    function loadSales() {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const paymentMethod = document.getElementById('paymentMethod').value;
        const userId = document.getElementById('userId').value;

        const params = new URLSearchParams({
            action: 'list',
            page: 1,
            limit: 10000,
            date_from: dateFrom,
            date_to: dateTo,
            payment_method: paymentMethod,
            user_id: userId
        });

        if (table) {
            table.destroy();
        }

        $('#salesTableBody').html('<tr><td colspan="7" class="text-center py-4">กำลังโหลดข้อมูล...</td></tr>');

        fetch(`api/sales_history.php?${params}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    displaySales(result.data);
                    updateSummary(result.data);
                    initializeDataTable();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: result.message,
                        confirmButtonText: 'ตกลง'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถโหลดข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    function displaySales(sales) {
        const tbody = document.getElementById('salesTableBody');

        if (sales.length === 0) {
            tbody.innerHTML = '';
            return;
        }

        tbody.innerHTML = sales.map(sale => {
            const paymentIcon = {
                'cash': '💵',
                'qr': '📱',
                'credit': '💳'
            } [sale.payment_method] || '';

            const paymentText = {
                'cash': 'เงินสด',
                'qr': 'QR Code',
                'credit': 'บัตร'
            } [sale.payment_method] || sale.payment_method;

            const fullName = (sale.first_name || '') + ' ' + (sale.last_name || '');
            const displayName = fullName.trim() ? fullName.trim() : sale.username;

            return `
                <tr>
                    <td class="font-mono font-semibold text-blue-600">${sale.receipt_number}</td>
                    <td class="text-slate-600">${formatDateTime(sale.sale_date)}</td>
                    <td>${displayName}</td>
                    <td class="text-center"><span class="px-2 py-1 bg-slate-100 rounded-full text-sm">${sale.item_count} รายการ</span></td>
                    <td class="text-right font-bold text-green-600">${parseFloat(sale.total_amount).toFixed(2)} ฿</td>
                    <td>${paymentIcon} ${paymentText}</td>
                    <td class="text-center">
                        <div class="flex gap-2 justify-center">
                            <button onclick="viewSaleDetail(${sale.id})" class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-all text-sm font-semibold">
                                ดูรายละเอียด
                            </button>
                            <button onclick="printReceiptDirect(${sale.id})" class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-all text-sm font-semibold">
                                พิมพ์
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function initializeDataTable() {
        table = $('#salesTable').DataTable({
            responsive: true,
            dom: '<"flex justify-between items-center mb-4"Bf>rt<"flex justify-between items-center mt-4"ip>',
            buttons: [{
                extend: 'excelHtml5',
                text: '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> Export Excel',
                className: 'bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 font-semibold border-none shadow-sm',
                title: 'Sales_History_' + new Date().toISOString().split('T')[0],
                exportOptions: {
                    columns: [0, 1, 2, 4, 5]
                }
            }],
            language: {
                "lengthMenu": "แสดง _MENU_ รายการ",
                "zeroRecords": "ไม่พบข้อมูล",
                "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
                "infoEmpty": "ไม่มีข้อมูล",
                "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                "search": "ค้นหา:",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "หน้าสุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            },
            pageLength: 10,
            order: [
                [1, 'desc']
            ]
        });
    }

    function updateSummary(sales) {
        const totalSales = sales.length;
        const totalRevenue = sales.reduce((sum, sale) => sum + parseFloat(sale.total_amount), 0);
        const totalItems = sales.reduce((sum, sale) => sum + parseInt(sale.item_count), 0);
        const avgPerBill = totalSales > 0 ? totalRevenue / totalSales : 0;
        const avgItems = totalSales > 0 ? totalItems / totalSales : 0;

        document.getElementById('totalSales').textContent = totalSales;
        document.getElementById('totalRevenue').textContent = totalRevenue.toFixed(2) + ' ฿';
        document.getElementById('avgPerBill').textContent = avgPerBill.toFixed(2) + ' ฿';
        document.getElementById('avgItems').textContent = avgItems.toFixed(1);
    }

    async function viewSaleDetail(saleId) {
        currentSaleId = saleId;

        try {
            const response = await fetch(`api/sales_history.php?action=detail&id=${saleId}`);
            const result = await response.json();

            if (result.success) {
                displaySaleDetail(result.data);
                document.getElementById('saleDetailModal').classList.remove('hidden');
                document.getElementById('saleDetailModal').classList.add('flex');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: result.message,
                    confirmButtonText: 'ตกลง'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่สามารถโหลดข้อมูลได้',
                confirmButtonText: 'ตกลง'
            });
        }
    }

    function displaySaleDetail(sale) {
        const paymentText = {
            'cash': '💵 เงินสด',
            'qr': '📱 QR Code',
            'credit': '💳 บัตร'
        } [sale.payment_method] || sale.payment_method;

        const fullName = (sale.first_name || '') + ' ' + (sale.last_name || '');
        const displayName = fullName.trim() ? fullName.trim() : sale.username;

        const content = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-slate-600">เลขที่ใบเสร็จ:</span><span class="font-mono font-bold ml-2">${sale.receipt_number}</span></div>
                    <div><span class="text-slate-600">วันที่-เวลา:</span><span class="font-semibold ml-2">${formatDateTime(sale.sale_date)}</span></div>
                    <div><span class="text-slate-600">พนักงาน:</span><span class="font-semibold ml-2">${displayName}</span></div>
                    <div><span class="text-slate-600">วิธีชำระเงิน:</span><span class="font-semibold ml-2">${paymentText}</span></div>
                </div>
                <div class="border-t border-slate-200 pt-4">
                    <h4 class="font-semibold text-slate-800 mb-3">รายการสินค้า</h4>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left">สินค้า</th><th class="px-3 py-2 text-center">จำนวน</th><th class="px-3 py-2 text-right">ราคา/หน่วย</th><th class="px-3 py-2 text-right">รวม</th></tr></thead>
                        <tbody class="divide-y divide-slate-200">
                            ${sale.items.map(item => `
                                <tr>
                                    <td class="px-3 py-2"><div class="font-semibold">${item.product_name}</div><div class="text-xs text-slate-500">${item.sku}</div></td>
                                    <td class="px-3 py-2 text-center font-semibold">${item.quantity}</td>
                                    <td class="px-3 py-2 text-right">${parseFloat(item.price).toFixed(2)} ฿</td>
                                    <td class="px-3 py-2 text-right font-bold text-blue-600">${parseFloat(item.subtotal).toFixed(2)} ฿</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="border-t-2 border-slate-800 pt-3">
                    <div class="flex justify-between items-center text-xl font-bold">
                        <span>ยอดรวมทั้งสิ้น:</span>
                        <span class="text-green-600">${parseFloat(sale.total_amount).toFixed(2)} ฿</span>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('saleDetailContent').innerHTML = content;
    }

    function closeSaleDetailModal() {
        document.getElementById('saleDetailModal').classList.add('hidden');
        document.getElementById('saleDetailModal').classList.remove('flex');
    }

    function printReceipt() {
        if (currentSaleId) {
            window.open(`receipt.php?id=${currentSaleId}`, '_blank', 'width=800,height=600');
        }
    }

    function printReceiptDirect(saleId) {
        window.open(`receipt.php?id=${saleId}`, '_blank', 'width=800,height=600');
    }

    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('th-TH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function resetFilters() {
        document.getElementById('dateFrom').value = '<?= date('Y-m-d', strtotime('-7 days')) ?>';
        document.getElementById('dateTo').value = '<?= date('Y-m-d') ?>';
        document.getElementById('paymentMethod').value = 'all';
        document.getElementById('userId').value = 'all';
        loadSales();
    }
</script>

<?php require_once '../templates/layouts/footer.php'; ?>