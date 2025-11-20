<?php // php c:\xampp\htdocs\ecoweb\api\create-order.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/coupon.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

function safeRollback(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']); exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$addressId = (int)($data['shipping_address_id'] ?? 0);
$paymentMethod = trim($data['payment_method'] ?? 'bank_transfer');
$couponCode = strtoupper(trim($data['coupon_code'] ?? ''));
$note = trim($data['note'] ?? '');

try {
    $pdo = getPDO();
    ensureOrderCouponColumns($pdo);
    if (!coupon_order_columns_available($pdo)) {
        echo json_encode([
            'success' => false,
            'message' => 'Bảng "orders" chưa được cập nhật để lưu thông tin mã giảm giá. Vui lòng chạy lại script cập nhật cơ sở dữ liệu.'
        ]);
        exit;
    }

    ensureCouponRedemptionTable($pdo);

    $pdo->beginTransaction();

    // Validate address
    $stmt = $pdo->prepare('SELECT address_id FROM user_addresses WHERE address_id = :aid AND user_id = :uid');
    $stmt->execute([':aid' => $addressId, ':uid' => $_SESSION['user_id']]);
    $addr = $stmt->fetch();
    if (!$addr) { safeRollback($pdo); echo json_encode(['success' => false, 'message' => 'Địa chỉ giao hàng không hợp lệ']); exit; }

    // Load cart
    $stmt = $pdo->prepare('SELECT c.quantity, p.product_id, p.name, p.price FROM cart c INNER JOIN products p ON c.product_id = p.product_id WHERE c.user_id = :uid');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $items = $stmt->fetchAll();
    if (!$items) { safeRollback($pdo); echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']); exit; }

    $subtotal = 0.0;
    foreach ($items as $it) { $subtotal += $it['price'] * $it['quantity']; }

    $discountAmount = 0.0;
    $finalAmount = $subtotal;
    $couponRow = null;

    if ($couponCode !== '') {
        $couponRow = fetchCouponByCode($pdo, $couponCode, true);
        if (!$couponRow) {
            safeRollback($pdo);
            echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại.']);
            exit;
        }

        try {
            $discountInfo = calculateCouponDiscount($pdo, $couponRow, (int) $_SESSION['user_id'], (float) $subtotal);
            $discountAmount = $discountInfo['discount_amount'];
            $finalAmount = $discountInfo['final_amount'];
        } catch (RuntimeException $couponError) {
            safeRollback($pdo);
            echo json_encode(['success' => false, 'message' => $couponError->getMessage()]);
            exit;
        }
    }

    $discountAmount = round($discountAmount, 2);
    $finalAmount = round($finalAmount, 2);

    // Generate order code
    $rand = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $orderCode = 'HBN' . date('Ymd') . $rand;

    // Insert order
    $stmt = $pdo->prepare('INSERT INTO orders (order_code, user_id, total_amount, coupon_code, coupon_id, discount_amount, final_amount, payment_method, status, note, shipping_address_id) VALUES (:code, :uid, :total, :coupon_code, :coupon_id, :discount_amount, :final_amount, :method, :status, :note, :addr)');
    $stmt->execute([
        ':code' => $orderCode,
        ':uid' => $_SESSION['user_id'],
        ':total' => $subtotal,
        ':coupon_code' => $couponRow ? $couponRow['coupon_code'] : null,
        ':coupon_id' => $couponRow ? (int) $couponRow['coupon_id'] : null,
        ':discount_amount' => $discountAmount,
        ':final_amount' => $finalAmount,
        ':method' => 'bank_transfer',
        ':status' => 'pending',
        ':note' => $note !== '' ? $note : null,
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

    if ($couponRow) {
        try {
            $updateCoupon = $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = :cid');
            $updateCoupon->execute([':cid' => (int) $couponRow['coupon_id']]);

            ensureCouponRedemptionTable($pdo);
            $redeemStmt = $pdo->prepare('INSERT INTO coupon_redemptions (coupon_id, user_id, order_id) VALUES (:cid, :uid, :oid)');
            $redeemStmt->execute([
                ':cid' => (int) $couponRow['coupon_id'],
                ':uid' => (int) $_SESSION['user_id'],
                ':oid' => $orderId,
            ]);
        } catch (Throwable $e) {
            // If redemption logging fails, roll back to avoid inconsistent state
            safeRollback($pdo);
            echo json_encode(['success' => false, 'message' => 'Không thể ghi nhận sử dụng mã giảm giá.']);
            exit;
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'order_code' => $orderCode,
        'discount_amount' => $discountAmount,
        'final_amount' => $finalAmount,
        'coupon_code_applied' => $couponRow ? $couponRow['coupon_code'] : null,
    ]);
} catch (Exception $e) {
    if ($pdo) {
        safeRollback($pdo);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Không thể tạo đơn hàng: ' . $e->getMessage(),
    ]);
}