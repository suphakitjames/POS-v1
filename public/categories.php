<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/Category.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Config\Database;
use App\Models\Category;
use App\Middleware\AuthMiddleware;

// Check Authentication - Admin Only
AuthMiddleware::checkAdmin();

// Database connection
$database = new Database();
$db = $database->connect();

// Init Model
$category = new Category($db);

// Handle Actions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $category->name = $_POST['name'];
            $category->description = $_POST['description'] ?? '';

            if ($category->create()) {
                echo json_encode(['success' => true, 'message' => 'เพิ่มหมวดหมู่สำเร็จ']);
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่");
            }
        } elseif ($action === 'update') {
            $category->id = $_POST['id'];
            $category->name = $_POST['name'];
            $category->description = $_POST['description'] ?? '';

            if ($category->update()) {
                echo json_encode(['success' => true, 'message' => 'แก้ไขหมวดหมู่สำเร็จ']);
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการแก้ไขหมวดหมู่");
            }
        } elseif ($action === 'delete') {
            $category->id = $_POST['id'];
            $result = $category->delete();

            if (is_array($result)) {
                if ($result['success']) {
                    echo json_encode(['success' => true, 'message' => 'ลบหมวดหมู่สำเร็จ']);
                } else {
                    if (isset($result['message'])) {
                        throw new Exception($result['message']);
                    }
                    $productCount = $result['product_count'] ?? 0;
                    if ($productCount > 0) {
                        throw new Exception("ไม่สามารถลบได้! มีสินค้า {$productCount} รายการอยู่ในหมวดหมู่นี้");
                    }
                    throw new Exception("เกิดข้อผิดพลาดในการลบหมวดหมู่");
                }
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการลบหมวดหมู่");
            }
        } elseif ($action === 'get_single') {
            $category->id = $_POST['id'];
            if ($category->read_single()) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description
                    ]
                ]);
            } else {
                throw new Exception("ไม่พบข้อมูลหมวดหมู่");
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch Categories with product count
$query = "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
          FROM categories c ORDER BY c.name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt;

$page_title = 'จัดการหมวดหมู่';
require_once '../templates/layouts/header.php';
?>

<div class="sm:flex sm:items-center mb-8">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-bold text-slate-800">จัดการหมวดหมู่สินค้า</h1>
        <p class="mt-2 text-sm text-slate-500">รายการหมวดหมู่สินค้าทั้งหมดในระบบ</p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button type="button" onclick="openAddModal()" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 sm:w-auto">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            เพิ่มหมวดหมู่ใหม่
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="categoriesTable" class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-slate-700">ชื่อหมวดหมู่</th>
                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-slate-700">รายละเอียด</th>
                    <th scope="col" class="px-6 py-4 text-center text-sm font-semibold text-slate-700">จำนวนสินค้า</th>
                    <th scope="col" class="relative py-4 pl-3 pr-6 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                <?php while ($row = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr class="hover:bg-slate-50 transition-colors duration-150">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                <span><?= htmlspecialchars($row['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 max-w-md">
                            <span class="line-clamp-2"><?= htmlspecialchars($row['description'] ?? '-') ?></span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $row['product_count'] > 0 ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' ?>">
                                <?= $row['product_count'] ?> รายการ
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="openEditModal(<?= $row['id'] ?>)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="แก้ไข">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="deleteCategory(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>', <?= $row['product_count'] ?>)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="ลบ">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal (Add/Edit) -->
<div id="categoryModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
                <form id="categoryForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="categoryId">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-xl font-bold leading-6 text-slate-900 mb-6" id="modalTitle">เพิ่มหมวดหมู่ใหม่</h3>
                                <div class="space-y-5">
                                    <div>
                                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">ชื่อหมวดหมู่ <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3" placeholder="เช่น อาหารและเครื่องดื่ม">
                                    </div>
                                    <div>
                                        <label for="description" class="block text-sm font-semibold text-slate-700 mb-1">รายละเอียด</label>
                                        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3" placeholder="คำอธิบายหมวดหมู่สินค้า"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-100">
                        <button type="submit" class="inline-flex w-full justify-center rounded-xl border border-transparent bg-blue-600 px-4 py-2.5 text-base font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm transition-all">บันทึกข้อมูล</button>
                        <button type="button" onclick="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-base font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#categoriesTable').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/th.json"
            },
            "dom": '<"flex flex-col sm:flex-row justify-between items-center mb-4 gap-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4 gap-4"ip>',
            "drawCallback": function() {
                $('.dataTables_length select').addClass('rounded-lg border-slate-300 text-slate-600 focus:ring-blue-500 focus:border-blue-500');
                $('.dataTables_filter input').addClass('rounded-lg border-slate-300 text-slate-600 focus:ring-blue-500 focus:border-blue-500 px-4 py-2');
            }
        });

        $('#categoryForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'categories.php',
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
        });
    });

    function openAddModal() {
        $('#formAction').val('create');
        $('#categoryId').val('');
        $('#modalTitle').text('เพิ่มหมวดหมู่ใหม่');
        $('#categoryForm')[0].reset();
        $('#categoryModal').removeClass('hidden');
    }

    function openEditModal(id) {
        $.post('categories.php', {
            action: 'get_single',
            id: id
        }, function(response) {
            if (response.success) {
                $('#formAction').val('update');
                $('#categoryId').val(response.data.id);
                $('#name').val(response.data.name);
                $('#description').val(response.data.description);
                $('#modalTitle').text('แก้ไขหมวดหมู่');
                $('#categoryModal').removeClass('hidden');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: response.message,
                    confirmButtonText: 'ตกลง'
                });
            }
        }, 'json');
    }

    function closeModal() {
        $('#categoryModal').addClass('hidden');
    }

    function deleteCategory(id, name, productCount) {
        if (productCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่สามารถลบได้!',
                html: `หมวดหมู่ "<strong>${name}</strong>" มีสินค้า <strong>${productCount}</strong> รายการ<br>กรุณาย้ายสินค้าออกก่อนลบหมวดหมู่`,
                confirmButtonText: 'เข้าใจแล้ว',
                confirmButtonColor: '#f59e0b'
            });
            return;
        }

        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: `คุณต้องการลบหมวดหมู่ "<strong>${name}</strong>" ใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('categories.php', {
                    action: 'delete',
                    id: id
                }, function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบสำเร็จ!',
                            text: response.message,
                            confirmButtonText: 'ตกลง'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่สามารถลบได้!',
                            text: response.message,
                            confirmButtonText: 'ตกลง'
                        });
                    }
                }, 'json').fail(function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                        confirmButtonText: 'ตกลง'
                    });
                    console.error(xhr.responseText);
                });
            }
        });
    }
</script>

<?php require_once '../templates/layouts/footer.php'; ?>