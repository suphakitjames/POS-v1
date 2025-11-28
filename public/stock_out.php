<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Middleware/AuthMiddleware.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/Product.php';
require_once '../src/Models/Transaction.php';

use App\Middleware\AuthMiddleware;
use App\Config\Database;
use App\Models\Product;
use App\Models\Transaction;

AuthMiddleware::check();

// Database Connection
$database = new Database();
$db = $database->connect();

// Init Models
$product = new Product($db);
$transaction = new Transaction($db);

// Handle POST Request (Stock Out)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $transaction->product_id = $_POST['product_id'];
        $transaction->user_id = $_SESSION['user_id'];
        $transaction->supplier_id = null; // Stock out usually doesn't involve supplier directly in this context
        $transaction->type = 'out';
        $transaction->quantity = (int)$_POST['quantity'];

        $reason = $_POST['reason'] ?? 'Usage';
        $user_note = $_POST['note'] ?? '';
        $transaction->note = "[$reason] " . $user_note;

        if ($transaction->quantity <= 0) {
            throw new Exception("จำนวนสินค้าต้องมากกว่า 0");
        }

        if ($transaction->create()) {
            echo json_encode(['success' => true, 'message' => 'บันทึกการเบิกสินค้าเรียบร้อยแล้ว']);
        } else {
            // Check if it failed due to insufficient stock (create method returns false)
            // Ideally Transaction model should throw specific exception or return error code.
            // For now, assuming generic error or insufficient stock.
            throw new Exception("บันทึกไม่สำเร็จ (อาจเกิดจากสินค้าในสต็อกไม่พอ)");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch Data for View
$products = $product->read();

$page_title = 'เบิกสินค้าออก';
require_once '../templates/layouts/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">เบิกสินค้าออก (Stock Out)</h1>
    <p class="mt-1 text-sm text-gray-600">บันทึกการเบิกจ่ายสินค้า, ตัดสต็อกขาย, หรือตัดของเสีย</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Section -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">ฟอร์มเบิกสินค้า</h2>

            <form id="stockOutForm" class="space-y-4">
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700">เลือกสินค้า <span class="text-red-500">*</span></label>
                    <select id="product_id" name="product_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm rounded-md">
                        <option value="">-- เลือกสินค้า --</option>
                        <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $row['id'] ?>" data-stock="<?= $row['stock_quantity'] ?>">
                                <?= htmlspecialchars($row['sku']) ?> - <?= htmlspecialchars($row['name']) ?> (คงเหลือ: <?= $row['stock_quantity'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500" id="stockDisplay">กรุณาเลือกสินค้าเพื่อดูยอดคงเหลือ</p>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">สาเหตุการเบิก <span class="text-red-500">*</span></label>
                    <select id="reason" name="reason" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm rounded-md">
                        <option value="Sale">ขายสินค้า (Sale)</option>
                        <option value="Usage">เบิกไปใช้ (Internal Usage)</option>
                        <option value="Damaged">สินค้าเสียหาย (Damaged)</option>
                        <option value="Lost">สินค้าสูญหาย (Lost)</option>
                        <option value="Expired">สินค้าหมดอายุ (Expired)</option>
                    </select>
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">จำนวนที่เบิก <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="quantity" min="1" required class="mt-1 focus:ring-orange-500 focus:border-orange-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="ระบุจำนวน">
                </div>

                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">หมายเหตุ</label>
                    <textarea name="note" id="note" rows="3" class="mt-1 focus:ring-orange-500 focus:border-orange-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="รายละเอียดเพิ่มเติม..."></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        บันทึกเบิกสินค้า
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent History Section -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden p-4">
            <h2 class="text-lg font-medium text-gray-900 mb-4">ประวัติการเบิกสินค้าล่าสุด</h2>
            <div class="overflow-x-auto">
                <table id="stockOutTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่/เวลา</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สินค้า</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สาเหตุ/หมายเหตุ</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้บันทึก</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        // Simple query for recent 'out' transactions
                        $query = "SELECT t.*, p.name as product_name, u.username 
                                  FROM transactions t 
                                  JOIN products p ON t.product_id = p.id 
                                  JOIN users u ON t.user_id = u.id 
                                  WHERE t.type = 'out' 
                                  ORDER BY t.created_at DESC LIMIT 100";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= format_date_thai($row['created_at']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['note']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">-<?= number_format($row['quantity']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['username']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#stockOutTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json"
            }
        });

        // Update stock display when product is selected
        $('#product_id').on('change', function() {
            var stock = $(this).find(':selected').data('stock');
            if (stock !== undefined) {
                $('#stockDisplay').text('คงเหลือปัจจุบัน: ' + stock + ' ชิ้น').removeClass('text-red-500').addClass('text-green-600');
                $('#quantity').attr('max', stock);
            } else {
                $('#stockDisplay').text('กรุณาเลือกสินค้าเพื่อดูยอดคงเหลือ').removeClass('text-green-600').addClass('text-gray-500');
                $('#quantity').removeAttr('max');
            }
        });

        $('#stockOutForm').on('submit', function(e) {
            e.preventDefault();

            var currentStock = $('#product_id').find(':selected').data('stock');
            var quantity = parseInt($('#quantity').val());

            if (quantity > currentStock) {
                alert('จำนวนที่เบิกเกินกว่าสินค้าคงเหลือในสต็อก!');
                return;
            }

            if (!confirm('ยืนยันการเบิกสินค้า?')) return;

            $.ajax({
                url: 'stock_out.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert('เกิดข้อผิดพลาด: ' + error);
                }
            });
        });
    });
</script>

<?php require_once '../templates/layouts/footer.php'; ?>