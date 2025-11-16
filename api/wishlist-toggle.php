<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào yêu thích']);
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

// Validate input
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
    exit;
}

try {
    $pdo = getPDO();
    $userId = $_SESSION['user_id'];
    
    // Check if product exists
    $stmt = $pdo->prepare('SELECT product_id FROM products WHERE product_id = :product_id');
    $stmt->execute(['product_id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }
    
    // Check if item already exists in wishlist
    $stmt = $pdo->prepare('SELECT wishlist_id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id');
    $stmt->execute([
        'user_id' => $userId,
        'product_id' => $productId
    ]);
    $existingItem = $stmt->fetch();
    
    if ($existingItem) {
        // Remove from wishlist
        $stmt = $pdo->prepare('DELETE FROM wishlist WHERE wishlist_id = :wishlist_id AND user_id = :user_id');
        $stmt->execute([
            'wishlist_id' => $existingItem['wishlist_id'],
            'user_id' => $userId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi yêu thích',
            'is_favorite' => false
        ]);
    } else {
        // Add to wishlist
        $stmt = $pdo->prepare('INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)');
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào yêu thích',
            'is_favorite' => true
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

