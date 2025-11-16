<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$cartId = isset($input['cart_id']) ? (int)$input['cart_id'] : 0;

// Validate input
if ($cartId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID giỏ hàng không hợp lệ']);
    exit;
}

try {
    $pdo = getPDO();
    $userId = $_SESSION['user_id'];
    
    // Delete cart item
    $stmt = $pdo->prepare('DELETE FROM cart WHERE cart_id = :cart_id AND user_id = :user_id');
    $stmt->execute([
        'cart_id' => $cartId,
        'user_id' => $userId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ hàng']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

