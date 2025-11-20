<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

requireLogin();
$pdo = getPDO();
$orderCode = trim($_GET['order_code'] ?? '');
if ($orderCode === '') { header('Location: ' . BASE_URL . '/public/products.php'); exit; }

$order = null;
try {
    $stmt = $pdo->prepare('SELECT order_id, order_code, total_amount, discount_amount, final_amount, coupon_code, created_at FROM orders WHERE order_code = :code AND user_id = :uid LIMIT 1');
    $stmt->execute([':code' => $orderCode, ':uid' => $_SESSION['user_id']]);
    $order = $stmt->fetch();
} catch (Exception $e) {}
if (!$order) { header('Location: ' . BASE_URL . '/public/products.php'); exit; }

$bankAccount = '123456789';
$bankName = 'VCB';
$accountName = 'GROWHOPE COMPANY';
$subtotal = (float)$order['total_amount'];
$discountAmount = isset($order['discount_amount']) ? (float)$order['discount_amount'] : 0.0;
$finalAmount = isset($order['final_amount']) && (float)$order['final_amount'] > 0 ? (float)$order['final_amount'] : max($subtotal - $discountAmount, 0);
$amount = (int)round($finalAmount > 0 ? $finalAmount : $subtotal);

$qrUrl = 'https://qr.sepay.vn/img?acc=' . urlencode($bankAccount) . '&bank=' . urlencode($bankName) . '&amount=' . $amount . '&des=' . urlencode($order['order_code']);

include __DIR__ . '/../includes/header.php';
?>
<style>
    body { background-color: var(--light); }
    .payment-container { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 40px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; }
    .success { background:#e9f7ef; border:1px solid #c8e6c9; color:#2e7d32; padding:12px; border-radius:8px; margin-bottom:15px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: <?php echo GRID_GAP; ?>; }
    .card { background: var(--white); border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); padding:20px; }
    .title { font-weight:700; color:var(--primary); margin-bottom:10px; }
    .order-info div { margin:6px 0; color:var(--dark); }
    .qr { display:flex; align-items:center; justify-content:center; background:#f8f8f8; border-radius:10px; padding:10px; }
    .copy-row { display:flex; align-items:center; gap:10px; margin-top:8px; }
    .btn { padding:10px 14px; border-radius:8px; border:none; cursor:pointer; font-weight:600; }
    .btn-primary { background: var(--primary); color: var(--white); }
    .btn-secondary { background: var(--secondary); color: var(--white); }
    .status { margin-top:12px; color:#a67d4a; display:flex; align-items:center; gap:8px; }
    .spinner { width:12px; height:12px; border:2px solid #f3f3f3; border-top:2px solid var(--secondary); border-radius:50%; animation:spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<main class="payment-container">
    <div class="success"><strong>Thanh toán tạo đơn thành công.</strong> Vui lòng chuyển khoản theo hướng dẫn để hoàn tất.</div>
    <div class="grid">
        <div class="card">
            <div class="title">Thông tin đơn hàng</div>
            <div class="order-info">
                <div><strong>Mã đơn:</strong> <?php echo htmlspecialchars($order['order_code']); ?></div>
                <div><strong>Ngày đặt:</strong> <?php echo date(DATETIME_FORMAT, strtotime($order['created_at'])); ?></div>
                <div><strong>Tạm tính:</strong> <?php echo number_format($subtotal, 0, ',', '.'); ?> đ</div>
                <?php if ($discountAmount > 0): ?>
                    <div><strong>Giảm giá<?php echo !empty($order['coupon_code']) ? ' (mã ' . htmlspecialchars($order['coupon_code']) . ')' : ''; ?>:</strong> -<?php echo number_format($discountAmount, 0, ',', '.'); ?> đ</div>
                <?php endif; ?>
                <div><strong>Tổng phải thanh toán:</strong> <span style="color:var(--secondary); font-weight:700;">&nbsp;<?php echo number_format($finalAmount > 0 ? $finalAmount : $subtotal, 0, ',', '.'); ?> đ</span></div>
            </div>
            <div class="status"><span class="spinner"></span> Đang chờ thanh toán...</div>
            <div style="margin-top:15px; display:flex; gap:10px;">
                <a href="<?php echo BASE_URL; ?>/auth/orders.php" class="btn btn-primary" style="text-decoration:none;">Xem đơn hàng của tôi</a>
                <a href="<?php echo BASE_URL; ?>/public/products.php" class="btn btn-secondary" style="text-decoration:none;">Tiếp tục mua sắm</a>
            </div>
        </div>
        <div class="card">
            <div class="title">Thông tin chuyển khoản (VietQR)</div>
            <div class="qr"><img src="<?php echo $qrUrl; ?>" alt="VietQR" style="max-width:100%; height:auto;"></div>
            <div style="margin-top:12px;">
                <div><strong>Ngân hàng:</strong> <?php echo htmlspecialchars($bankName); ?></div>
                <div><strong>Số tài khoản:</strong> <?php echo htmlspecialchars($bankAccount); ?></div>
                <div><strong>Chủ tài khoản:</strong> <?php echo htmlspecialchars($accountName); ?></div>
                <div class="copy-row"><strong>Nội dung chuyển khoản:</strong> <span id="transferDes" style="color:var(--secondary); font-weight:700;"><?php echo htmlspecialchars($order['order_code']); ?></span> <button class="btn btn-primary" type="button" onclick="copyDes()">Sao chép</button></div>
            </div>
        </div>
    </div>
</main>
<script>
function copyDes(){ const t=document.getElementById('transferDes').innerText; navigator.clipboard.writeText(t).then(()=>alert('Đã sao chép nội dung chuyển khoản.')); }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>