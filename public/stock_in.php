<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Middleware/AuthMiddleware.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/Product.php';
require_once '../src/Models/Transaction.php';
require_once '../src/Models/Supplier.php';

use App\Middleware\AuthMiddleware;
use App\Config\Database;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Supplier;

AuthMiddleware::check();

// Database Connection
$database = new Database();
$db = $database->connect();

// Init Models
$product = new Product($db);
$transaction = new Transaction($db);
$supplier = new Supplier($db);

// Handle POST Request (Stock In)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $transaction->product_id = $_POST['product_id'];
        $transaction->user_id = $_SESSION['user_id'];
        $transaction->supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
        $transaction->type = 'in';
        $transaction->quantity = (int)$_POST['quantity'];
        $transaction->note = $_POST['note'] ?? '';

        if ($transaction->quantity <= 0) {
            throw new Exception("จำนวนสินค้าต้องมากกว่า 0");
        }

        if ($transaction->create()) {
            echo json_encode(['success' => true, 'message' => 'บันทึกการรับสินค้าเรียบร้อยแล้ว']);
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch Data for View
$products = $product->read();
$suppliers = $supplier->read();

$page_title = 'รับสินค้าเข้า';
require_once '../templates/layouts/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">รับสินค้าเข้า (Stock In)</h1>
    <p class="mt-1 text-sm text-gray-600">บันทึกการรับสินค้าเข้าสต็อกและเพิ่มจำนวนสินค้า</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Section -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">ฟอร์มรับสินค้า</h2>

            <form id="stockInForm" class="space-y-4">
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700">เลือกสินค้า <span class="text-red-500">*</span></label>
                    <select id="product_id" name="product_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">-- เลือกสินค้า --</option>
                        <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $row['id'] ?>">
                                <?= htmlspecialchars($row['sku']) ?> - <?= htmlspecialchars($row['name']) ?> (คงเหลือ: <?= $row['stock_quantity'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">ผู้จัดจำหน่าย (Supplier)</label>
                    <select id="supplier_id" name="supplier_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">-- ไม่ระบุ --</option>
                        <?php while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">จำนวนที่รับ <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="quantity" min="1" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="ระบุจำนวน">
                </div>

                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">หมายเหตุ</label>
                    <textarea name="note" id="note" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="เช่น เลขที่ใบส่งของ, ล็อตการผลิต"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        บันทึกรับสินค้า
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent History Section -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden p-4">
            <h2 class="text-lg font-medium text-gray-900 mb-4">ประวัติการรับสินค้าล่าสุด</h2>
            <div class="overflow-x-auto">
                <table id="stockInTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่/เวลา</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สินค้า</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้บันทึก</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $query = "SELECT t.*, p.name as product_name, u.username 
                                  FROM transactions t 
                                  JOIN products p ON t.product_id = p.id 
                                  JOIN users u ON t.user_id = u.id 
                                  WHERE t.type = 'in' 
                                  ORDER BY t.created_at DESC LIMIT 100";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= format_date_thai($row['created_at']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">+<?= number_format($row['quantity']) ?></td>
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
        $('#stockInTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json"
            }
        });

        $('#stockInForm').on('submit', function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'ยืนยันการรับสินค้า?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'stock_in.php',
                        method: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: response.message,
                                    confirmButtonText: 'ตกลง'
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: response.message,
                                    confirmButtonText: 'ตกลง'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: error,
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    });
                }
            });
        });
    });
</script>

<?php require_once '../templates/layouts/footer.php'; ?>