<?php
/**
 * API lấy QR code thanh toán từ SePay
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/sepay_config.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$orderCode = trim($data['order_code'] ?? '');

if (!$orderCode) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã đơn hàng']);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = :code AND user_id = :uid LIMIT 1');
    $stmt->execute([':code' => $orderCode, ':uid' => $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }
    
    if ($order['status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng đã được thanh toán']);
        exit;
    }
    
    $amount = (int)($order['final_amount'] ?? $order['total_amount']);
    
    // Tạo QR code sử dụng VietQR API
    // Format: https://img.vietqr.io/image/BANK_CODE-ACCOUNT_NUMBER-TEMPLATE.jpg?amount=AMOUNT&addInfo=CONTENT
    
    if (defined('SEPAY_BANK_CODE') && defined('SEPAY_ACCOUNT_NUMBER')) {
        // Sử dụng VietQR API (chuẩn ngân hàng Việt Nam)
        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.jpg?amount=%d&addInfo=%s&accountName=%s',
            SEPAY_BANK_CODE,
            SEPAY_ACCOUNT_NUMBER,
            $amount,
            urlencode($orderCode),
            urlencode(defined('SEPAY_ACCOUNT_NAME') ? SEPAY_ACCOUNT_NAME : 'SEPAY')
        );
    } else {
        // Fallback: Sử dụng Google Chart API để tạo QR từ text
        $qrText = sprintf(
            'Chuyen khoan %d VND den %s. Noi dung: %s',
            $amount,
            SEPAY_MERCHANT_ID,
            $orderCode
        );
        $qrUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrText);
    }
    
    echo json_encode([
        'success' => true,
        'qr_url' => $qrUrl,
        'order_code' => $orderCode,
        'amount' => $amount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
