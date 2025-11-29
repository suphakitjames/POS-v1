<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/Product.php';
require_once '../src/Models/Supplier.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Config\Database;
use App\Models\Product;
use App\Models\Supplier;
use App\Middleware\AuthMiddleware;

// Check Authentication
AuthMiddleware::check();

// Database connection
$database = new Database();
$db = $database->connect();
$product = new Product($db);
$supplier = new Supplier($db);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        header('Content-Type: application/json');

        // Handle File Upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = uniqid('prod_', true) . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $image_path = $destination;
                }
            }
        }

        // Set Product Properties
        $product->sku = $_POST['sku'];
        $product->barcode = $_POST['barcode'] ?? '';
        $product->name = $_POST['name'];
        $product->description = $_POST['description'] ?? '';
        $product->category_id = $_POST['category_id'];
        $product->supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
        $product->cost_price = $_POST['cost_price'];
        $product->selling_price = $_POST['selling_price'];
        $product->stock_quantity = $_POST['stock_quantity'];
        $product->reorder_point = $_POST['reorder_point'];
        $product->expire_date = !empty($_POST['expire_date']) ? $_POST['expire_date'] : null;

        if ($action === 'create') {
            $product->image_path = $image_path;
            if ($product->create()) {
                echo json_encode(['success' => true, 'message' => 'เพิ่มสินค้าสำเร็จ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มสินค้า']);
            }
        } elseif ($action === 'update') {
            $product->id = $_POST['id'];

            if ($image_path) {
                $product->image_path = $image_path;
            } else {
                $product->image_path = $_POST['existing_image_path'] ?? null;
            }

            if ($product->update()) {
                echo json_encode(['success' => true, 'message' => 'อัปเดตสินค้าสำเร็จ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตสินค้า']);
            }
        }
        exit;
    }

    if ($action === 'delete') {
        header('Content-Type: application/json');
        $product->id = $_POST['id'];

        if ($product->delete()) {
            echo json_encode(['success' => true, 'message' => 'ลบสินค้าสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบสินค้า']);
        }
        exit;
    }

    if ($action === 'get') {
        header('Content-Type: application/json');
        $product->id = $_POST['id'];
        $data = $product->read_single();
        echo json_encode($data);
        exit;
    }
}

