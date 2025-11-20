<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

requireLogin();

$orderCode = $_GET['order_code'] ?? '';
$order = null;

if ($orderCode) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = :code AND user_id = :uid LIMIT 1');
        $stmt->execute([':code' => $orderCode, ':uid' => $_SESSION['user_id']]);
        $order = $stmt->fetch();
    } catch (Exception $e) {}
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .success-container { max-width: 600px; margin: 80px auto; padding: 20px; text-align: center; }
    .success-icon { font-size: 80px; color: #28a745; margin-bottom: 20px; }
    .success-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 15px; }
    .order-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left; }
    .info-row { display: flex; justify-content: space-between; margin: 10px 0; }
    .btn { display: inline-block; padding: 12px 24px; margin: 10px; border-radius: 6px; text-decoration: none; font-weight: 600; }
    .btn-primary { background: var(--primary); color: white; }
</style>

<div class="success-container">
    <div class="success-icon">✓</div>
    <h1 class="success-title">Đơn hàng đã được tạo thành công!</h1>
    <p>Vui lòng chuyển khoản theo thông tin SePay đã cung cấp.</p>
    
    <?php if ($order): ?>
    <div class="order-info">
        <div class="info-row">
            <span>Mã đơn hàng:</span>
            <strong><?php echo htmlspecialchars($order['order_code']); ?></strong>
        </div>
        <div class="info-row">
            <span>Tổng tiền:</span>
            <strong><?php echo number_format($order['final_amount'] ?? $order['total_amount'], 0, ',', '.'); ?> đ</strong>
        </div>
        <div class="info-row">
            <span>Trạng thái:</span>
            <strong><?php echo $order['status'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán'; ?></strong>
        </div>
    </div>
    <?php endif; ?>
    
    <a href="<?php echo BASE_URL; ?>/auth/orders.php" class="btn btn-primary">Xem đơn hàng của tôi</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
