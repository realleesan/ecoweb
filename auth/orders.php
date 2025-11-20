<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/database.php';

requireLogin();

$orders = [];
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT order_id, order_code, total_amount, status, created_at FROM orders WHERE user_id = :uid ORDER BY created_at DESC');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) { $orders = []; }

include '../includes/header.php';
?>
<style>
    body { background-color: var(--light); }
    .account-container { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 40px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; }
    .account-content { display: grid; grid-template-columns: 280px 1fr; gap: <?php echo GRID_GAP; ?>; align-items: start; }
    .account-main { background-color: var(--white); border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
    .account-main-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid var(--light); }
    .account-main-header h1 { font-size: 26px; color: var(--primary); margin-bottom: 8px; }
    .order-card { border:1px solid #e0e0e0; border-radius:10px; padding:14px; margin-bottom:12px; }
    .order-row { display:flex; justify-content: space-between; align-items:center; gap:10px; }
    .order-meta { color:#777; font-size: 13px; }
    .order-total { color: var(--secondary); font-weight:700; }
    .status { padding:6px 10px; border-radius: 20px; font-size: 12px; font-weight:600; }
    .st-pending { background:#fff5f0; color:#a64b2a; border:1px solid #ffd8c2; }
    .st-paid { background:#e9f7ef; color:#2e7d32; border:1px solid #c8e6c9; }
    .st-planted { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .st-cancelled { background:#fee; color:#c33; border:1px solid #fcc; }
    .order-actions { display:flex; gap:8px; }
    .btn { padding:8px 12px; border-radius:8px; border:none; cursor:pointer; font-weight:600; text-decoration:none; display:inline-block; }
    .btn-primary { background: var(--primary); color: var(--white); }
    .btn-secondary { background: var(--secondary); color: var(--white); }
    .empty { text-align:center; padding:60px 20px; color:#999; }
</style>

<div class="account-container">
    <div class="account-content">
        <aside>
            <?php include __DIR__ . '/sidebar-account.php'; ?>
        </aside>
        <section class="account-main">
            <div class="account-main-header">
                <h1>Đơn hàng của tôi</h1>
                <p>Xem lịch sử các đơn hàng đã đặt</p>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty">
                    <i class="fas fa-box-open" style="font-size:48px; color:#ddd;"></i>
                    <p>Bạn chưa có đơn hàng nào.</p>
                    <a href="<?php echo BASE_URL; ?>/public/products.php" class="btn btn-primary">Bắt đầu mua sắm</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="order-card">
                        <div class="order-row">
                            <div>
                                <div style="font-weight:700; color:var(--dark)">Mã đơn: <?php echo htmlspecialchars($o['order_code']); ?></div>
                                <div class="order-meta">Ngày đặt: <?php echo date(DATETIME_FORMAT, strtotime($o['created_at'])); ?></div>
                            </div>
                            <div class="order-total"><?php echo number_format($o['total_amount'], 0, ',', '.'); ?> đ</div>
                        </div>
                        <div class="order-row" style="margin-top:10px;">
                            <?php
                                $status = $o['status'];
                                if ($status === 'planted') {
                                    $stClass = 'st-planted';
                                    $stLabel = '🌳 Đã trồng cây';
                                } elseif ($status === 'paid') {
                                    $stClass = 'st-paid';
                                    $stLabel = '✓ Đã thanh toán';
                                } elseif ($status === 'cancelled') {
                                    $stClass = 'st-cancelled';
                                    $stLabel = 'Đã hủy';
                                } else {
                                    $stClass = 'st-pending';
                                    $stLabel = 'Đang chờ thanh toán';
                                }
                            ?>
                            <span class="status <?php echo $stClass; ?>"><?php echo $stLabel; ?></span>
                            <div class="order-actions">
                                <?php if ($status === 'planted'): ?>
                                    <a href="<?php echo BASE_URL; ?>/auth/my-trees.php" class="btn btn-primary">Xem cây đã trồng</a>
                                <?php elseif ($status === 'paid'): ?>
                                    <a href="<?php echo BASE_URL; ?>/payment/map.php?order_code=<?php echo urlencode($o['order_code']); ?>" class="btn btn-primary">Trồng cây ngay</a>
                                <?php elseif ($status === 'pending'): ?>
                                    <a href="<?php echo BASE_URL; ?>/payment/payment.php?order_code=<?php echo urlencode($o['order_code']); ?>" class="btn btn-secondary">Chi tiết</a>
                                    <a href="<?php echo BASE_URL; ?>/payment/payment.php?order_code=<?php echo urlencode($o['order_code']); ?>" class="btn btn-primary">Thanh toán ngay</a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>/payment/payment.php?order_code=<?php echo urlencode($o['order_code']); ?>" class="btn btn-secondary">Chi tiết</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include '../includes/footer.php'; ?>