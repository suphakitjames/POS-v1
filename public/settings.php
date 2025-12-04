<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Middleware/AuthMiddleware.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::checkAdmin();

$page_title = 'ตั้งค่าระบบ';
require_once '../templates/layouts/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-200 bg-slate-50">
            <h2 class="text-xl font-bold text-slate-800">ตั้งค่าระบบ</h2>
            <p class="text-sm text-slate-500 mt-1">กำหนดค่าต่างๆ ของระบบ</p>
        </div>

        <div class="p-6">
            <form id="settingsForm" class="space-y-6">
                <!-- PromptPay Settings -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                        ตั้งค่าการชำระเงิน (PromptPay)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">PromptPay ID (เบอร์มือถือ หรือ เลขผู้เสียภาษี)</label>
                            <input type="text" name="promptpay_id" id="promptpay_id" class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="เช่น 0812345678 หรือ 01055..." required>
                            <p class="text-xs text-slate-500 mt-1">ใช้สำหรับสร้าง QR Code รับเงินหน้าร้าน</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100">
                    <button type="submit" id="saveSettingsBtn" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-100 transition-all shadow-sm flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        บันทึกการตั้งค่า
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Settings page JavaScript - using jQuery from header.php
    jQuery(document).ready(function($) {
        console.log('=== SETTINGS PAGE LOADED ===');
        console.log('jQuery version:', $.fn.jquery);

        // Load existing settings
        function loadSettings() {
            console.log('Loading settings...');
            $.ajax({
                url: 'api/settings.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Settings loaded successfully:', response);
                    if (response.success && response.data) {
                        if (response.data.promptpay_id) {
                            $('#promptpay_id').val(response.data.promptpay_id);
                            console.log('PromptPay ID set to:', response.data.promptpay_id);
                        }
                    } else {
                        console.warn('No settings data returned');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('=== ERROR LOADING SETTINGS ===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    console.error('Status Code:', xhr.status);

                    if (xhr.status === 500) {
                        alert('Internal Server Error: กรุณาตรวจสอบ database และ API');
                    } else {
                        alert('ไม่สามารถโหลดข้อมูลการตั้งค่าได้: ' + error);
                    }
                }
            });
        }

        // Save settings
        $('#settingsForm').on('submit', function(e) {
            e.preventDefault();
            console.log('=== SAVING SETTINGS ===');

            const formData = $(this).serialize();
            console.log('Form data:', formData);

            const $submitBtn = $('#saveSettingsBtn');
            const originalHtml = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<span class="loading">กำลังบันทึก...</span>');

            $.ajax({
                url: 'api/settings.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log('=== SAVE SUCCESS ===');
                    console.log('Response:', response);
                    $submitBtn.prop('disabled', false).html(originalHtml);

                    if (response.success) {
                        alert(response.message || 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
                        loadSettings(); // Reload to confirm
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('=== ERROR SAVING SETTINGS ===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    console.error('Status Code:', xhr.status);

                    $submitBtn.prop('disabled', false).html(originalHtml);

                    if (xhr.status === 500) {
                        alert('Internal Server Error: กรุณาตรวจสอบ database และ API\n\n' + xhr.responseText);
                    } else {
                        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + error);
                    }
                }
            });
        });

        // Initial load
        loadSettings();
    });
</script>

<?php require_once '../templates/layouts/footer.php'; ?>