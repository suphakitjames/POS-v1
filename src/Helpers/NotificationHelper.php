<?php

namespace App\Helpers;

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Notification.php';

use App\Config\Database;
use App\Models\Notification;
use PDO;

class NotificationHelper
{
    private $db;
    private $notification;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->notification = new Notification($this->db);
    }

    /**
     * Check for low stock products and create notifications
     * @param int $user_id - User to notify (typically admin)
     * @return int - Number of notifications created
     */
    public function checkLowStockProducts($user_id)
    {
        $count = 0;

        // Query products where stock_quantity <= reorder_point
        $query = 'SELECT id, name, sku, stock_quantity, reorder_point 
                  FROM products 
                  WHERE stock_quantity > 0 
                  AND stock_quantity <= reorder_point';

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            // Check if notification already exists (prevent duplicates)
            if (!$this->notification->exists($user_id, 'low_stock', $product['id'])) {
                $this->notification->user_id = $user_id;
                $this->notification->type = 'low_stock';
                $this->notification->title = '⚠️ สินค้าต่ำกว่าเกณฑ์';
                $this->notification->message = sprintf(
                    'สินค้า %s (SKU: %s) เหลือเพียง %d ชิ้น (เกณฑ์: %d)',
                    $product['name'],
                    $product['sku'],
                    $product['stock_quantity'],
                    $product['reorder_point']
                );
                $this->notification->related_id = $product['id'];

                if ($this->notification->create()) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check for out of stock products and create notifications
     * @param int $user_id
     * @return int - Number of notifications created
     */
    public function checkOutOfStock($user_id)
    {
        $count = 0;

        // Query products where stock_quantity = 0
        $query = 'SELECT id, name, sku 
                  FROM products 
                  WHERE stock_quantity = 0';

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            if (!$this->notification->exists($user_id, 'out_of_stock', $product['id'])) {
                $this->notification->user_id = $user_id;
                $this->notification->type = 'out_of_stock';
                $this->notification->title = '📦 สินค้าหมดสต็อก';
                $this->notification->message = sprintf(
                    'สินค้า %s (SKU: %s) หมดสต็อก กรุณาเติมสินค้า',
                    $product['name'],
                    $product['sku']
                );
                $this->notification->related_id = $product['id'];

                if ($this->notification->create()) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check for products expiring soon (within 7-30 days)
     * @param int $user_id
     * @param int $days - Number of days threshold (default 30)
     * @return int - Number of notifications created
     */
    public function checkExpiringProducts($user_id, $days = 30)
    {
        $count = 0;

        // Query products expiring within specified days
        $query = 'SELECT id, name, sku, expire_date, 
                  DATEDIFF(expire_date, NOW()) as days_until_expiry
                  FROM products 
                  WHERE expire_date IS NOT NULL 
                  AND expire_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
                  AND stock_quantity > 0
                  ORDER BY expire_date ASC';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            if (!$this->notification->exists($user_id, 'expiring_soon', $product['id'])) {
                $this->notification->user_id = $user_id;
                $this->notification->type = 'expiring_soon';
                $this->notification->title = '🕒 สินค้าใกล้หมดอายุ';

                $daysText = $product['days_until_expiry'] == 1 ? '1 วัน' : $product['days_until_expiry'] . ' วัน';
                $this->notification->message = sprintf(
                    'สินค้า %s (SKU: %s) จะหมดอายุใน %s (วันที่ %s)',
                    $product['name'],
                    $product['sku'],
                    $daysText,
                    date('d/m/Y', strtotime($product['expire_date']))
                );
                $this->notification->related_id = $product['id'];

                if ($this->notification->create()) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Create security alert notification
     * @param int $user_id - User to notify (admin)
     * @param string $username - Username that had failed attempts
     * @param string $ip_address - IP address
     * @param int $failure_count - Number of failures
     * @return bool
     */
    public function triggerSecurityAlert($user_id, $username, $ip_address, $failure_count)
    {
        $this->notification->user_id = $user_id;
        $this->notification->type = 'security_alert';
        $this->notification->title = '👮 การแจ้งเตือนความปลอดภัย';
        $this->notification->message = sprintf(
            'มีการพยายาม Login ผิดพลาด %d ครั้ง สำหรับบัญชี "%s" จาก IP: %s',
            $failure_count,
            $username,
            $ip_address
        );
        $this->notification->related_id = null; // No related product

        return $this->notification->create();
    }

    /**
     * Run all notification checks for ALL users
     * @return array - Summary of created notifications
     */
    public function runAllChecks()
    {
        // Get all users from database
        $query = 'SELECT id FROM users';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'low_stock' => 0,
            'out_of_stock' => 0,
            'expiring_soon' => 0,
            'total' => 0
        ];

        // Create notifications for all active users
        foreach ($users as $user) {
            $user_id = $user['id'];
            $summary['low_stock'] += $this->checkLowStockProducts($user_id);
            $summary['out_of_stock'] += $this->checkOutOfStock($user_id);
            $summary['expiring_soon'] += $this->checkExpiringProducts($user_id, 30);
        }

        $summary['total'] = $summary['low_stock'] + $summary['out_of_stock'] + $summary['expiring_soon'];

        return $summary;
    }

    /**
     * Clean up old notifications
     * @param int $days - Delete notifications older than this (default 30)
     * @return bool
     */
    public function cleanup($days = 30)
    {
        return $this->notification->deleteOld($days);
    }
}
