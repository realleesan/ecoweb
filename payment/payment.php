<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/sepay_config.php';
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

if (!$order) {
    header('Location: ' . BASE_URL);
    exit;
}

// Nếu đơn hàng đã trồng cây, chuyển về trang "Cây của tôi"
if ($order['status'] === 'planted') {
    header('Location: ' . BASE_URL . '/auth/my-trees.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .payment-container { max-width: 600px; margin: 60px auto; padding: 20px; }
    .payment-card { background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px; }
    .title { font-size: 24px; font-weight: 700; color: #333; text-align: center; margin-bottom: 30px; }
    .order-info { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
    .info-row { display: flex; justify-content: space-between; margin: 12px 0; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #666; font-size: 14px; }
    .info-value { color: #333; font-weight: 600; font-size: 14px; }
    .amount { text-align: center; margin: 30px 0; }
    .amount-label { color: #999; font-size: 14px; margin-bottom: 8px; }
    .amount-value { color: #667eea; font-size: 42px; font-weight: 800; }
    .qr-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 30px; margin: 20px 0; text-align: center; }
    .qr-section h3 { color: white; font-size: 18px; margin-bottom: 20px; }
    .qr-code-container { background: white; border-radius: 12px; padding: 20px; display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .qr-code-container img { max-width: 280px; width: 100%; height: auto; display: block; }
    .qr-loading { color: white; font-size: 16px; }
    .divider { text-align: center; margin: 30px 0; color: #999; font-size: 14px; position: relative; }
    .divider::before, .divider::after { content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: #ddd; }
    .divider::before { left: 0; }
    .divider::after { right: 0; }
    .bank-info { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; margin: 20px 0; }
    .bank-info h3 { color: #495057; font-size: 16px; margin-bottom: 15px; text-align: center; }
    .bank-row { margin: 10px 0; }
    .bank-label { color: #6c757d; font-size: 13px; font-weight: 600; }
    .bank-value { color: #333; font-size: 16px; font-weight: 700; margin-top: 5px; }
    .copy-btn { background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; margin-left: 10px; }
    .copy-btn:hover { background: #5568d3; }
    .note { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 8px; font-size: 14px; margin: 20px 0; }
    .btn { display: block; width: 100%; padding: 14px; border-radius: 10px; border: none; font-weight: 700; font-size: 16px; cursor: pointer; text-align: center; text-decoration: none; margin-top: 10px; }
    .btn-primary { background: #667eea; color: white; }
    .btn-secondary { background: #f1f3f5; color: #666; }
</style>

<div class="payment-container">
    <div class="payment-card">
        <h1 class="title">Thông tin thanh toán</h1>

        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Mã đơn hàng:</span>
                <span class="info-value"><?php echo htmlspecialchars($order['order_code']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Trạng thái:</span>
                <span class="info-value"><?php echo $order['status'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán'; ?></span>
            </div>
        </div>

        <div class="amount">
            <div class="amount-label">Số tiền cần thanh toán</div>
            <div class="amount-value"><?php echo number_format($order['final_amount'] ?? $order['total_amount'], 0, ',', '.'); ?> đ</div>
        </div>

        <?php if ($order['status'] === 'pending'): ?>
        
        <div class="note" style="background: #fff3cd; border-color: #ffc107; color: #856404;">
            ⏳ <strong>Chờ thanh toán:</strong> Quét mã QR bên dưới hoặc chuyển khoản thủ công.
        </div>
        
        <!-- QR Code SePay -->
        <div class="qr-section">
            <h3>📱 Quét mã QR để thanh toán qua SePay</h3>
            <div class="qr-code-container" id="qr-container">
                <div class="qr-loading">Đang tải mã QR...</div>
            </div>
            <p style="color: white; margin-top: 15px; font-size: 14px;">Mở app ngân hàng và quét mã QR</p>
        </div>
        
        <div class="divider">Hoặc chuyển khoản thủ công</div>
        
        <div class="bank-info">
            <h3>💳 Thông tin chuyển khoản thủ công:</h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Nếu không quét được QR code</p>
            
            <div class="bank-row">
                <div class="bank-label">Tài khoản SePay:</div>
                <div class="bank-value">
                    <?php echo htmlspecialchars(SEPAY_MERCHANT_ID); ?>
                    <button class="copy-btn" onclick="copyText('<?php echo htmlspecialchars(SEPAY_MERCHANT_ID); ?>')">Copy</button>
                </div>
            </div>
            
            <div class="bank-row">
                <div class="bank-label">Nội dung chuyển khoản:</div>
                <div class="bank-value">
                    <?php echo htmlspecialchars($order['order_code']); ?>
                    <button class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($order['order_code']); ?>')">Copy</button>
                </div>
            </div>
            
            <div class="bank-row">
                <div class="bank-label">Số tiền:</div>
                <div class="bank-value">
                    <?php echo number_format($order['final_amount'] ?? $order['total_amount'], 0, ',', '.'); ?> đ
                    <button class="copy-btn" onclick="copyText('<?php echo (int)($order['final_amount'] ?? $order['total_amount']); ?>')">Copy</button>
                </div>
            </div>
        </div>

        <div class="note">
            ⚠️ <strong>Lưu ý:</strong> Vui lòng chuyển khoản đúng nội dung <strong><?php echo htmlspecialchars($order['order_code']); ?></strong> để hệ thống tự động xác nhận đơn hàng.
        </div>
        
        <button class="btn btn-primary" onclick="checkPaymentStatus()">Kiểm tra thanh toán</button>
        <?php else: ?>
        <div class="note" style="background: #d4edda; border-color: #c3e6cb; color: #155724; text-align: center;">
            <h3 style="color: #2e7d32; margin-bottom: 10px;">✅ Thanh toán thành công!</h3>
            <p style="margin: 0;">Bạn đã sở hữu quyền chọn đất.</p>
        </div>
        
        <a href="<?php echo BASE_URL; ?>/payment/map.php?order_code=<?php echo urlencode($order['order_code']); ?>" class="btn btn-primary" style="font-size: 18px; padding: 18px;">
            🗺️ VÀO BẢN ĐỒ CHỌN ĐẤT NGAY
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/auth/orders.php" class="btn btn-secondary">Xem đơn hàng của tôi</a>
    </div>
</div>

<script>
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Đã copy: ' + text);
    });
}

// Load QR code và bắt đầu kiểm tra thanh toán khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($order['status'] === 'pending'): ?>
    // Load QR code
    loadQRCode();
    
    // QUAN TRỌNG: Kiểm tra trạng thái liên tục mỗi 3 giây
    // Ngay khi SePay IPN cập nhật status='paid', sẽ tự động chuyển sang map
    setInterval(checkPaymentStatusAuto, 3000);
    <?php elseif ($order['status'] === 'paid'): ?>
    // Nếu đã thanh toán rồi, tự động chuyển sang map sau 2 giây
    setTimeout(function() {
        window.location.href = '<?php echo BASE_URL; ?>/payment/map.php?order_code=<?php echo urlencode($order['order_code']); ?>';
    }, 2000);
    <?php endif; ?>
});

function loadQRCode() {
    const orderCode = '<?php echo htmlspecialchars($order['order_code']); ?>';
    
    fetch('<?php echo BASE_URL; ?>/api/get-sepay-qr.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_code: orderCode })
    })
    .then(r => r.json())
    .then(res => {
        if (res && res.success && res.qr_url) {
            document.getElementById('qr-container').innerHTML = 
                '<img src="' + res.qr_url + '" alt="QR Code" onerror="handleQRError()">';
        } else {
            document.getElementById('qr-container').innerHTML = 
                '<div style="color: #c0392b; padding: 20px;">Không thể tải mã QR. Vui lòng chuyển khoản thủ công.</div>';
        }
    })
    .catch(err => {
        console.error('QR load error:', err);
        document.getElementById('qr-container').innerHTML = 
            '<div style="color: #c0392b; padding: 20px;">Lỗi tải mã QR. Vui lòng chuyển khoản thủ công.</div>';
    });
}

function handleQRError() {
    document.getElementById('qr-container').innerHTML = 
        '<div style="color: #c0392b; padding: 20px;">Không thể hiển thị mã QR. Vui lòng chuyển khoản thủ công.</div>';
}

function checkPaymentStatusAuto() {
    // Gọi API kiểm tra âm thầm
    fetch('<?php echo BASE_URL; ?>/api/check-order-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_code: '<?php echo htmlspecialchars($order['order_code']); ?>' })
    })
    .then(r => r.json())
    .then(res => {
        if (res && res.status === 'paid') {
            // ✅ THANH TOÁN THÀNH CÔNG -> Tự động chuyển sang bản đồ
            console.log('Payment successful! Redirecting to map...');
            window.location.href = '<?php echo BASE_URL; ?>/payment/map.php?order_code=<?php echo urlencode($order['order_code']); ?>';
        }
    })
    .catch(err => console.error('Waiting for payment...', err));
}

function checkPaymentStatus() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Đang kiểm tra...';
    
    // Reload trang để kiểm tra status mới từ database
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
