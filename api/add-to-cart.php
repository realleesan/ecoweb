<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

// Validate input
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
    exit;
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Số lượng phải lớn hơn 0']);
    exit;
}

try {
    $pdo = getPDO();
    $userId = $_SESSION['user_id'];
    
    // Check if product exists
    $stmt = $pdo->prepare('SELECT product_id, name, price, stock FROM products WHERE product_id = :product_id');
    $stmt->execute(['product_id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }
    
    // Check stock availability
    if ($product['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
        exit;
    }
    
    // Check if item already exists in cart
    $stmt = $pdo->prepare('SELECT cart_id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id');
    $stmt->execute([
        'user_id' => $userId,
        'product_id' => $productId
    ]);
    $existingItem = $stmt->fetch();
    
    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        
        // Check stock limit
        if ($newQuantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho. Tồn kho hiện tại: ' . $product['stock']]);
            exit;
        }
        
        $stmt = $pdo->prepare('UPDATE cart SET quantity = :quantity WHERE cart_id = :cart_id');
        $stmt->execute([
            'quantity' => $newQuantity,
            'cart_id' => $existingItem['cart_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật số lượng sản phẩm trong giỏ hàng',
            'quantity' => $newQuantity
        ]);
    } else {
        // Check stock limit for new item
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho. Tồn kho hiện tại: ' . $product['stock']]);
            exit;
        }
        
        // Insert new item
        $stmt = $pdo->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)');
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'quantity' => $quantity
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

