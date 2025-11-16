<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

try {
    $pdo = getPDO();
    $userId = $_SESSION['user_id'];
    
    // Get total items in cart
    $stmt = $pdo->prepare('SELECT SUM(quantity) as total FROM cart WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    $result = $stmt->fetch();
    
    $count = $result['total'] ? (int)$result['total'] : 0;
    
    echo json_encode(['success' => true, 'count' => $count]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => $e->getMessage()]);
}

