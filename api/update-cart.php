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
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

// Validate input
if ($cartId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID giỏ hàng không hợp lệ']);
    exit;
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Số lượng phải lớn hơn 0']);
    exit;
}

try {
    $pdo = getPDO();
    $userId = $_SESSION['user_id'];
    
    // Check if cart item belongs to user
    $stmt = $pdo->prepare('
        SELECT c.cart_id, c.quantity, p.stock 
        FROM cart c
        INNER JOIN products p ON c.product_id = p.product_id
        WHERE c.cart_id = :cart_id AND c.user_id = :user_id
    ');
    $stmt->execute([
        'cart_id' => $cartId,
        'user_id' => $userId
    ]);
    $cartItem = $stmt->fetch();
    
    if (!$cartItem) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng']);
        exit;
    }
    
    // Check stock availability
    if ($quantity > $cartItem['stock']) {
        echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho. Tồn kho hiện tại: ' . $cartItem['stock']]);
        exit;
    }
    
    // Update quantity
    $stmt = $pdo->prepare('UPDATE cart SET quantity = :quantity WHERE cart_id = :cart_id AND user_id = :user_id');
    $stmt->execute([
        'quantity' => $quantity,
        'cart_id' => $cartId,
        'user_id' => $userId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Đã cập nhật số lượng']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

