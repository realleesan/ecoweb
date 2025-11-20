<?php
/**
 * API kiểm tra trạng thái đơn hàng
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$orderCode = trim($data['order_code'] ?? '');

if (!$orderCode) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã đơn hàng']);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT status FROM orders WHERE order_code = :code AND user_id = :uid LIMIT 1');
    $stmt->execute([':code' => $orderCode, ':uid' => $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'status' => $order['status']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
