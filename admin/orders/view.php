<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$pdo = getPDO();

$orderId = isset($_GET['id']) ? trim($_GET['id']) : '';
if (empty($orderId)) {
    header('Location: ' . BASE_URL . '/admin/orders/index.php');
    exit;
}

// Lấy thông tin đơn hàng
$order = null;
try {
    $stmt = $pdo->prepare('
        SELECT o.*, u.full_name, u.email, u.phone, u.address
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = :id
    ');
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch();
} catch (Throwable $e) {
    $order = null;
}

if (!$order) {
    header('Location: ' . BASE_URL . '/admin/orders/index.php');
    exit;
}

// Lấy chi tiết sản phẩm trong đơn hàng
$orderItems = [];
try {
    $stmt = $pdo->prepare('
        SELECT oi.*, p.product_name, p.image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = :id
    ');
    $stmt->execute([':id' => $orderId]);
    $orderItems = $stmt->fetchAll();
} catch (Throwable $e) {
    $orderItems = [];
}

$statusLabels = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy'
];

$paymentLabels = [
    'pending' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán'
];

$methodLabels = [
    'cod' => 'Thanh toán khi nhận hàng',
    'bank_transfer' => 'Chuyển khoản ngân hàng',
    'momo' => 'Ví MoMo',
    'vnpay' => 'VNPay'
];

include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 24px; }
    .order-view__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; }
    .order-view__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .order-view__title-group .order-date { font-size: 13px; color: rgba(0,0,0,0.55); margin-top: 4px; }
    .breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 14px; flex-wrap: wrap; margin-bottom: 6px; }
    .breadcrumb a { color: var(--secondary); text-decoration: none; font-weight: 600; }
    .breadcrumb span { color: rgba(0,0,0,0.55); }
    .btn-back { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; background: rgba(0,0,0,0.06); color: var(--dark); text-decoration: none; font-weight: 600; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .btn-back:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(0,0,0,0.12); }
    
    .order-content-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .order-content-grid { grid-template-columns: 1fr; } }
    
    .order-section { background: var(--white); border-radius: 14px; border: 1px solid #f0ebe3; padding: 24px; }
    .order-section__title { font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid rgba(210,100,38,0.15); }
    
    .product-list { display: flex; flex-direction: column; gap: 16px; }
    .product-item { display: grid; grid-template-columns: 80px 1fr auto; gap: 16px; align-items: center; padding: 16px; background: rgba(255,247,237,0.3); border-radius: 12px; border: 1px solid rgba(210,100,38,0.1); }
    .product-image { width: 80px; height: 80px; border-radius: 10px; overflow: hidden; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; }
    .product-image img { width: 100%; height: 100%; object-fit: cover; }
    .product-image i { font-size: 32px; color: rgba(0,0,0,0.2); }
    .product-info { display: flex; flex-direction: column; gap: 6px; }
    .product-name { font-weight: 700; color: var(--dark); font-size: 15px; }
    .product-category { font-size: 12px; color: rgba(0,0,0,0.5); }
    .product-price { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
    .product-unit-price { font-size: 13px; color: rgba(0,0,0,0.6); }
    .product-total-price { font-size: 16px; font-weight: 700; color: var(--secondary); }
    
    .order-summary { background: rgba(255,247,237,0.5); border-radius: 12px; padding: 16px; margin-top: 16px; }
    .summary-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(210,100,38,0.1); }
    .summary-row:last-child { border-bottom: none; padding-top: 16px; margin-top: 6px; border-top: 2px solid rgba(210,100,38,0.2); }
    .summary-label { font-size: 14px; color: rgba(0,0,0,0.7); font-weight: 600; }
    .summary-value { font-size: 14px; color: var(--dark); font-weight: 600; }
    .summary-row:last-child .summary-label { font-size: 16px; color: var(--primary); }
    .summary-row:last-child .summary-value { font-size: 18px; color: var(--secondary); font-weight: 700; }
    
    .info-section { margin-bottom: 20px; }
    .info-section:last-child { margin-bottom: 0; }
    .info-row { display: flex; flex-direction: column; gap: 6px; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.06); }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 12px; color: rgba(0,0,0,0.5); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-size: 14px; color: var(--dark); font-weight: 500; }
    .status-badge { display: inline-flex; align-items: center; justify-content: center; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-badge--pending { background: rgba(255,193,7,0.15); color: #f57c00; }
    .status-badge--processing { background: rgba(33,150,243,0.15); color: #1976d2; }
    .status-badge--completed { background: rgba(76,175,80,0.15); color: #388e3c; }
    .status-badge--cancelled { background: rgba(244,67,54,0.15); color: #d32f2f; }
    .payment-badge { display: inline-flex; align-items: center; justify-content: center; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .payment-badge--pending { background: rgba(255,193,7,0.15); color: #f57c00; }
    .payment-badge--paid { background: rgba(76,175,80,0.15); color: #388e3c; }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content order-view">
        <div class="order-view__header">
            <div class="order-view__title-group">
                <h1>Đơn Hàng #<?php echo htmlspecialchars($orderId); ?></h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <a href="<?php echo BASE_URL; ?>/admin/orders/index.php">Quản Lý Đơn Hàng</a>
                    <span>/</span>
                    <span>Chi Tiết</span>
                </nav>
                <div class="order-date">Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/orders/index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Quay Lại
            </a>
        </div>

        <div class="order-content-grid">
            <div>
                <div class="order-section">
                    <h2 class="order-section__title">Sản Phẩm Trong Đơn</h2>
                    
                    <div class="product-list">
                        <?php foreach ($orderItems as $item): 
                            $productImage = !empty($item['image']) ? $item['image'] : '';
                            $unitPrice = (float) ($item['price'] ?? 0);
                            $quantity = (int) ($item['quantity'] ?? 1);
                            $totalPrice = $unitPrice * $quantity;
                        ?>
                            <div class="product-item">
                                <div class="product-image">
                                    <?php if ($productImage): ?>
                                        <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Sản phẩm'); ?></div>
                                    <div class="product-category">Số lượng: <?php echo $quantity; ?></div>
                                    <div class="product-category">Cây Cảnh</div>
                                </div>
                                <div class="product-price">
                                    <div class="product-unit-price"><?php echo number_format($unitPrice, 0, ',', '.'); ?> đ</div>
                                    <div class="product-total-price"><?php echo number_format($totalPrice, 0, ',', '.'); ?> đ</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-summary">
                        <div class="summary-row">
                            <span class="summary-label">Tạm tính:</span>
                            <span class="summary-value"><?php echo number_format((float)$order['total_amount'], 0, ',', '.'); ?> đ</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tổng cộng:</span>
                            <span class="summary-value"><?php echo number_format((float)($order['final_amount'] ?? $order['total_amount']), 0, ',', '.'); ?> đ</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="order-section" style="margin-bottom:20px;">
                    <h2 class="order-section__title">Thông Tin Đơn Hàng</h2>
                    
                    <div class="info-section">
                        <div class="info-row">
                            <div class="info-label">Mã đơn hàng</div>
                            <div class="info-value"><?php echo htmlspecialchars($orderId); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Trạng thái</div>
                            <div class="info-value">
                                <span class="status-badge status-badge--<?php echo $order['status']; ?>">
                                    <?php echo $statusLabels[$order['status']] ?? 'N/A'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Thanh toán</div>
                            <div class="info-value">
                                <span class="payment-badge payment-badge--<?php echo $order['payment_status']; ?>">
                                    <?php echo $paymentLabels[$order['payment_status']] ?? 'N/A'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phương thức</div>
                            <div class="info-value"><?php echo $methodLabels[$order['payment_method']] ?? 'N/A'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Ngày đặt</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                    </div>
                </div>

                <div class="order-section" style="margin-bottom:20px;">
                    <h2 class="order-section__title">Thông Tin Khách Hàng</h2>
                    
                    <div class="info-section">
                        <div class="info-row">
                            <div class="info-label">Tên</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Điện thoại</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="order-section">
                    <h2 class="order-section__title">Địa Chỉ Giao Hàng</h2>
                    
                    <div class="info-section">
                        <div class="info-row">
                            <div class="info-label">Người nhận</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Điện thoại</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Địa chỉ</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
