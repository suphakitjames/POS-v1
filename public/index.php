<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../src/Helpers/functions.php';
require_once '../src/Middleware/AuthMiddleware.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/Product.php';
require_once '../src/Models/Transaction.php';
require_once '../src/Helpers/NotificationHelper.php';

use App\Middleware\AuthMiddleware;
use App\Config\Database;
use App\Models\Product;
use App\Models\Transaction;
use App\Helpers\NotificationHelper;

// Check Authentication
AuthMiddleware::check();

// Get Database Connection
$database = new Database();
$db = $database->connect();

// Instantiate Models
$productModel = new Product($db);
$transactionModel = new Transaction($db);

// Auto-generate notifications for all users
$notificationHelper = new NotificationHelper();
$notificationHelper->runAllChecks();

// Query: Total Products
$stmt = $db->query("SELECT COUNT(*) as total FROM products");
$total_products = $stmt->fetch()['total'];

// Query: Total Inventory Value
$stmt = $db->query("SELECT SUM(stock_quantity * cost_price) as total_value FROM products");
$total_value = $stmt->fetch()['total_value'] ?? 0;

// Query: Low Stock Items
$stmt = $db->query("SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity <= reorder_point");
$low_stock_count = $stmt->fetch()['low_stock'];

// Query: Out of Stock Items
$stmt = $db->query("SELECT COUNT(*) as out_of_stock FROM products WHERE stock_quantity = 0");
$out_of_stock_count = $stmt->fetch()['out_of_stock'];

// Fetch Data for Widgets
$best_sellers = $transactionModel->getBestSellers(5);
$expiring_soon = $productModel->getExpiringSoon(30);
$recent_activities = $transactionModel->getRecentActivity(10);
$daily_stats = $transactionModel->getDailyStats(30);

// Prepare Chart Data
$chart_labels = [];
$chart_data_in = [];
$chart_data_out = [];

foreach ($daily_stats as $stat) {
    $chart_labels[] = date('d/m', strtotime($stat['date']));
    $chart_data_in[] = $stat['total_in'];
    $chart_data_out[] = $stat['total_out'];
}

$page_title = 'ภาพรวมระบบ';
require_once '../templates/layouts/header.php';
?>

<div class="sm:flex sm:items-center justify-between mb-8">

    <!-- Quick Actions -->
    <div class="mt-4 sm:mt-0 flex gap-3">
        <a href="stock_in.php" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            รับสินค้าเข้า
        </a>
        <a href="stock_out.php" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
            </svg>
            เบิกสินค้าออก
        </a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="users.php" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                <svg class="w-5 h-5 mr-2 -ml-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                เพิ่มผู้ใช้งาน
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <!-- Card 1: Total Products -->
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-100">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500/10 rounded-xl p-3">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-slate-500 truncate">สินค้าทั้งหมด</dt>
                        <dd class="text-2xl font-bold text-slate-800"><?= number_format($total_products) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Inventory Value -->
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-100">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500/10 rounded-xl p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-slate-500 truncate">มูลค่าสต็อกรวม</dt>
                        <dd class="text-2xl font-bold text-slate-800"><?= format_currency($total_value) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Low Stock -->
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-100">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500/10 rounded-xl p-3">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-slate-500 truncate">สินค้าใกล้หมด</dt>
                        <dd class="text-2xl font-bold text-slate-800"><?= number_format($low_stock_count) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Out of Stock -->
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-100">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-500/10 rounded-xl p-3">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-slate-500 truncate">สินค้าหมด</dt>
                        <dd class="text-2xl font-bold text-slate-800"><?= number_format($out_of_stock_count) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="bg-white shadow-sm rounded-2xl border border-slate-100 p-6 mb-8">
    <h3 class="text-lg font-bold text-slate-800 mb-4">แนวโน้มสต็อก (30 วันล่าสุด)</h3>
    <div class="relative h-80 w-full">
        <canvas id="stockChart"></canvas>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white shadow-sm rounded-2xl border border-slate-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
        <h3 class="text-lg font-bold text-slate-800">กิจกรรมล่าสุด</h3>
        <div class="flex space-x-2">
            <button onclick="filterActivity('all')" class="filter-btn px-3 py-1.5 text-sm font-medium rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors active" data-type="all">ทั้งหมด</button>
            <button onclick="filterActivity('in')" class="filter-btn px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-50 transition-colors" data-type="in">รับเข้า</button>
            <button onclick="filterActivity('out')" class="filter-btn px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-50 transition-colors" data-type="out">เบิกออก</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">เวลา</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">ผู้ดำเนินการ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">รายการ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">สินค้า</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">จำนวน</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white" id="activityTableBody">
                <?php foreach ($recent_activities as $activity): ?>
                    <tr class="activity-row hover:bg-slate-50 transition-colors" data-type="<?= $activity['type'] ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 mr-3">
                                    <?= strtoupper(substr($activity['username'], 0, 1)) ?>
                                </div>
                                <span class="text-sm font-medium text-slate-900"><?= h($activity['username']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($activity['type'] === 'in'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                    รับเข้า
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                    เบิกออก
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900"><?= h($activity['product_name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold <?= $activity['type'] === 'in' ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $activity['type'] === 'in' ? '+' : '-' ?><?= number_format($activity['quantity']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Chart.js
    const ctx = document.getElementById('stockChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'รับเข้า (In)',
                data: <?= json_encode($chart_data_in) ?>,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }, {
                label: 'เบิกออก (Out)',
                data: <?= json_encode($chart_data_out) ?>,
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [2, 4],
                        color: '#f1f5f9'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    });

    // Filter Logic
    function filterActivity(type) {
        const rows = document.querySelectorAll('.activity-row');
        const buttons = document.querySelectorAll('.filter-btn');

        // Update buttons
        buttons.forEach(btn => {
            if (btn.dataset.type === type) {
                btn.classList.add('bg-blue-50', 'text-blue-600');
                btn.classList.remove('text-slate-600', 'hover:bg-slate-50');
            } else {
                btn.classList.remove('bg-blue-50', 'text-blue-600');
                btn.classList.add('text-slate-600', 'hover:bg-slate-50');
            }
        });

        // Filter rows
        rows.forEach(row => {
            if (type === 'all' || row.dataset.type === type) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

<?php require_once '../templates/layouts/footer.php'; ?>