<?php
/**
 * API xử lý trồng cây sau thanh toán
 * Lưu vị trí các cây vào database
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$orderCode = trim($data['order_code'] ?? '');
$cells = $data['cells'] ?? [];

if (!$orderCode || empty($cells)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();
    
    // Lấy thông tin đơn hàng
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = :code AND user_id = :uid AND status = :status');
    $stmt->execute([
        ':code' => $orderCode,
        ':uid' => $_SESSION['user_id'],
        ':status' => 'paid'
    ]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Đơn hàng không hợp lệ']);
        exit;
    }
    
    // Lấy danh sách sản phẩm trong đơn hàng
    $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :oid');
    $stmt->execute([':oid' => $order['order_id']]);
    $orderItems = $stmt->fetchAll();
    
    // Tính tổng số cây
    $totalTrees = 0;
    foreach ($orderItems as $item) {
        $totalTrees += $item['quantity'];
    }
    
    // Kiểm tra số lượng ô đã chọn
    if (count($cells) !== $totalTrees) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => "Số ô đã chọn ({count($cells)}) không khớp với số cây đã mua ($totalTrees)"
        ]);
        exit;
    }
    
    // Kiểm tra xem đơn hàng đã trồng cây chưa
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM tree_plantings WHERE order_id = :oid');
    $stmt->execute([':oid' => $order['order_id']]);
    $existing = $stmt->fetch();
    
    if ($existing['count'] > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Đơn hàng này đã trồng cây rồi']);
        exit;
    }
    
    // Phân bổ cây vào các ô
    $cellIndex = 0;
    foreach ($orderItems as $item) {
        for ($i = 0; $i < $item['quantity']; $i++) {
            if ($cellIndex >= count($cells)) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Lỗi phân bổ cây']);
                exit;
            }
            
            $cell = $cells[$cellIndex];
            
            // Kiểm tra ô đã được sử dụng chưa
            $stmt = $pdo->prepare('SELECT id FROM tree_plantings WHERE land_id = :lid AND grid_row = :row AND grid_col = :col');
            $stmt->execute([
                ':lid' => $cell['land_id'],
                ':row' => $cell['row'],
                ':col' => $cell['col']
            ]);
            
            if ($stmt->fetch()) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => "Ô [{$cell['row']},{$cell['col']}] đã có cây. Vui lòng chọn ô khác."
                ]);
                exit;
            }
            
            // Lưu thông tin cây
            $stmt = $pdo->prepare('
                INSERT INTO tree_plantings 
                (order_id, order_item_id, land_id, user_id, product_id, product_name, grid_row, grid_col, center_lat, center_lng)
                VALUES (:oid, :oiid, :lid, :uid, :pid, :pname, :row, :col, :lat, :lng)
            ');
            
            $stmt->execute([
                ':oid' => $order['order_id'],
                ':oiid' => $item['item_id'],
                ':lid' => $cell['land_id'],
                ':uid' => $_SESSION['user_id'],
                ':pid' => $item['product_id'],
                ':pname' => $item['product_name'],
                ':row' => $cell['row'],
                ':col' => $cell['col'],
                ':lat' => $cell['center_lat'],
                ':lng' => $cell['center_lng']
            ]);
            
            $cellIndex++;
        }
    }
    
    // Cập nhật trạng thái đơn hàng thành 'planted'
    $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE order_id = :oid');
    $stmt->execute([
        ':status' => 'planted',
        ':oid' => $order['order_id']
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Trồng cây thành công',
        'trees_planted' => $totalTrees
    ]);
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
