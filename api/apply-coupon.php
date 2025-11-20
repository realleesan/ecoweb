<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/coupon.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

requireLogin();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập trước khi áp dụng mã giảm giá.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$couponCode = isset($data['coupon_code']) ? strtoupper(trim((string) $data['coupon_code'])) : '';

if ($couponCode === '') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá.']);
    exit;
}

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT c.quantity, p.price FROM cart c INNER JOIN products p ON c.product_id = p.product_id WHERE c.user_id = :uid');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$cartItems) {
        echo json_encode(['success' => false, 'message' => 'Giỏ hàng đang trống, không thể áp dụng mã.']);
        exit;
    }

    $subtotal = 0.0;
    foreach ($cartItems as $item) {
        $subtotal += (float) $item['price'] * (int) $item['quantity'];
    }

    $coupon = fetchCouponByCode($pdo, $couponCode, true);
    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại.']);
        exit;
    }

    $discountInfo = calculateCouponDiscount($pdo, $coupon, (int) $_SESSION['user_id'], $subtotal);

    echo json_encode([
        'success' => true,
        'coupon_code' => $coupon['coupon_code'],
        'discount_amount' => $discountInfo['discount_amount'],
        'final_amount' => $discountInfo['final_amount'],
        'preview' => buildCouponPreview($coupon, $discountInfo['discount_amount'], $discountInfo['final_amount']),
    ]);
} catch (RuntimeException $ex) {
    echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Không thể áp dụng mã giảm giá.']);
}

