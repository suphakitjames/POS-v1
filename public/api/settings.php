<?php
session_start();
require_once '../../src/Config/Database.php';
require_once '../../src/Helpers/functions.php';

use App\Config\Database;

// Clean output buffer
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Check Authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        die(json_encode(['success' => false, 'message' => 'Unauthorized']));
    }

    $db = new Database();
    $conn = $db->connect();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all settings
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        echo json_encode(['success' => true, 'data' => $settings]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update settings
        if (empty($_POST)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'No data received']));
        }

        $conn->beginTransaction();

        try {
            foreach ($_POST as $key => $value) {
                $stmt = $conn->prepare("
                    INSERT INTO settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ");
                $stmt->execute([$key, $value, $value]);
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว']);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
