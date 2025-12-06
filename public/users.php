<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Models/User.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Config\Database;
use App\Models\User;
use App\Middleware\AuthMiddleware;

// Check Authentication
AuthMiddleware::check();

// Check Admin Role
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Database connection
$database = new Database();
$db = $database->connect();
$user = new User($db);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    if (ob_get_length()) ob_clean();

    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';

        // Handle File Upload
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception('อนุญาตเฉพาะไฟล์รูปภาพ (jpg, jpeg, png, gif) เท่านั้น');
            }

            $fileName = uniqid() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
                $profile_image = $fileName;
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');
            }
        }

        if ($action === 'create') {
            if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['role'])) {
                throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
            }

            if (!empty($_POST['phone']) && !preg_match('/^[0-9]{10}$/', $_POST['phone'])) {
                throw new Exception('เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก');
            }

            $user->username = $_POST['username'];
            $user->first_name = $_POST['first_name'] ?? '';
            $user->last_name = $_POST['last_name'] ?? '';
            $user->phone = $_POST['phone'] ?? '';
            $user->profile_image = $profile_image ?? '';
            $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $user->role = $_POST['role'];

            if ($user->usernameExists()) {
                echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว']);
                exit;
            }

            if ($user->create()) {
                echo json_encode(['success' => true, 'message' => 'เพิ่มผู้ใช้งานสำเร็จ']);
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการเพิ่มผู้ใช้งาน');
            }
            exit;
        }

        if ($action === 'update') {
            if (empty($_POST['id']) || empty($_POST['username']) || empty($_POST['role'])) {
                throw new Exception('ข้อมูลไม่ครบถ้วน');
            }

            if (!empty($_POST['phone']) && !preg_match('/^[0-9]{10}$/', $_POST['phone'])) {
                throw new Exception('เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก');
            }

            $user->id = $_POST['id'];
            $existingUser = $user->read_single();

            if (!$existingUser) {
                throw new Exception('ไม่พบผู้ใช้งานที่ต้องการแก้ไข');
            }

            $user->username = $_POST['username'];
            $user->first_name = $_POST['first_name'] ?? '';
            $user->last_name = $_POST['last_name'] ?? '';
            $user->phone = $_POST['phone'] ?? '';
            $user->role = $_POST['role'];

            if ($profile_image) {
                $user->profile_image = $profile_image;
            } else {
                $user->profile_image = $existingUser['profile_image'] ?? '';
            }

            if (!empty($_POST['password'])) {
                $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            } else {
                $user->password = '';
            }

            if ($user->update()) {
                echo json_encode(['success' => true, 'message' => 'อัปเดตข้อมูลสำเร็จ']);
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการอัปเดตข้อมูล');
            }
            exit;
        }

        if ($action === 'delete') {
            if (empty($_POST['id'])) {
                throw new Exception('ไม่พบรหัสผู้ใช้งาน');
            }

            $user->id = $_POST['id'];

            if ($user->id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบบัญชีของตัวเองได้']);
                exit;
            }

            if ($user->delete()) {
                echo json_encode(['success' => true, 'message' => 'ลบผู้ใช้งานสำเร็จ']);
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการลบผู้ใช้งาน');
            }
            exit;
        }

        if ($action === 'get') {
            if (empty($_POST['id'])) {
                throw new Exception('ไม่พบรหัสผู้ใช้งาน');
            }

            $user->id = $_POST['id'];
            $data = $user->read_single();

            if (!$data) {
                throw new Exception('ไม่พบข้อมูลผู้ใช้งาน');
            }

            echo json_encode($data);
            exit;
        }

        throw new Exception('Invalid action');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    } catch (Error $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
        exit;
    }
}

// Get all users
$stmt = $user->read();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'จัดการผู้ใช้งาน';
require_once '../templates/layouts/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">จัดการผู้ใช้งาน</h1>
            <p class="mt-1 text-sm text-gray-600">จัดการบัญชีผู้ใช้งานและสิทธิ์การเข้าถึง</p>
        </div>
        <button type="button" onclick="openAddModal()" class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all shadow-sm">
            <svg class="-ml-0.5 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            เพิ่มผู้ใช้งาน
        </button>
    </div>
</div>

