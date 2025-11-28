<?php

namespace App\Models;

use PDO;

class Notification
{
    private $conn;
    private $table = 'notifications';

    public $id;
    public $user_id;
    public $type;
    public $title;
    public $message;
    public $related_id;
    public $is_read;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Create a new notification
     * @return bool
     */
    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' 
                  (user_id, type, title, message, related_id) 
                  VALUES (:user_id, :type, :title, :message, :related_id)';

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->message = htmlspecialchars(strip_tags($this->message));

        // Bind
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':related_id', $this->related_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Read all notifications for a specific user
     * @param int $user_id
     * @param int $limit
     * @return PDOStatement
     */
    public function read($user_id, $limit = 10)
    {
        $query = 'SELECT * FROM ' . $this->table . ' 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Read unread notifications for a specific user
     * @param int $user_id
     * @param int $limit
     * @return PDOStatement
     */
    public function readUnread($user_id, $limit = 10)
    {
        $query = 'SELECT * FROM ' . $this->table . ' 
                  WHERE user_id = :user_id AND is_read = FALSE 
                  ORDER BY created_at DESC 
                  LIMIT :limit';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Get unread notification count
     * @param int $user_id
     * @return int
     */
    public function getUnreadCount($user_id)
    {
        $query = 'SELECT COUNT(*) as count FROM ' . $this->table . ' 
                  WHERE user_id = :user_id AND is_read = FALSE';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    /**
     * Mark a single notification as read
     * @param int $id
     * @return bool
     */
    public function markAsRead($id)
    {
        $query = 'UPDATE ' . $this->table . ' 
                  SET is_read = TRUE 
                  WHERE id = :id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Mark all notifications as read for a user
     * @param int $user_id
     * @return bool
     */
    public function markAllAsRead($user_id)
    {
        $query = 'UPDATE ' . $this->table . ' 
                  SET is_read = TRUE 
                  WHERE user_id = :user_id AND is_read = FALSE';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Delete old read notifications (older than specified days)
     * @param int $days
     * @return bool
     */
    public function deleteOld($days = 30)
    {
        $query = 'DELETE FROM ' . $this->table . ' 
                  WHERE is_read = TRUE 
                  AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Check if notification already exists (to prevent duplicates)
     * @param int $user_id
     * @param string $type
     * @param int $related_id
     * @return bool
     */
    public function exists($user_id, $type, $related_id)
    {
        $query = 'SELECT id FROM ' . $this->table . ' 
                  WHERE user_id = :user_id 
                  AND type = :type 
                  AND related_id = :related_id 
                  AND is_read = FALSE 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':related_id', $related_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
}
