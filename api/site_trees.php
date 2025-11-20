<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
header('Content-Type: application/json; charset=utf-8');

$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
if (!$site_id) {
    echo json_encode(['success' => false, 'error' => 'Missing site_id']);
    exit;
}

try {
    $pdo = getPDO();
    $sql = "SELECT p.id AS planting_id, p.lat, p.lng, p.planted_at,
                   prod.product_id, prod.name AS product_name, prod.category_id AS product_category_id, prod.price AS product_price,
                   u.user_id AS user_id, u.full_name AS user_name
            FROM plantings p
            JOIN products prod ON prod.product_id = p.product_id
            JOIN users u ON u.user_id = p.user_id
            WHERE p.site_id = :sid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':sid' => $site_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


