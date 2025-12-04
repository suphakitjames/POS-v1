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

// Database connection
$database = new Database();
$db = $database->connect();
$user = new User($db);

$user->id = $_SESSION['user_id'];
$currentUser = $user->read_single();

$success_msg = '';
$error_msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        try {
            $user->username = $currentUser['username']; // Keep username same
            $user->role = $currentUser['role']; // Keep role same
            $user->first_name = $_POST['first_name'] ?? '';
            $user->last_name = $_POST['last_name'] ?? '';
            $user->phone = $_POST['phone'] ?? '';
            $user->profile_image = $currentUser['profile_image']; // Default to current

            // Handle Image Upload
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
                    $user->profile_image = $fileName;
                    $_SESSION['profile_image'] = $fileName;
                } else {
                    throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');
                }
            }

            if ($user->update()) {
                $success_msg = 'บันทึกข้อมูลสำเร็จ';
                // Refresh user data
                $currentUser = $user->read_single();
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }
    } elseif ($action === 'change_password') {
        try {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Verify current password (need to fetch hash from DB first, read_single doesn't return password)
            // We need a custom query or method to verify password, or just trust the user is logged in?
            // Ideally, we should verify the current password.
            // For now, let's implement a simple check if we can.
            // Since User model doesn't expose password hash in read_single, we might need to add a method or just rely on session?
            // Wait, for security, we MUST verify current password.

            // Let's quickly query to get the password hash
            $query = 'SELECT password_hash FROM users WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($current_password, $row['password_hash'])) {
                throw new Exception('รหัสผ่านปัจจุบันไม่ถูกต้อง');
            }

            if (strlen($new_password) < 6) {
                throw new Exception('รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
            }

            if ($new_password !== $confirm_password) {
                throw new Exception('รหัสผ่านใหม่ไม่ตรงกัน');
            }

            $user->username = $currentUser['username'];
            $user->role = $currentUser['role'];
            $user->first_name = $currentUser['first_name'];
            $user->last_name = $currentUser['last_name'];
            $user->phone = $currentUser['phone'];
            $user->profile_image = $currentUser['profile_image'];
            $user->password = password_hash($new_password, PASSWORD_DEFAULT);

            if ($user->update()) {
                $success_msg = 'เปลี่ยนรหัสผ่านสำเร็จ';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน');
            }
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }
    }
}

$page_title = 'ข้อมูลส่วนตัว';
require_once '../templates/layouts/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">ข้อมูลส่วนตัว</h1>
        <p class="mt-1 text-sm text-gray-600">จัดการข้อมูลส่วนตัวและรหัสผ่านของคุณ</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?= $success_msg ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?= $error_msg ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Left Column: Profile Card -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 text-center">
                    <div class="relative inline-block">
                        <div class="h-32 w-32 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden border-4 border-white shadow-lg mx-auto">
                            <?php if (!empty($currentUser['profile_image']) && file_exists('uploads/profiles/' . $currentUser['profile_image'])): ?>
                                <img src="uploads/profiles/<?= h($currentUser['profile_image']) ?>" alt="Profile" class="h-full w-full object-cover">
                            <?php else: ?>
                                <span class="text-4xl font-bold text-gray-500"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                        <label for="profile_upload" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full shadow-lg cursor-pointer hover:bg-blue-700 transition-colors" title="เปลี่ยนรูปโปรไฟล์">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </label>
                    </div>

                    <h2 class="mt-4 text-xl font-bold text-gray-900">
                        <?= h(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?: h($currentUser['username']) ?>
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">@<?= h($currentUser['username']) ?></p>

                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $currentUser['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?>">
                        <?= $currentUser['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน' ?>
                    </div>
                </div>
                <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                    <p class="text-xs text-gray-500 text-center">
                        สมาชิกเมื่อ: <?= date('d M Y', strtotime($currentUser['created_at'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Column: Edit Forms -->
        <div class="md:col-span-2 space-y-6">
            <!-- General Info Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">ข้อมูลทั่วไป</h3>
                </div>
                <form action="profile.php" method="POST" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" value="update_info">
                    <!-- Hidden file input triggered by the camera icon -->
                    <input type="file" name="profile_image" id="profile_upload" class="hidden" accept="image/*" onchange="this.form.submit()">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ</label>
                            <input type="text" name="first_name" value="<?= h($currentUser['first_name'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">นามสกุล</label>
                            <input type="text" name="last_name" value="<?= h($currentUser['last_name'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" value="<?= h($currentUser['phone'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors shadow-sm">
                            บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">เปลี่ยนรหัสผ่าน</h3>
                </div>
                <form action="profile.php" method="POST" class="p-6">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่านปัจจุบัน</label>
                        <input type="password" name="current_password" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" required minlength="6" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" required minlength="6" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-gray-800 text-white font-medium rounded-lg hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors shadow-sm">
                            เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/layouts/footer.php'; ?>