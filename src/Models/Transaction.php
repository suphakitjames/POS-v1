<?php

namespace App\Models;

use PDO;

class Transaction
{
    private $conn;
    private $table = 'transactions';

    public $id;
    public $product_id;
    public $user_id;
    public $supplier_id;
    public $type; // 'in', 'out', 'adjust'
    public $quantity;
    public $remaining_stock;
    public $note;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create Transaction
    public function create()
    {
        // 1. Get current stock quantity of the product
        $query_stock = "SELECT stock_quantity FROM products WHERE id = :product_id LIMIT 1";
        $stmt_stock = $this->conn->prepare($query_stock);
        $stmt_stock->bindParam(':product_id', $this->product_id);
        $stmt_stock->execute();
        $row = $stmt_stock->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false; // Product not found
        }

        $current_stock = (int)$row['stock_quantity'];
        $new_stock = 0;

        // 2. Calculate new stock based on transaction type
        if ($this->type === 'in') {
            $new_stock = $current_stock + $this->quantity;
        } elseif ($this->type === 'out') {
            if ($current_stock < $this->quantity) {
                return false; // Insufficient stock
            }
            $new_stock = $current_stock - $this->quantity;
        } elseif ($this->type === 'adjust') {
            // For adjust, quantity can be positive (add) or negative (remove)
            // But usually UI sends positive quantity and type determines action.
            // Let's assume 'adjust' logic is handled by caller setting type to 'in' or 'out' 
            // OR if type is strictly 'adjust', we might need more logic.
            // Based on schema, type is enum('in','out','adjust').
            // Let's assume for 'adjust', the quantity passed is the CHANGE amount.
            // If we want to support explicit 'adjust' type:
            $new_stock = $current_stock + $this->quantity; // Quantity should be negative for reduction
        }

        $this->remaining_stock = $new_stock;

        try {
            $this->conn->beginTransaction();

            // 3. Insert Transaction Record
            $query = 'INSERT INTO ' . $this->table . ' 
                      SET product_id = :product_id,
                          user_id = :user_id,
                          supplier_id = :supplier_id,
                          type = :type,
                          quantity = :quantity,
                          remaining_stock = :remaining_stock,
                          note = :note';

            $stmt = $this->conn->prepare($query);

            // Clean data
            $this->note = htmlspecialchars(strip_tags($this->note));

            // Bind data
            $stmt->bindParam(':product_id', $this->product_id);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':supplier_id', $this->supplier_id); // Can be null
            $stmt->bindParam(':type', $this->type);
            $stmt->bindParam(':quantity', $this->quantity);
            $stmt->bindParam(':remaining_stock', $this->remaining_stock);
            $stmt->bindParam(':note', $this->note);

            if (!$stmt->execute()) {
                throw new \Exception("Error creating transaction record.");
            }

            // 4. Update Product Stock
            $query_update = "UPDATE products SET stock_quantity = :stock_quantity WHERE id = :product_id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(':stock_quantity', $new_stock);
            $stmt_update->bindParam(':product_id', $this->product_id);

            if (!$stmt_update->execute()) {
                throw new \Exception("Error updating product stock.");
            }

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            // printf("Error: %s.\n", $e->getMessage()); // For debugging
            return false;
        }
    }

    // Get History (Optional for now, but good to have)
    public function getHistory($limit = 10)
    {
        $query = 'SELECT t.*, p.name as product_name, u.username 
                  FROM ' . $this->table . ' t
                  LEFT JOIN products p ON t.product_id = p.id
                  LEFT JOIN users u ON t.user_id = u.id
                  ORDER BY t.created_at DESC
                  LIMIT :limit';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Get Best Sellers (Top products by quantity sold)
    public function getBestSellers($limit = 5)
    {
        $query = 'SELECT p.name, SUM(t.quantity) as total_sold 
                  FROM ' . $this->table . ' t
                  JOIN products p ON t.product_id = p.id
                  WHERE t.type = "out"
                  GROUP BY t.product_id
                  ORDER BY total_sold DESC
                  LIMIT :limit';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get Movement History with Filters
    public function getMovementHistory($filters = [])
    {
        $query = 'SELECT t.*, p.name as product_name, u.username 
                  FROM ' . $this->table . ' t
                  LEFT JOIN products p ON t.product_id = p.id
                  LEFT JOIN users u ON t.user_id = u.id
                  WHERE 1=1';

        // Filter by Date Range
        if (!empty($filters['start_date'])) {
            $query .= ' AND DATE(t.created_at) >= :start_date';
        }
        if (!empty($filters['end_date'])) {
            $query .= ' AND DATE(t.created_at) <= :end_date';
        }

        // Filter by Type
        if (!empty($filters['type'])) {
            $query .= ' AND t.type = :type';
        }

        // Exclude Sales
        if (!empty($filters['exclude_sales'])) {
            $query .= " AND t.note NOT LIKE 'ขาย - ใบเสร็จ:%'";
        }

        $query .= ' ORDER BY t.created_at DESC';

        $stmt = $this->conn->prepare($query);

        if (!empty($filters['start_date'])) {
            $stmt->bindParam(':start_date', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $stmt->bindParam(':end_date', $filters['end_date']);
        }
        if (!empty($filters['type'])) {
            $stmt->bindParam(':type', $filters['type']);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get Recent Activity for Dashboard
    public function getRecentActivity($limit = 10)
    {
        $query = 'SELECT t.*, p.name as product_name, u.username 
                  FROM ' . $this->table . ' t
                  LEFT JOIN products p ON t.product_id = p.id
                  LEFT JOIN users u ON t.user_id = u.id
                  ORDER BY t.created_at DESC
                  LIMIT :limit';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get Daily Stats for Chart (Last N days)
    public function getDailyStats($days = 30)
    {
        $query = "SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as total_out
                  FROM " . $this->table . "
                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
