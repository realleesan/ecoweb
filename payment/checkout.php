<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

requireLogin();
$user = getCurrentUser();
if (!$user) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$pdo = getPDO();
$addresses = [];
$cartItems = [];
$subtotal = 0;

try {
    $stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = :uid ORDER BY is_default DESC, created_at DESC');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $addresses = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT c.quantity, p.product_id, p.name, p.price FROM cart c INNER JOIN products p ON c.product_id = p.product_id WHERE c.user_id = :uid');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();
    foreach ($cartItems as $item) { $subtotal += $item['price'] * $item['quantity']; }
} catch (Exception $e) {}

if (empty($cartItems)) { header('Location: ' . BASE_URL . '/public/products.php'); exit; }

include __DIR__ . '/../includes/header.php';
?>
<style>
    body { background-color: var(--light); }
    .checkout-container { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 40px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; }
    .grid { display: grid; grid-template-columns: 1fr 380px; gap: <?php echo GRID_GAP; ?>; align-items: start; }
    .card { background: var(--white); border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
    .section-title { font-weight: 700; color: var(--primary); margin-bottom: 15px; }
    .address-list { display: grid; gap: 12px; }
    .address-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; display: grid; grid-template-columns: 24px 1fr; gap: 10px; }
    .warning { background:#fff5f0; border:1px solid #ffd8c2; color:#a64b2a; padding:12px; border-radius:8px; }
    .btn { padding: 10px 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; }
    .btn-primary { background: var(--primary); color: var(--white); }
    .btn-secondary { background: var(--secondary); color: var(--white); }
    .input, textarea { width: 100%; padding: 10px 12px; border:1px solid #ddd; border-radius:6px; font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; }
    .summary { position: sticky; top: 20px; }
    .summary-item { display:flex; justify-content: space-between; margin: 8px 0; }
    .summary-item--discount { color: #c0392b; font-weight: 600; }
    .summary-item--final strong { color: var(--secondary); font-size: 18px; }
    .coupon-feedback { margin-top: 10px; font-size: 14px; }
    .coupon-feedback--success { color: #2f7f2f; }
    .coupon-feedback--error { color: #c0392b; }
    #coupon-spinner { display: none; }
</style>

<main class="checkout-container">
    <div class="grid">
        <div class="card">
            <h2 class="section-title">Địa chỉ giao hàng</h2>
            <?php if (empty($addresses)): ?>
                <div class="warning">Bạn chưa có địa chỉ giao hàng. <a href="<?php echo BASE_URL; ?>/auth/addresses.php" class="btn btn-primary" style="margin-left:10px; display:inline-block; text-decoration:none;">Thêm địa chỉ mới</a></div>
            <?php else: ?>
                <div class="address-list">
                    <?php foreach ($addresses as $addr): ?>
                        <label class="address-card">
                            <input type="radio" name="shipping_address_id" value="<?php echo (int)$addr['address_id']; ?>" <?php echo $addr['is_default'] ? 'checked' : ''; ?>>
                            <div>
                                <div style="font-weight:600; color:var(--dark)"><?php echo htmlspecialchars($addr['recipient_name']); ?> • <?php echo htmlspecialchars($addr['phone']); ?></div>
                                <div style="color:#666; font-size:14px;"><?php echo htmlspecialchars($addr['street_address'] . ', ' . $addr['ward'] . ', ' . $addr['city']); ?></div>
                                <?php if ($addr['is_default']): ?><div style="color:var(--secondary); font-size:12px; font-weight:600;">Mặc định</div><?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo BASE_URL; ?>/auth/addresses.php" class="btn btn-primary" style="margin-top:12px; text-decoration:none; display:inline-block;">Thêm địa chỉ mới</a>
            <?php endif; ?>

            <h2 class="section-title" style="margin-top:20px;">Phương thức thanh toán</h2>
            <label style="display:flex; align-items:center; gap:10px;">
                <input type="radio" name="payment_method" value="bank_transfer" checked>
                <span>Chuyển khoản ngân hàng</span>
            </label>

            <h2 class="section-title" style="margin-top:20px;">Mã giảm giá</h2>
            <div style="display:flex; gap:10px;">
                <input class="input" type="text" id="coupon_code" placeholder="Nhập mã giảm giá">
                <button class="btn btn-primary" type="button" id="coupon_apply_btn" onclick="applyCoupon()">Áp dụng</button>
            </div>
            <div class="coupon-feedback" id="coupon_feedback" aria-live="polite"></div>

            <h2 class="section-title" style="margin-top:20px;">Ghi chú đơn hàng</h2>
            <textarea id="order_note" rows="4" placeholder="Ghi chú cho đơn hàng"></textarea>
        </div>

        <div class="card summary">
            <h2 class="section-title">Tóm tắt đơn hàng</h2>
            <div>
                <?php foreach ($cartItems as $item): ?>
                    <div class="product-row">
                        <div style="color:var(--dark); flex:1;"><?php echo htmlspecialchars($item['name']); ?> × <?php echo (int)$item['quantity']; ?></div>
                        <div style="color:var(--secondary); font-weight:700;"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ</div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="summary-item"><span>Tạm tính</span><strong id="summary_subtotal" data-value="<?php echo (float) $subtotal; ?>"><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</strong></div>
            <div class="summary-item"><span>Phí vận chuyển</span><strong>Miễn phí</strong></div>
            <div class="summary-item summary-item--discount" id="summary_discount" style="display:none;"><span>Giảm giá</span><strong>-0 đ</strong></div>
            <div class="summary-item summary-item--final"><span>Tổng cộng</span><strong id="summary_final" data-value="<?php echo (float) $subtotal; ?>"><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</strong></div>
            <button class="btn btn-secondary" style="width:100%; margin-top:12px; padding:14px; font-size:16px;" onclick="placeOrder()">ĐẶT HÀNG NGAY</button>
        </div>
    </div>
</main>

<script>
let appliedCoupon = null;
let discountAmount = 0;
let finalAmount = <?php echo (float) $subtotal; ?>;

function displayCouponFeedback(message, type) {
    const feedbackEl = document.getElementById('coupon_feedback');
    feedbackEl.textContent = message;
    feedbackEl.className = 'coupon-feedback ' + (type === 'success' ? 'coupon-feedback--success' : 'coupon-feedback--error');
}

function updateSummary() {
    const discountRow = document.getElementById('summary_discount');
    const finalEl = document.getElementById('summary_final');
    const subtotalValue = parseFloat(document.getElementById('summary_subtotal').dataset.value || '0');

    if (discountAmount > 0) {
        discountRow.style.display = 'flex';
        discountRow.querySelector('strong').textContent = '-' + new Intl.NumberFormat('vi-VN').format(discountAmount) + ' đ';
    } else {
        discountRow.style.display = 'none';
        discountRow.querySelector('strong').textContent = '-0 đ';
    }

    finalEl.dataset.value = finalAmount.toString();
    finalEl.textContent = new Intl.NumberFormat('vi-VN').format(finalAmount) + ' đ';
}

function toggleCouponLoading(isLoading) {
    const btn = document.getElementById('coupon_apply_btn');
    btn.disabled = isLoading;
    btn.textContent = isLoading ? 'Đang kiểm tra...' : 'Áp dụng';
}

function applyCoupon() {
    const codeInput = document.getElementById('coupon_code');
    const code = codeInput.value.trim().toUpperCase();
    if (code === '') {
        displayCouponFeedback('Vui lòng nhập mã giảm giá.', 'error');
        return;
    }

    toggleCouponLoading(true);
    displayCouponFeedback('Đang kiểm tra mã...', 'success');

    fetch('<?php echo BASE_URL; ?>/api/apply-coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ coupon_code: code })
    }).then(res => res.json()).then(data => {
        if (data && data.success) {
            appliedCoupon = data.coupon_code;
            discountAmount = data.discount_amount;
            finalAmount = data.final_amount;
            displayCouponFeedback('Áp dụng mã thành công. Giảm ' + new Intl.NumberFormat('vi-VN').format(discountAmount) + ' đ.', 'success');
            updateSummary();
        } else {
            appliedCoupon = null;
            discountAmount = 0;
            finalAmount = parseFloat(document.getElementById('summary_subtotal').dataset.value || '0');
            displayCouponFeedback(data.message || 'Không thể áp dụng mã giảm giá.', 'error');
            updateSummary();
        }
    }).catch(() => {
        displayCouponFeedback('Không thể kiểm tra mã giảm giá. Vui lòng thử lại.', 'error');
    }).finally(() => {
        toggleCouponLoading(false);
    });
}

function placeOrder(){
    const addr = document.querySelector('input[name="shipping_address_id"]:checked');
    if (!addr) { alert('Vui lòng chọn địa chỉ giao hàng.'); return; }
    
    const payload = {
        shipping_address_id: parseInt(addr.value),
        payment_method: 'bank_transfer',
        // Logic lấy mã giảm giá: ưu tiên mã đã áp dụng thành công, nếu không thì lấy giá trị đang nhập
        coupon_code: (typeof appliedCoupon !== 'undefined' && appliedCoupon) ? appliedCoupon : document.getElementById('coupon_code').value.trim(),
        note: document.getElementById('order_note').value.trim()
    };
    
    // Disable nút bấm để tránh double-click
    const orderBtn = event.target || document.querySelector('button[onclick="placeOrder()"]');
    if(orderBtn) {
        orderBtn.disabled = true;
        orderBtn.textContent = 'ĐANG CHUYỂN HƯỚNG...';
    }
    
    fetch('<?php echo BASE_URL; ?>/api/create-order.php', {
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res && res.success) {
            // --- LOGIC MỚI CHO SEPAY GATEWAY ---
            
            if (res.payment_url && res.params) {
                // Trường hợp 1: API trả về dữ liệu để POST sang SePay (Mới)
                
                // 1. Tạo form ẩn
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = res.payment_url; // Link SePay Sandbox
                
                // 2. Đổ dữ liệu vào các input ẩn
                for (const key in res.params) {
                    if (res.params.hasOwnProperty(key)) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = res.params[key];
                        form.appendChild(input);
                    }
                }
                
                // 3. Gắn vào body và tự động submit
                document.body.appendChild(form);
                form.submit();
                
            } else if (res.redirect_url) {
                // Trường hợp 2: Fallback cho logic cũ (nếu API chưa cập nhật kịp)
                window.location.href = res.redirect_url;
            } else {
                // Trường hợp lỗi: Thành công nhưng không có link
                alert('Lỗi: Không tìm thấy đường dẫn thanh toán.');
                if(orderBtn) {
                    orderBtn.disabled = false;
                    orderBtn.textContent = 'ĐẶT HÀNG NGAY';
                }
            }
            // -----------------------------------
        } else {
            alert(res.message || 'Không thể tạo đơn hàng.');
            if(orderBtn) {
                orderBtn.disabled = false;
                orderBtn.textContent = 'ĐẶT HÀNG NGAY';
            }
        }
    })
    .catch(err => {
        console.error('Lỗi kết nối:', err);
        alert('Có lỗi xảy ra khi kết nối tới server. Vui lòng thử lại.');
        if(orderBtn) {
            orderBtn.disabled = false;
            orderBtn.textContent = 'ĐẶT HÀNG NGAY';
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>