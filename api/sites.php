<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT id, name, center_lat, center_lng, bbox_lat1, bbox_lng1, bbox_lat2, bbox_lng2 FROM sites");
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $sites]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


