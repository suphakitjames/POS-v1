<?php

namespace App\Models;

use PDO;

class Category
{
    private $conn;
    private $table = 'categories';

    public $id;
    public $name;
    public $description;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Read All Categories
    public function read()
    {
        $query = 'SELECT * FROM ' . $this->table . ' ORDER BY name ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get Single Category
    public function read_single()
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE id = ? LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            return true;
        }
        return false;
    }

    // Create Category
    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' 
                  SET name = :name, 
                      description = :description';

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));

        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update Category
    public function update()
    {
        $query = 'UPDATE ' . $this->table . ' 
                  SET name = :name, 
                      description = :description 
                  WHERE id = :id';

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Count products linked to this category
    public function countProducts()
    {
        $query = 'SELECT COUNT(*) as total FROM products WHERE category_id = :category_id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Delete category with validation
    public function delete()
    {
        // Check if category has associated products
        $productCount = $this->countProducts();

        // If has products, prevent deletion
        if ($productCount > 0) {
            return [
                'success' => false,
                'product_count' => $productCount
            ];
        }

        try {
            // No dependencies, safe to delete
            $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
            $stmt = $this->conn->prepare($query);
            $this->id = htmlspecialchars(strip_tags($this->id));
            $stmt->bindParam(':id', $this->id);

            if ($stmt->execute()) {
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Unknown error occurred'];
        } catch (\PDOException $e) {
            // Check for foreign key constraint violation
            if ($e->getCode() == '23000') {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถลบได้เนื่องจากมีสินค้าอยู่ในหมวดหมู่นี้'
                ];
            }
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}
