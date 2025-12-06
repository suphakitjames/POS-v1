<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/Supplier.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Config\Database;
use App\Models\Supplier;
use App\Middleware\AuthMiddleware;

// Check Authentication
AuthMiddleware::check();

// Database connection
$database = new Database();
$db = $database->connect();

// Init Model
$supplier = new Supplier($db);

// Handle Actions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $supplier->name = $_POST['name'];
            $supplier->contact_info = $_POST['contact_info'];
            $supplier->address = $_POST['address'];

            if ($supplier->create()) {
                echo json_encode(['success' => true, 'message' => 'เพิ่มคู่ค้าสำเร็จ']);
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการเพิ่มคู่ค้า");
            }
        } elseif ($action === 'update') {
            $supplier->id = $_POST['id'];
            $supplier->name = $_POST['name'];
            $supplier->contact_info = $_POST['contact_info'];
            $supplier->address = $_POST['address'];

            if ($supplier->update()) {
                echo json_encode(['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ']);
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการแก้ไขข้อมูล");
            }
        } elseif ($action === 'delete') {
            $supplier->id = $_POST['id'];
            $result = $supplier->delete();

            if (is_array($result)) {
                if ($result['success']) {
                    echo json_encode(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
                } else {
                    if (isset($result['message'])) {
                        throw new Exception($result['message']);
                    }
                    $productCount = $result['product_count'] ?? 0;
                    $transactionCount = $result['transaction_count'] ?? 0;
                    $errorParts = [];
                    if ($productCount > 0) {
                        $errorParts[] = "สินค้า {$productCount} รายการ";
                    }
                    if ($transactionCount > 0) {
                        $errorParts[] = "รายการธุรกรรม {$transactionCount} รายการ";
                    }
                    $errorMessage = "ไม่สามารถลบได้! มี" . implode(" และ ", $errorParts) . " ผูกกับผู้จัดจำหน่ายรายนี้อยู่";
                    throw new Exception($errorMessage);
                }
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการลบข้อมูล");
            }
        } elseif ($action === 'get_single') {
            $supplier->id = $_POST['id'];
            if ($supplier->read_single()) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'contact_info' => $supplier->contact_info,
                        'address' => $supplier->address
                    ]
                ]);
            } else {
                throw new Exception("ไม่พบข้อมูล");
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch Suppliers
$suppliers = $supplier->read();

$page_title = 'จัดการคู่ค้า';
require_once '../templates/layouts/header.php';
?>

<div class="sm:flex sm:items-center mb-8">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-bold text-slate-800">จัดการคู่ค้า (Suppliers)</h1>
        <p class="mt-2 text-sm text-slate-500">รายชื่อผู้จัดจำหน่ายและข้อมูลการติดต่อ</p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button type="button" onclick="openAddModal()" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 sm:w-auto">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            เพิ่มคู่ค้าใหม่
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="suppliersTable" class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-slate-700">ชื่อร้านค้า/บริษัท</th>
                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-slate-700">ข้อมูลติดต่อ</th>
                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-slate-700">ที่อยู่</th>
                    <th scope="col" class="relative py-4 pl-3 pr-6 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                <?php while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr class="hover:bg-slate-50 transition-colors duration-150">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars($row['contact_info']) ?></td>
                        <td class="px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars($row['address']) ?></td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="openEditModal(<?= $row['id'] ?>)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="แก้ไข">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="deleteSupplier(<?= $row['id'] ?>)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="ลบ">
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
<div id="supplierModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
                <form id="supplierForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="supplierId">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-xl font-bold leading-6 text-slate-900 mb-6" id="modalTitle">เพิ่มคู่ค้าใหม่</h3>
                                <div class="space-y-5">
                                    <div>
                                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">ชื่อร้านค้า/บริษัท <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3">
                                    </div>
                                    <div>
                                        <label for="contact_info" class="block text-sm font-semibold text-slate-700 mb-1">เบอร์โทร / อีเมล / ไลน์</label>
                                        <input type="text" name="contact_info" id="contact_info" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3">
                                    </div>
                                    <div>
                                        <label for="address" class="block text-sm font-semibold text-slate-700 mb-1">ที่อยู่</label>
                                        <textarea name="address" id="address" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3"></textarea>
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
        $('#suppliersTable').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/th.json"
            },
            "dom": '<"flex flex-col sm:flex-row justify-between items-center mb-4 gap-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4 gap-4"ip>',
            "drawCallback": function() {
                $('.dataTables_length select').addClass('rounded-lg border-slate-300 text-slate-600 focus:ring-blue-500 focus:border-blue-500');
                $('.dataTables_filter input').addClass('rounded-lg border-slate-300 text-slate-600 focus:ring-blue-500 focus:border-blue-500 px-4 py-2');
            }
        });

        $('#supplierForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'suppliers.php',
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
        $('#supplierId').val('');
        $('#modalTitle').text('เพิ่มคู่ค้าใหม่');
        $('#supplierForm')[0].reset();
        $('#supplierModal').removeClass('hidden');
    }

    function openEditModal(id) {
        $.post('suppliers.php', {
            action: 'get_single',
            id: id
        }, function(response) {
            if (response.success) {
                $('#formAction').val('update');
                $('#supplierId').val(response.data.id);
                $('#name').val(response.data.name);
                $('#contact_info').val(response.data.contact_info);
                $('#address').val(response.data.address);
                $('#modalTitle').text('แก้ไขข้อมูลคู่ค้า');
                $('#supplierModal').removeClass('hidden');
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
        $('#supplierModal').addClass('hidden');
    }

    function deleteSupplier(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: 'คุณต้องการลบข้อมูลนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('suppliers.php', {
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