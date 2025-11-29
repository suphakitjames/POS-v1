<?php

namespace App\Models;

use PDO;

class Supplier
{
    private $conn;
    private $table = 'suppliers';

    public $id;
    public $name;
    public $contact_info;
    public $address;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Read Suppliers
    public function read()
    {
        $query = 'SELECT * FROM ' . $this->table . ' ORDER BY name ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get Single Supplier
    public function read_single()
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE id = ? LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->contact_info = $row['contact_info'];
            $this->address = $row['address'];
            return true;
        }
        return false;
    }

    // Create Supplier
    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' 
                  SET name = :name, 
                      contact_info = :contact_info, 
                      address = :address';

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->contact_info = htmlspecialchars(strip_tags($this->contact_info));
        $this->address = htmlspecialchars(strip_tags($this->address));

        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':contact_info', $this->contact_info);
        $stmt->bindParam(':address', $this->address);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update Supplier
    public function update()
    {
        $query = 'UPDATE ' . $this->table . ' 
                  SET name = :name, 
                      contact_info = :contact_info, 
                      address = :address 
                  WHERE id = :id';

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->contact_info = htmlspecialchars(strip_tags($this->contact_info));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':contact_info', $this->contact_info);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Count Products associated with this Supplier
    public function countProducts()
    {
        $query = 'SELECT COUNT(*) as total FROM products WHERE supplier_id = :supplier_id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':supplier_id', $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Delete Supplier
    public function delete()
    {
        // Check if supplier has associated products
        $productCount = $this->countProducts();

        if ($productCount > 0) {
            // Return false with product count info
            return ['success' => false, 'product_count' => $productCount];
        }

        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'product_count' => 0];
    }
}
