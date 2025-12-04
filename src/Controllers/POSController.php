<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class POSController
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * ค้นหาสินค้า (รองรับ SKU, Barcode, Name)
     */
    public function searchProducts($keyword)
    {
        try {
            $query = "SELECT p.*, c.name as category_name 
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE (p.sku LIKE :k1 
                         OR p.barcode LIKE :k2 
                         OR p.name LIKE :k3)
                      AND p.stock_quantity > 0
                      LIMIT 20";

            $searchTerm = "%{$keyword}%";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':k1', $searchTerm);
            $stmt->bindParam(':k2', $searchTerm);
            $stmt->bindParam(':k3', $searchTerm);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * ดึงข้อมูลสินค้าทั้งหมดที่มีสต็อก
     */
    public function getAllProducts()
    {
        try {
            $query = "SELECT p.*, c.name as category_name 
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE p.stock_quantity > 0
                      ORDER BY p.name ASC";

            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * ดึงข้อมูลสินค้าจาก Barcode (สำหรับเครื่องสแกน)
     */
    public function getProductByBarcode($barcode)
    {
        try {
            $query = "SELECT p.*, c.name as category_name 
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE p.barcode = :barcode
                      AND p.stock_quantity > 0
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':barcode', $barcode);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * บันทึกการขาย (Checkout)
     */
    public function checkout($userId, $shiftId, $items, $paymentMethod)
    {
        try {
            $this->conn->beginTransaction();

            // สร้างเลขที่ใบเสร็จ
            $receiptNumber = $this->generateReceiptNumber();

            // คำนวณยอดรวม
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            // Insert ข้อมูลหัวบิล
            $salesQuery = "INSERT INTO sales (receipt_number, user_id, shift_id, total_amount, payment_method, sale_date) 
                          VALUES (:receipt_number, :user_id, :shift_id, :total_amount, :payment_method, NOW())";

            $salesStmt = $this->conn->prepare($salesQuery);
            $salesStmt->bindParam(':receipt_number', $receiptNumber);
            $salesStmt->bindParam(':user_id', $userId);
            $salesStmt->bindParam(':shift_id', $shiftId);
            $salesStmt->bindParam(':total_amount', $totalAmount);
            $salesStmt->bindParam(':payment_method', $paymentMethod);
            $salesStmt->execute();

            $saleId = $this->conn->lastInsertId();

            // Insert รายการสินค้าและตัดสต็อก
            foreach ($items as $item) {
                // ตรวจสอบสต็อก
                $checkStockQuery = "SELECT stock_quantity FROM products WHERE id = :product_id FOR UPDATE";
                $checkStmt = $this->conn->prepare($checkStockQuery);
                $checkStmt->bindParam(':product_id', $item['product_id']);
                $checkStmt->execute();
                $product = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$product || $product['stock_quantity'] < $item['quantity']) {
                    throw new \Exception("สินค้า ID {$item['product_id']} มีสต็อกไม่เพียงพอ");
                }

                // Insert รายการสินค้า
                $subtotal = $item['quantity'] * $item['price'];
                $itemQuery = "INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal) 
                             VALUES (:sale_id, :product_id, :quantity, :price, :subtotal)";

                $itemStmt = $this->conn->prepare($itemQuery);
                $itemStmt->bindParam(':sale_id', $saleId);
                $itemStmt->bindParam(':product_id', $item['product_id']);
                $itemStmt->bindParam(':quantity', $item['quantity']);
                $itemStmt->bindParam(':price', $item['price']);
                $itemStmt->bindParam(':subtotal', $subtotal);
                $itemStmt->execute();

                // ตัดสต็อก
                $updateStockQuery = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
                $updateStmt = $this->conn->prepare($updateStockQuery);
                $updateStmt->bindParam(':quantity', $item['quantity']);
                $updateStmt->bindParam(':product_id', $item['product_id']);
                $updateStmt->execute();

                // บันทึก Log ใน transactions table (ถ้ามี)
                $remainingStock = $product['stock_quantity'] - $item['quantity'];
                $transQuery = "INSERT INTO transactions (product_id, user_id, type, quantity, remaining_stock, note, created_at) 
                              VALUES (:product_id, :user_id, 'out', :quantity, :remaining_stock, :note, NOW())";

                $transStmt = $this->conn->prepare($transQuery);
                $transStmt->bindParam(':product_id', $item['product_id']);
                $transStmt->bindParam(':user_id', $userId);
                $transStmt->bindParam(':quantity', $item['quantity']);
                $transStmt->bindParam(':remaining_stock', $remainingStock);
                $note = "ขาย - ใบเสร็จ: {$receiptNumber}";
                $transStmt->bindParam(':note', $note);
                $transStmt->execute();
            }

            $this->conn->commit();

            return [
                'success' => true,
                'sale_id' => $saleId,
                'receipt_number' => $receiptNumber,
                'total_amount' => $totalAmount,
                'message' => 'บันทึกการขายสำเร็จ'
            ];
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * สร้างเลขที่ใบเสร็จ
     */
    private function generateReceiptNumber()
    {
        $date = date('Ymd');
        $query = "SELECT receipt_number FROM sales 
                  WHERE receipt_number LIKE 'INV-{$date}-%' 
                  ORDER BY id DESC LIMIT 1";

        $stmt = $this->conn->query($query);
        $lastReceipt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lastReceipt) {
            $lastNumber = intval(substr($lastReceipt['receipt_number'], -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("INV-%s-%04d", $date, $newNumber);
    }

    /**
     * ดึงข้อมูลการขาย
     */
    public function getSale($saleId)
    {
        try {
            $query = "SELECT s.*, u.username 
                      FROM sales s
                      LEFT JOIN users u ON s.user_id = u.id
                      WHERE s.id = :sale_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sale_id', $saleId);
            $stmt->execute();

            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sale) {
                // ดึงรายการสินค้า
                $itemQuery = "SELECT si.*, p.name as product_name 
                             FROM sale_items si
                             LEFT JOIN products p ON si.product_id = p.id
                             WHERE si.sale_id = :sale_id";

                $itemStmt = $this->conn->prepare($itemQuery);
                $itemStmt->bindParam(':sale_id', $saleId);
                $itemStmt->execute();

                $sale['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $sale;
        } catch (\Exception $e) {
            return null;
        }
    }
}
