<?php
require_once '../../src/Helpers/functions.php';
require_once '../../src/Config/Database.php';
require_once '../../src/Models/Notification.php';
require_once '../../src/Middleware/AuthMiddleware.php';

use App\Config\Database;
use App\Models\Notification;
use App\Middleware\AuthMiddleware;

// Check Authentication
AuthMiddleware::check();

// Set JSON header
header('Content-Type: application/json');

// Turn off error display
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Clear output buffer
if (ob_get_length()) ob_clean();

// Database connection
$database = new Database();
$db = $database->connect();
$notification = new Notification($db);

// Get current user
$user_id = $_SESSION['user_id'];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_notifications':
            // Get notifications for current user
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $stmt = $notification->read($user_id, $limit);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format created_at to Thai format
            foreach ($notifications as &$notif) {
                $notif['created_at_formatted'] = format_date_thai($notif['created_at']);
                $notif['time_ago'] = timeAgo($notif['created_at']);
            }

            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
            break;

        case 'get_unread':
            // Get only unread notifications
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $stmt = $notification->readUnread($user_id, $limit);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format created_at
            foreach ($notifications as &$notif) {
                $notif['created_at_formatted'] = format_date_thai($notif['created_at']);
                $notif['time_ago'] = timeAgo($notif['created_at']);
            }

            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
            break;

        case 'get_count':
            // Get unread count
            $count = $notification->getUnreadCount($user_id);

            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;

        case 'mark_as_read':
            // Mark single notification as read
            if (empty($_POST['id'])) {
                throw new Exception('ไม่พบรหัสการแจ้งเตือน');
            }

            $id = (int)$_POST['id'];

            if ($notification->markAsRead($id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'ทำเครื่องหมายว่าอ่านแล้ว'
                ]);
            } else {
                throw new Exception('ไม่สามารถทำเครื่องหมายได้');
            }
            break;

        case 'mark_all_read':
            // Mark all notifications as read for current user
            if ($notification->markAllAsRead($user_id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว'
                ]);
            } else {
                throw new Exception('ไม่สามารถทำเครื่องหมายได้');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}

/**
 * Helper function to calculate time ago
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'เมื่อสักครู่';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' นาทีที่แล้ว';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ชั่วโมงที่แล้ว';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' วันที่แล้ว';
    } else {
        return date('d/m/Y', $time);
    }
}
