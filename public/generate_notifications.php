<?php
require_once '../src/Helpers/functions.php';
require_once '../src/Config/Database.php';
require_once '../src/Helpers/NotificationHelper.php';

use App\Helpers\NotificationHelper;

// Run this script to generate notifications
$helper = new NotificationHelper();

// Check and create notifications for admin user (user_id = 1)
$admin_user_id = 1;

echo "Running notification checks...\n\n";

$result = $helper->runAllChecks($admin_user_id);

echo "✅ Notifications generated:\n";
echo "   - Low Stock: {$result['low_stock']} notifications\n";
echo "   - Out of Stock: {$result['out_of_stock']} notifications\n";
echo "   - Expiring Soon: {$result['expiring_soon']} notifications\n";
echo "   - Total: {$result['total']} notifications\n\n";

echo "Done! Refresh the page to see notifications.\n";
