<?php

namespace App\Models;

use PDO;

class LoginAttempt
{
    private $conn;
    private $table = 'login_attempts';

    public $id;
    public $username;
    public $ip_address;
    public $is_successful;
    public $attempted_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Record a login attempt
     * @return bool
     */
    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' 
                  (username, ip_address, is_successful) 
                  VALUES (:username, :ip_address, :is_successful)';

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->ip_address = htmlspecialchars(strip_tags($this->ip_address));

        // Bind
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':ip_address', $this->ip_address);
        $stmt->bindParam(':is_successful', $this->is_successful, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Get recent failed login attempts for a specific username
     * @param string $username
     * @param int $hours - Time window in hours
     * @return array
     */
    public function getRecentFailures($username, $hours = 1)
    {
        $query = 'SELECT * FROM ' . $this->table . ' 
                  WHERE username = :username 
                  AND is_successful = FALSE 
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                  ORDER BY attempted_at DESC';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':hours', $hours, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get failed login attempts count for a username within time window
     * @param string $username
     * @param int $hours
     * @return int
     */
    public function getFailureCount($username, $hours = 1)
    {
        $query = 'SELECT COUNT(*) as count FROM ' . $this->table . ' 
                  WHERE username = :username 
                  AND is_successful = FALSE 
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':hours', $hours, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    /**
     * Get suspicious IPs (multiple failed attempts)
     * @param int $threshold - Minimum number of failures to be considered suspicious
     * @param int $hours - Time window
     * @return array
     */
    public function getSuspiciousIPs($threshold = 3, $hours = 1)
    {
        $query = 'SELECT ip_address, COUNT(*) as fail_count 
                  FROM ' . $this->table . ' 
                  WHERE is_successful = FALSE 
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                  GROUP BY ip_address 
                  HAVING fail_count >= :threshold
                  ORDER BY fail_count DESC';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->bindParam(':hours', $hours, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete old login attempts (cleanup)
     * @param int $days - Delete records older than this many days
     * @return bool
     */
    public function deleteOld($days = 90)
    {
        $query = 'DELETE FROM ' . $this->table . ' 
                  WHERE attempted_at < DATE_SUB(NOW(), INTERVAL :days DAY)';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
