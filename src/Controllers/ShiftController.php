<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class ShiftController
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * เปิดกะใหม่
     */
    public function openShift($userId, $startCash)
    {
        try {
            // ตรวจสอบว่ามีกะที่เปิดอยู่แล้วหรือไม่
            $openShift = $this->getOpenShift($userId);
            if ($openShift) {
                return [
                    'success' => false,
                    'message' => 'มีกะที่เปิดอยู่แล้ว กรุณาปิดกะก่อน'
                ];
            }

            $query = "INSERT INTO shifts (user_id, start_time, start_cash, status) 
                      VALUES (:user_id, NOW(), :start_cash, 'open')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':start_cash', $startCash);

            if ($stmt->execute()) {
                $shiftId = $this->conn->lastInsertId();
                return [
                    'success' => true,
                    'shift_id' => $shiftId,
                    'message' => 'เปิดกะสำเร็จ'
                ];
            }

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเปิดกะ'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ปิดกะ
     */
    public function closeShift($shiftId, $endCash)
    {
        try {
            // คำนวณยอดเงินสดที่คาดว่าจะมี (start_cash + ยอดขายเงินสด)
            $query = "SELECT s.start_cash, 
                             COALESCE(SUM(CASE WHEN sl.payment_method = 'cash' THEN sl.total_amount ELSE 0 END), 0) as cash_sales
                      FROM shifts s
                      LEFT JOIN sales sl ON sl.shift_id = s.id
                      WHERE s.id = :shift_id
                      GROUP BY s.id, s.start_cash";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':shift_id', $shiftId);
            $stmt->execute();

            $shiftData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$shiftData) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลกะ'
                ];
            }

            $expectedCash = $shiftData['start_cash'] + $shiftData['cash_sales'];
            $diffAmount = $endCash - $expectedCash;

            // อัพเดทข้อมูลการปิดกะ
            $updateQuery = "UPDATE shifts 
                           SET end_time = NOW(), 
                               end_cash = :end_cash, 
                               expected_cash = :expected_cash, 
                               diff_amount = :diff_amount, 
                               status = 'closed'
                           WHERE id = :shift_id";

            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':end_cash', $endCash);
            $updateStmt->bindParam(':expected_cash', $expectedCash);
            $updateStmt->bindParam(':diff_amount', $diffAmount);
            $updateStmt->bindParam(':shift_id', $shiftId);

            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'expected_cash' => $expectedCash,
                    'end_cash' => $endCash,
                    'diff_amount' => $diffAmount,
                    'message' => 'ปิดกะสำเร็จ'
                ];
            }

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการปิดกะ'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ดึงข้อมูลกะที่เปิดอยู่
     */
    public function getOpenShift($userId)
    {
        try {
            $query = "SELECT * FROM shifts 
                      WHERE user_id = :user_id AND status = 'open' 
                      ORDER BY start_time DESC LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ดึงสรุปยอดขายในกะปัจจุบัน
     */
    public function getShiftSummary($shiftId)
    {
        try {
            $query = "SELECT 
                        COUNT(sl.id) as total_sales,
                        COALESCE(SUM(sl.total_amount), 0) as total_amount,
                        COALESCE(SUM(CASE WHEN sl.payment_method = 'cash' THEN sl.total_amount ELSE 0 END), 0) as cash_total,
                        COALESCE(SUM(CASE WHEN sl.payment_method = 'qr' THEN sl.total_amount ELSE 0 END), 0) as qr_total,
                        COALESCE(SUM(CASE WHEN sl.payment_method = 'credit' THEN sl.total_amount ELSE 0 END), 0) as credit_total
                      FROM sales sl
                      WHERE sl.shift_id = :shift_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':shift_id', $shiftId);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return null;
        }
    }
}
