<?php

namespace App\Models;

use PDO;

class User
{
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    public $password;
    public $role;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Read all users
    public function read()
    {
        $query = 'SELECT id, username, role, created_at FROM ' . $this->table . ' ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single user
    public function read_single()
    {
        $query = 'SELECT id, username, role, created_at FROM ' . $this->table . ' WHERE id = ? LIMIT 0,1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->username = $row['username'];
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            return $row;
        }

        return null;
    }

    // Create user
    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' (username, password_hash, role) VALUES (:username, :password, :role)';
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->role = htmlspecialchars(strip_tags($this->role));
        // Password should be hashed before setting property or here

        // Bind
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update user
    public function update()
    {
        // Check if password is being updated
        if (!empty($this->password)) {
            $query = 'UPDATE ' . $this->table . '
                      SET username = :username,
                          password_hash = :password,
                          role = :role
                      WHERE id = :id';
        } else {
            $query = 'UPDATE ' . $this->table . '
                      SET username = :username,
                          role = :role
                      WHERE id = :id';
        }

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':id', $this->id);

        if (!empty($this->password)) {
            $stmt->bindParam(':password', $this->password);
        }

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete user
    public function delete()
    {
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if username exists (for creation)
    public function usernameExists()
    {
        $query = 'SELECT id FROM ' . $this->table . ' WHERE username = :username LIMIT 0,1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
}
