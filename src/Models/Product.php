<?php

namespace App\Models;

use PDO;

class Product
{
    private $conn;
    private $table = 'products';

    public $id;
    public $sku;
    public $barcode;
    public $name;
    public $description;
    public $image_path;
    public $category_id;
    public $cost_price;
    public $selling_price;
    public $stock_quantity;
    public $reorder_point;
    public $expire_date;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all products with category name
    public function read()
    {
        $query = 'SELECT 
                    p.*, 
                    c.name as category_name 
                  FROM ' . $this->table . ' p
                  LEFT JOIN categories c ON p.category_id = c.id
                  ORDER BY p.created_at DESC';

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Get single product
    public function read_single()
    {
        $query = 'SELECT 
                    p.*, 
                    c.name as category_name 
                  FROM ' . $this->table . ' p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = ?
                  LIMIT 0,1';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create product
    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' 
                  SET sku = :sku,
                      barcode = :barcode,
                      name = :name,
                      description = :description,
                      image_path = :image_path,
                      category_id = :category_id,
                      cost_price = :cost_price,
                      selling_price = :selling_price,
                      stock_quantity = :stock_quantity,
                      reorder_point = :reorder_point,
                      expire_date = :expire_date';

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':sku', $this->sku);
        $stmt->bindParam(':barcode', $this->barcode);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':image_path', $this->image_path);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':cost_price', $this->cost_price);
        $stmt->bindParam(':selling_price', $this->selling_price);
        $stmt->bindParam(':stock_quantity', $this->stock_quantity);
        $stmt->bindParam(':reorder_point', $this->reorder_point);
        $stmt->bindParam(':expire_date', $this->expire_date);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Update product
    public function update()
    {
        $query = 'UPDATE ' . $this->table . ' 
                  SET sku = :sku,
                      barcode = :barcode,
                      name = :name,
                      description = :description,
                      image_path = :image_path,
                      category_id = :category_id,
                      cost_price = :cost_price,
                      selling_price = :selling_price,
                      stock_quantity = :stock_quantity,
                      reorder_point = :reorder_point,
                      expire_date = :expire_date
                  WHERE id = :id';

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':sku', $this->sku);
        $stmt->bindParam(':barcode', $this->barcode);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':image_path', $this->image_path);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':cost_price', $this->cost_price);
        $stmt->bindParam(':selling_price', $this->selling_price);
        $stmt->bindParam(':stock_quantity', $this->stock_quantity);
        $stmt->bindParam(':reorder_point', $this->reorder_point);
        $stmt->bindParam(':expire_date', $this->expire_date);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete product
    public function delete()
    {
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get all categories
    public function getCategories()
    {
        $query = 'SELECT * FROM categories ORDER BY name ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get Low Stock Products
    public function getLowStock($limit = 5)
    {
        $query = 'SELECT * FROM ' . $this->table . ' 
                  WHERE stock_quantity <= reorder_point 
                  ORDER BY stock_quantity ASC 
                  LIMIT :limit';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get Expiring Soon Products
    public function getExpiringSoon($days = 30)
    {
        $query = 'SELECT * FROM ' . $this->table . ' 
                  WHERE expire_date IS NOT NULL 
                  AND expire_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  ORDER BY expire_date ASC';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