<!-- Users Table Card -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="usersTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ผู้ใช้งาน</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ชื่อ-นามสกุล</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">เบอร์โทร</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">บทบาท</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">วันที่สร้าง</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden border border-gray-300">
                                    <?php if (!empty($u['profile_image']) && file_exists('uploads/profiles/' . $u['profile_image'])): ?>
                                        <img src="uploads/profiles/<?= h($u['profile_image']) ?>" alt="" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <span class="text-gray-500 font-bold text-sm"><?= strtoupper(substr($u['username'], 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= h($u['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= h(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= h($u['phone'] ?? '') ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">ผู้ดูแลระบบ</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">พนักงาน</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openEditModal(<?= $u['id'] ?>)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="แก้ไข">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?= $u['id'] ?>)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="ลบ">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl z-10">
            <h3 id="modalTitle" class="text-xl font-bold text-white">เพิ่มผู้ใช้งาน</h3>
        </div>
        <form id="userForm" class="p-6" enctype="multipart/form-data">
            <input type="hidden" id="userId" name="id">
            <input type="hidden" id="formAction" name="action">

            <div class="space-y-4">
                <!-- Profile Image Upload -->
                <div class="flex justify-center mb-4">
                    <div class="relative">
                        <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-400 hover:border-blue-500 transition-colors cursor-pointer" onclick="document.getElementById('profile_image').click()">
                            <img id="previewImage" src="" alt="" class="h-full w-full object-cover hidden">
                            <div id="placeholderImage" class="text-center">
                                <svg class="h-8 w-8 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-xs text-gray-500 block mt-1">รูปโปรไฟล์</span>
                            </div>
                        </div>
                        <input type="file" name="profile_image" id="profile_image" class="hidden" accept="image/*" onchange="previewFile()">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อผู้ใช้งาน <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อ</label>
                        <input type="text" name="first_name" id="first_name" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">นามสกุล</label>
                        <input type="text" name="last_name" id="last_name" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" id="phone" maxlength="10" pattern="\d{10}" title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">รหัสผ่าน <span id="passwordRequired" class="text-red-500">*</span></label>
                    <input type="password" name="password" id="password" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                    <p id="passwordHint" class="text-xs text-gray-500 mt-1 hidden">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">บทบาท <span class="text-red-500">*</span></label>
                    <select name="role" id="role" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                        <option value="staff">พนักงาน (Staff)</option>
                        <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-3 justify-end border-t pt-6">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors">ยกเลิก</button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-sm">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
    let table;

    $(document).ready(function() {
        table = $('#usersTable').DataTable({
            "language": {
                "sProcessing": "กำลังดำเนินการ...",
                "sLengthMenu": "แสดง _MENU_ รายการ",
                "sZeroRecords": "ไม่พบข้อมูล",
                "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "sInfoEmpty": "แสดง 0 ถึง 0 จาก 0 รายการ",
                "sInfoFiltered": "(กรองข้อมูล _MAX_ ทุกรายการ)",
                "sSearch": "ค้นหา:",
                "oPaginate": {
                    "sFirst": "หน้าแรก",
                    "sPrevious": "ก่อนหน้า",
                    "sNext": "ถัดไป",
                    "sLast": "หน้าสุดท้าย"
                }
            },
            "pageLength": 10,
            "order": [
                [4, "desc"]
            ]
        });
    });

    function previewFile() {
        const preview = document.getElementById('previewImage');
        const placeholder = document.getElementById('placeholderImage');
        const file = document.getElementById('profile_image').files[0];
        const reader = new FileReader();

        reader.onloadend = function() {
            preview.src = reader.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }

        if (file) {
            reader.readAsDataURL(file);
        } else {
            preview.src = "";
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
        }
    }

    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'เพิ่มผู้ใช้งาน';
        document.getElementById('formAction').value = 'create';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';

        document.getElementById('previewImage').src = '';
        document.getElementById('previewImage').classList.add('hidden');
        document.getElementById('placeholderImage').classList.remove('hidden');

        document.getElementById('password').required = true;
        document.getElementById('passwordRequired').classList.remove('hidden');
        document.getElementById('passwordHint').classList.add('hidden');

        document.getElementById('userModal').classList.remove('hidden');
        document.getElementById('userModal').classList.add('flex');
    }

    function openEditModal(id) {
        document.getElementById('modalTitle').textContent = 'แก้ไขผู้ใช้งาน';
        document.getElementById('formAction').value = 'update';

        document.getElementById('password').required = false;
        document.getElementById('passwordRequired').classList.add('hidden');
        document.getElementById('passwordHint').classList.remove('hidden');

        $.ajax({
            url: 'users.php',
            method: 'POST',
            data: {
                action: 'get',
                id: id
            },
            success: function(data) {
                document.getElementById('userId').value = data.id;
                document.getElementById('username').value = data.username;
                document.getElementById('first_name').value = data.first_name || '';
                document.getElementById('last_name').value = data.last_name || '';
                document.getElementById('phone').value = data.phone || '';
                document.getElementById('role').value = data.role;
                document.getElementById('password').value = '';

                const preview = document.getElementById('previewImage');
                const placeholder = document.getElementById('placeholderImage');

                if (data.profile_image) {
                    preview.src = 'uploads/profiles/' + data.profile_image;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                } else {
                    preview.src = '';
                    preview.classList.add('hidden');
                    placeholder.classList.remove('hidden');
                }

                document.getElementById('userModal').classList.remove('hidden');
                document.getElementById('userModal').classList.add('flex');
            },
            error: function(xhr, status, error) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: response.message || error,
                        confirmButtonText: 'ตกลง'
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: error,
                        confirmButtonText: 'ตกลง'
                    });
                }
            }
        });
    }

    function closeModal() {
        document.getElementById('userModal').classList.add('hidden');
        document.getElementById('userModal').classList.remove('flex');
    }

    function deleteUser(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: 'คุณต้องการลบผู้ใช้งานนี้หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'users.php',
                    method: 'POST',
                    data: {
                        action: 'delete',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
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
                                title: 'เกิดข้อผิดพลาด!',
                                text: response.message,
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: response.message || error,
                                confirmButtonText: 'ตกลง'
                            });
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: error,
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    }
                });
            }
        });
    }

    $('#userForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        const phone = formData.get('phone');
        if (phone && phone.length !== 10) {
            Swal.fire({
                icon: 'warning',
                title: 'แจ้งเตือน',
                text: 'กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        $.ajax({
            url: 'users.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
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
                try {
                    var response = JSON.parse(xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: response.message || error,
                        confirmButtonText: 'ตกลง'
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: 'เกิดข้อผิดพลาดในการบันทึก: ' + error,
                        confirmButtonText: 'ตกลง'
                    });
                }
            }
        });
    });
</script>

<?php require_once '../templates/layouts/footer.php'; ?>