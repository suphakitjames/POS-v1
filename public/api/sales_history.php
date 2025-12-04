<?php
session_start();
require_once '../../src/Helpers/functions.php';
require_once '../../src/Config/Database.php';

use App\Config\Database;

// Check Authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();

    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        // ดึงรายการขายทั้งหมด
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = ($page - 1) * $limit;

        // Filters
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $paymentMethod = $_GET['payment_method'] ?? null;
        $userId = $_GET['user_id'] ?? null;

        // Build query
        $where = [];
        $params = [];

        if ($dateFrom) {
            $where[] = "DATE(s.sale_date) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(s.sale_date) <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        if ($paymentMethod && $paymentMethod !== 'all') {
            $where[] = "s.payment_method = :payment_method";
            $params[':payment_method'] = $paymentMethod;
        }

        if ($userId && $userId !== 'all') {
            $where[] = "s.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countQuery = "SELECT COUNT(*) as total FROM sales s $whereClause";
        $countStmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get data
        $query = "SELECT s.*, u.username,
                         (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
                  FROM sales s
                  LEFT JOIN users u ON s.user_id = u.id
                  $whereClause
                  ORDER BY s.sale_date DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $sales,
            'pagination' => [
                'total' => intval($total),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } elseif ($action === 'detail') {
        // ดึงรายละเอียดบิลเดียว
        $saleId = $_GET['id'] ?? null;

        if (!$saleId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ไม่พบ ID']);
            exit;
        }

        // Get sale header
        $query = "SELECT s.*, u.username 
                  FROM sales s
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE s.id = :sale_id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':sale_id', $saleId);
        $stmt->execute();
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sale) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลการขาย']);
            exit;
        }

        // Get sale items
        $itemQuery = "SELECT si.*, p.name as product_name, p.sku
                     FROM sale_items si
                     LEFT JOIN products p ON si.product_id = p.id
                     WHERE si.sale_id = :sale_id";

        $itemStmt = $conn->prepare($itemQuery);
        $itemStmt->bindParam(':sale_id', $saleId);
        $itemStmt->execute();
        $sale['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $sale
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
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
