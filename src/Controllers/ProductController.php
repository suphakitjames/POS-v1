<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\Product;

class ProductController
{
    private $db;
    private $product;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->product = new Product($this->db);
    }

    public function index()
    {
        $result = $this->product->read();
        return $result->fetchAll();
    }
}