// Get all products, categories, and suppliers
$products = $product->read()->fetchAll(PDO::FETCH_ASSOC);
$categories = $product->getCategories();
$suppliers = $supplier->read()->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'จัดการสินค้า';
require_once '../templates/layouts/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">รายการสินค้า</h1>
            <p class="mt-1 text-sm text-gray-600">จัดการสินค้าทั้งหมดในระบบ</p>
        </div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <button type="button" onclick="openAddModal()" class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all shadow-sm">
                <svg class="-ml-0.5 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                เพิ่มสินค้าใหม่
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filters Card -->
<div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <div class="flex items-center gap-4">
        <label class="text-sm font-medium text-gray-700">กรองตามหมวดหมู่:</label>
        <select id="categoryFilter" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">ทั้งหมด</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= h($cat['name']) ?>"><?= h($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Products Table Card -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="productsTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">สินค้า</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">หมวดหมู่</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">SKU / Barcode</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">ราคาขาย</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">คงเหลือ</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($products as $p): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-12 w-12 flex-shrink-0">
                                    <?php if ($p['image_path']): ?>
                                        <img class="h-12 w-12 rounded-lg object-cover" src="<?= h($p['image_path']) ?>" alt="">
                                    <?php else: ?>
                                        <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= h($p['name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= mb_strimwidth(h($p['description']), 0, 40, '...') ?></div>
                                    <?php if (!empty($p['supplier_name'])): ?>
                                        <div class="text-xs text-blue-600 mt-1">
                                            <span class="font-semibold">Supplier:</span> <?= h($p['supplier_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?= h($p['category_name']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-medium"><?= h($p['sku']) ?></div>
                            <div class="text-gray-500 text-xs"><?= h($p['barcode']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                            <?= number_format($p['selling_price'], 2) ?> ฿
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php
                            $stock_class = 'bg-green-100 text-green-800';
                            if ($p['stock_quantity'] <= 0) {
                                $stock_class = 'bg-red-100 text-red-800';
                            } elseif ($p['stock_quantity'] <= $p['reorder_point']) {
                                $stock_class = 'bg-yellow-100 text-yellow-800';
                            }
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $stock_class ?>">
                                <?= number_format($p['stock_quantity']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openPrintModal(<?= $p['id'] ?>)" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="พิมพ์บาร์โค้ด">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </button>
                                <button onclick="openEditModal(<?= $p['id'] ?>)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="แก้ไข">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="deleteProduct(<?= $p['id'] ?>)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="ลบ">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl z-10">
            <h3 id="modalTitle" class="text-xl font-bold text-white">เพิ่มสินค้าใหม่</h3>
        </div>
        <form id="productForm" class="p-6" enctype="multipart/form-data">
            <input type="hidden" id="productId" name="id">
            <input type="hidden" id="formAction" name="action">
            <input type="hidden" id="existingImagePath" name="existing_image_path">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Image Upload Section -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">รูปภาพสินค้า</label>
                    <div class="flex items-center gap-4">
                        <div id="imagePreviewContainer" class="w-24 h-24 rounded-lg bg-gray-100 border border-gray-300 flex items-center justify-center overflow-hidden relative hidden">
                            <img id="imagePreview" src="" alt="Preview" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="image" id="imageInput" accept="image/*" class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100
                            " />
                            <p class="mt-1 text-xs text-gray-500">รองรับไฟล์ JPG, PNG, GIF (ขนาดแนะนำ 500x500px)</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">SKU <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" name="sku" id="sku" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                        <button type="button" onclick="generateSKU()" class="px-3 py-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm whitespace-nowrap">
                            Auto Gen
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Barcode</label>
                    <div class="flex gap-2">
                        <input type="text" name="barcode" id="barcode" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                        <button type="button" onclick="generateBarcode()" class="px-3 py-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm whitespace-nowrap">
                            Auto Gen
                        </button>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อสินค้า <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">รายละเอียด</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">หมวดหมู่ <span class="text-red-500">*</span></label>
                    <select name="category_id" id="category_id" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                        <option value="">เลือกหมวดหมู่</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ผู้จัดจำหน่าย</label>
                    <select name="supplier_id" id="supplier_id" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                        <option value="">เลือกผู้จัดจำหน่าย</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= h($sup['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">วันหมดอายุ</label>
                    <input type="date" name="expire_date" id="expire_date" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ราคาทุน <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="cost_price" id="cost_price" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ราคาขาย <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="selling_price" id="selling_price" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนคงเหลือ <span class="text-red-500">*</span></label>
                    <input type="number" name="stock_quantity" id="stock_quantity" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">จุดสั่งซื้อ <span class="text-red-500">*</span></label>
                    <input type="number" name="reorder_point" id="reorder_point" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                </div>
            </div>

            <div class="mt-6 flex gap-3 justify-end border-t pt-6">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-sm">
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Print Barcode Modal -->
<div id="printModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full">
        <div class="bg-gray-800 px-6 py-4 rounded-t-xl">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                พิมพ์บาร์โค้ด
            </h3>
        </div>
        <div class="p-6">
            <input type="hidden" id="printProductId">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนดวงที่ต้องการพิมพ์</label>
                <input type="number" id="printQty" value="1" min="1" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-center text-lg font-bold">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closePrintModal()" class="flex-1 px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors">
                    ยกเลิก
                </button>
                <button type="button" onclick="confirmPrint()" class="flex-1 px-5 py-2.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-sm">
                    ยืนยัน
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let table;

    $(document).ready(function() {
        table = $('#productsTable').DataTable({
            "language": {
                "sProcessing": "กำลังดำเนินการ...",
                "sLengthMenu": "แสดง _MENU_ รายการ",
                "sZeroRecords": "ไม่พบข้อมูล",
                "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "sInfoEmpty": "แสดง 0 ถึง 0 จาก 0 รายการ",
                "sInfoFiltered": "(กรองข้อมูล _MAX_ ทุกรายการ)",
                "sInfoPostFix": "",
                "sSearch": "ค้นหา:",
                "sUrl": "",
                "oPaginate": {
                    "sFirst": "หน้าแรก",
                    "sPrevious": "ก่อนหน้า",
                    "sNext": "ถัดไป",
                    "sLast": "หน้าสุดท้าย"
                }
            },
            "pageLength": 10,
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, "ทั้งหมด"]
            ],
            "order": [
                [0, "asc"]
            ]
        });

        // Check for search parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('search');
        if (searchTerm) {
            table.search(searchTerm).draw();
        }

        $('#categoryFilter').on('change', function() {
            table.column(1).search(this.value).draw();
        });

        // Image Preview Handler
        $('#imageInput').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').attr('src', e.target.result);
                    $('#imagePreviewContainer').removeClass('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    });

    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'เพิ่มสินค้าใหม่';
        document.getElementById('formAction').value = 'create';
        document.getElementById('productForm').reset();
        document.getElementById('productId').value = '';
        document.getElementById('existingImagePath').value = '';

        // Reset Image Preview
        $('#imagePreview').attr('src', '');
        $('#imagePreviewContainer').addClass('hidden');

        document.getElementById('productModal').classList.remove('hidden');
        document.getElementById('productModal').classList.add('flex');
    }

    function openEditModal(id) {
        document.getElementById('modalTitle').textContent = 'แก้ไขสินค้า';
        document.getElementById('formAction').value = 'update';

        $.ajax({
            url: 'products.php',
            method: 'POST',
            data: {
                action: 'get',
                id: id
            },
            success: function(data) {
                document.getElementById('productId').value = data.id;
                document.getElementById('sku').value = data.sku;
                document.getElementById('barcode').value = data.barcode || '';
                document.getElementById('name').value = data.name;
                document.getElementById('description').value = data.description || '';
                document.getElementById('category_id').value = data.category_id;
                document.getElementById('supplier_id').value = data.supplier_id || '';
                document.getElementById('cost_price').value = data.cost_price;
                document.getElementById('selling_price').value = data.selling_price;
                document.getElementById('stock_quantity').value = data.stock_quantity;
                document.getElementById('reorder_point').value = data.reorder_point;
                document.getElementById('expire_date').value = data.expire_date || '';
                document.getElementById('existingImagePath').value = data.image_path || '';

                // Show existing image
                if (data.image_path) {
                    $('#imagePreview').attr('src', data.image_path);
                    $('#imagePreviewContainer').removeClass('hidden');
                } else {
                    $('#imagePreview').attr('src', '');
                    $('#imagePreviewContainer').addClass('hidden');
                }

                document.getElementById('productModal').classList.remove('hidden');
                document.getElementById('productModal').classList.add('flex');
            },
            error: function(xhr, status, error) {
                alert('เกิดข้อผิดพลาด: ' + error);
            }
        });
    }

    function closeModal() {
        document.getElementById('productModal').classList.add('hidden');
        document.getElementById('productModal').classList.remove('flex');
    }

    function openPrintModal(id) {
        document.getElementById('printProductId').value = id;
        document.getElementById('printQty').value = 1;
        document.getElementById('printModal').classList.remove('hidden');
        document.getElementById('printModal').classList.add('flex');
        // Focus on quantity input
        setTimeout(() => document.getElementById('printQty').focus(), 100);
    }

    function closePrintModal() {
        document.getElementById('printModal').classList.add('hidden');
        document.getElementById('printModal').classList.remove('flex');
    }

    function confirmPrint() {
        const id = document.getElementById('printProductId').value;
        const qty = document.getElementById('printQty').value;

        if (qty < 1) {
            alert('กรุณาระบุจำนวนอย่างน้อย 1 ดวง');
            return;
        }

        // Open print page in new tab
        window.open(`barcode_print.php?product_id=${id}&qty=${qty}`, '_blank');
        closePrintModal();
    }

    function deleteProduct(id) {
        if (confirm('คุณต้องการลบสินค้านี้หรือไม่?')) {

            $.ajax({
                url: 'products.php',
                method: 'POST',
                data: {
                    action: 'delete',
                    id: id
                },
                dataType: 'json',
                success: function(response) {

                    alert(response.message);
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    alert('เกิดข้อผิดพลาด: ' + error + '\nResponse: ' + xhr.responseText);
                }
            });
        }
    }

    $('#productForm').on('submit', function(e) {
        e.preventDefault();

        // Use FormData for file upload
        var formData = new FormData(this);

        $.ajax({
            url: 'products.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false, // Important for FormData
            contentType: false, // Important for FormData
            success: function(response) {
                alert(response.message);
                if (response.success) {
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                alert('เกิดข้อผิดพลาดในการบันทึก: ' + error);
            }
        });
    });

    function generateSKU() {
        // Generate SKU: PROD- + Random 6 digits
        const randomNum = Math.floor(100000 + Math.random() * 900000);
        document.getElementById('sku').value = 'PROD-' + randomNum;
    }

    function generateBarcode() {
        // Generate Barcode: Random 13 digits (EAN-13 style but just random for now)
        let result = '';
        for (let i = 0; i < 12; i++) {
            result += Math.floor(Math.random() * 10);
        }
        // Calculate simple check digit (not real EAN-13 algorithm but enough for internal use)
        // Or just use 13 random digits
        result += Math.floor(Math.random() * 10);
        document.getElementById('barcode').value = result;
    }
</script>

<?php require_once '../templates/layouts/footer.php'; ?>