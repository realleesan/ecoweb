<?php
/**
 * API lấy tất cả cây đã trồng của mọi người
 * Để hiển thị trên bản đồ phủ xanh
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
    
    // Lấy tất cả cây đã trồng với thông tin chi tiết
    $stmt = $pdo->query("
        SELECT 
            tp.id,
            tp.center_lat,
            tp.center_lng,
            tp.grid_row,
            tp.grid_col,
            tp.planted_at,
            tp.product_name,
            l.name AS land_name,
            l.id AS land_id,
            u.full_name AS user_name,
            u.username,
            o.order_code
        FROM tree_plantings tp
        INNER JOIN lands l ON tp.land_id = l.id
        LEFT JOIN users u ON tp.user_id = u.user_id
        LEFT JOIN orders o ON tp.order_id = o.order_id
        WHERE l.status = 'approved'
        ORDER BY tp.planted_at DESC
    ");
    
    $trees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => count($trees),
        'data' => $trees
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
