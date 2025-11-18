<?php // php c:\xampp\htdocs\ecoweb\api\create-order.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']); exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để đặt hàng']); exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$addressId = (int)($data['shipping_address_id'] ?? 0);
$paymentMethod = trim($data['payment_method'] ?? 'bank_transfer');
$couponCode = trim($data['coupon_code'] ?? '');
$note = trim($data['note'] ?? '');

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // Validate address
    $stmt = $pdo->prepare('SELECT address_id FROM user_addresses WHERE address_id = :aid AND user_id = :uid');
    $stmt->execute([':aid' => $addressId, ':uid' => $_SESSION['user_id']]);
    $addr = $stmt->fetch();
    if (!$addr) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'Địa chỉ giao hàng không hợp lệ']); exit; }

    // Load cart
    $stmt = $pdo->prepare('SELECT c.quantity, p.product_id, p.name, p.price FROM cart c INNER JOIN products p ON c.product_id = p.product_id WHERE c.user_id = :uid');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $items = $stmt->fetchAll();
    if (!$items) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']); exit; }

    $total = 0;
    foreach ($items as $it) { $total += $it['price'] * $it['quantity']; }

    // Generate order code
    $rand = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $orderCode = 'HBN' . date('Ymd') . $rand;

    // Insert order
    $stmt = $pdo->prepare('INSERT INTO orders (order_code, user_id, total_amount, payment_method, status, note, shipping_address_id) VALUES (:code, :uid, :total, :method, :status, :note, :addr)');
    $stmt->execute([
        ':code' => $orderCode,
        ':uid' => $_SESSION['user_id'],
        ':total' => $total,
        ':method' => 'bank_transfer',
        ':status' => 'pending',
        ':note' => $note,
        ':addr' => $addressId
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // Insert order items
    $oi = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, total_price) VALUES (:oid, :pid, :name, :price, :qty, :total)');
    foreach ($items as $it) {
        $oi->execute([
            ':oid' => $orderId,
            ':pid' => $it['product_id'],
            ':name' => $it['name'],
            ':price' => $it['price'],
            ':qty' => $it['quantity'],
            ':total' => $it['price'] * $it['quantity']
        ]);
    }

    // Clear cart
    $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = :uid');
    $stmt->execute([':uid' => $_SESSION['user_id']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'order_code' => $orderCode]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'message' => 'Không thể tạo đơn hàng.']);
}