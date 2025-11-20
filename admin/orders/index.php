<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$pdo = getPDO();

// Lấy tham số lọc
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$paymentFilter = isset($_GET['payment']) ? trim($_GET['payment']) : '';
$dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Thống kê đơn hàng
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'completed_orders' => 0,
    'total_revenue' => 0
];

try {
    $statsStmt = $pdo->query('
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_orders,
            SUM(COALESCE(final_amount, total_amount)) as total_revenue
        FROM orders
    ');
    $statsData = $statsStmt->fetch();
    if ($statsData) {
        $stats['total_orders'] = (int) $statsData['total_orders'];
        $stats['pending_orders'] = (int) $statsData['pending_orders'];
        $stats['completed_orders'] = (int) $statsData['completed_orders'];
        $stats['total_revenue'] = (float) $statsData['total_revenue'];
    }
} catch (Throwable $e) {}

// Xây dựng điều kiện lọc
$conditions = [];
$params = [];

if ($keyword !== '') {
    $conditions[] = '(o.order_id LIKE :kw OR u.full_name LIKE :kw OR u.email LIKE :kw OR u.phone LIKE :kw)';
    $params[':kw'] = '%' . $keyword . '%';
}

if (in_array($statusFilter, ['pending', 'processing', 'completed', 'cancelled'], true)) {
    $conditions[] = 'o.status = :status';
    $params[':status'] = $statusFilter;
}

if (in_array($paymentFilter, ['pending', 'paid'], true)) {
    $conditions[] = 'o.payment_status = :payment';
    $params[':payment'] = $paymentFilter;
}

if ($dateFrom !== '') {
    $conditions[] = 'DATE(o.created_at) >= :date_from';
    $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $conditions[] = 'DATE(o.created_at) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

// Phân trang
$itemsPerPage = PAGINATION_CATEGORIES_PER_PAGE;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Đếm tổng số đơn hàng
$totalOrders = 0;
try {
    $countSql = "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.user_id $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) { $countStmt->bindValue($k, $v); }
    $countStmt->execute();
    $totalOrders = (int) $countStmt->fetchColumn();
} catch (Throwable $e) { $totalOrders = 0; }

// Lấy danh sách đơn hàng
$orders = [];
try {
    $sql = "
        SELECT 
            o.order_id,
            o.user_id,
            o.total_amount,
            o.final_amount,
            o.status,
            o.payment_status,
            o.payment_method,
            o.created_at,
            u.full_name,
            u.email,
            u.phone,
            u.address
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        $whereSql
        ORDER BY o.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (Throwable $e) { $orders = []; }

include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 30px; }
    .orders-page__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; }
    .orders-page__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .orders-page__title-group p { color: rgba(0,0,0,0.55); font-size: 14px; }
    .breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 14px; flex-wrap: wrap; margin-bottom: 6px; }
    .breadcrumb a { color: var(--secondary); text-decoration: none; font-weight: 600; }
    .breadcrumb span { color: rgba(0,0,0,0.55); }
    
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    .stat-card { background: linear-gradient(135deg, rgba(255,247,237,0.8), rgba(255,247,237,0.4)); border-radius: 12px; padding: 18px; border: 1px solid rgba(210,100,38,0.15); position: relative; overflow: hidden; }
    .stat-card::before { content: ''; position: absolute; top: -50%; right: -20px; width: 120px; height: 120px; background: rgba(210,100,38,0.08); border-radius: 50%; }
    .stat-icon { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary), #f7c76a); display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 20px; margin-bottom: 12px; box-shadow: 0 6px 12px rgba(210,100,38,0.2); }
    .stat-value { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 4px; }
    .stat-label { font-size: 13px; color: rgba(0,0,0,0.6); font-weight: 600; }
    
    .orders-filter { background-color: rgba(255,247,237,0.9); border-radius: 12px; padding: 16px 18px; border: 1px solid rgba(210,100,38,0.15); display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; align-items: end; }
    .orders-filter__actions { grid-column: 1 / -1; }
    @media (max-width: <?php echo BREAKPOINT_LG; ?>) { .orders-filter { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .orders-filter { grid-template-columns: 1fr; } }
    .orders-filter__field label { font-weight: 600; font-size: 12px; color: var(--dark); margin-bottom: 5px; display: block; }
    .orders-filter__field input, .orders-filter__field select { width: 100%; border-radius: 8px; border: 1px solid #e5e5e5; padding: 8px 12px; font-size: 13px; transition: border 0.2s ease, box-shadow 0.2s ease; background-color: var(--white); }
    .orders-filter__field input:focus, .orders-filter__field select:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(210,100,38,0.15); outline: none; }
    .orders-filter__actions { display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-top: 4px; }
    .btn-filter-submit { padding: 8px 14px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; background: var(--secondary); color: var(--white); font-size: 12px; white-space: nowrap; }
    .btn-filter-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(210,100,38,0.25); }
    .btn-filter-reset { padding: 8px 12px; border-radius: 8px; border: 1px solid #e5e5e5; font-weight: 600; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; background: var(--white); color: var(--dark); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-size: 12px; white-space: nowrap; }
    .btn-filter-reset:hover { transform: translateY(-1px); box-shadow: 0 6px 12px rgba(0,0,0,0.1); }
    
    .orders-table-wrapper { border-radius: 12px; border: 1px solid #f0ebe3; overflow: hidden; background: var(--white); }
    .orders-table { width: 100%; display: table; table-layout: fixed; }
    .order-row { width: 100%; display: grid; grid-template-columns: 2fr 1fr 0.9fr 1fr 0.9fr 1.1fr 0.8fr; gap: 10px; align-items: center; padding: 12px 16px; background-color: var(--white); border-bottom: 1px solid #f3f1ed; box-sizing: border-box; }
    .order-row:last-child { border-bottom: none; }
    .order-row--head { width: 100%; background-color: rgba(255,247,237,0.75); font-weight: 600; color: rgba(0,0,0,0.6); text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; position: relative; box-sizing: border-box; padding: 12px 16px; }
    .order-row--head::before { content: ''; position: absolute; inset: 0; background: rgba(255,247,237,0.75); z-index: 0; }
    .order-row--head .order-col { position: relative; z-index: 1; }
    .order-col { display: flex; align-items: center; gap: 8px; font-size: 13px; overflow: hidden; }
    .order-id { font-weight: 700; color: var(--secondary); font-size: 13px; line-height: 1.3; }
    .order-category { font-size: 11px; color: rgba(0,0,0,0.5); font-weight: 600; }
    .customer-info { display: flex; flex-direction: column; gap: 3px; overflow: hidden; }
    .customer-name { font-weight: 600; color: var(--dark); font-size: 13px; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .customer-contact { font-size: 11px; color: rgba(0,0,0,0.55); line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .status-badge { display: inline-flex; align-items: center; justify-content: center; padding: 5px 10px; border-radius: 16px; font-size: 11px; font-weight: 600; white-space: nowrap; text-align: center; line-height: 1.2; }
    .status-badge--pending { background: rgba(255,193,7,0.15); color: #f57c00; }
    .status-badge--processing { background: rgba(33,150,243,0.15); color: #1976d2; }
    .status-badge--completed { background: rgba(76,175,80,0.15); color: #388e3c; }
    .status-badge--cancelled { background: rgba(244,67,54,0.15); color: #d32f2f; }
    .payment-badge { display: inline-flex; align-items: center; justify-content: center; padding: 5px 10px; border-radius: 16px; font-size: 11px; font-weight: 600; white-space: nowrap; text-align: center; line-height: 1.2; }
    .payment-badge--pending { background: rgba(255,193,7,0.15); color: #f57c00; }
    .payment-badge--paid { background: rgba(76,175,80,0.15); color: #388e3c; }
    .order-actions { display: flex; gap: 6px; justify-content: center; }
    .order-actions a { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: var(--dark); background: rgba(0,0,0,0.06); text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; font-size: 13px; }
    .order-actions a:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.1); }
    @media (max-width: <?php echo BREAKPOINT_LG; ?>) { 
        .orders-table-wrapper { overflow-x: auto; }
        .orders-table { min-width: 800px; }
        .order-row { grid-template-columns: 2fr 1fr 0.9fr 1fr 0.9fr 1.1fr 0.8fr; padding: 12px 14px; } 
    }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .order-row { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 18px; } .order-row--head { display: none; } }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content orders-page">
        <div class="orders-page__header">
            <div class="orders-page__title-group">
                <h1>Quản Lý Đơn Hàng</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <span>Quản Lý Đơn Hàng</span>
                </nav>
                <p>Tổng cộng: <?php echo $totalOrders; ?> đơn hàng</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Tổng Đơn Hàng</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Chờ Xử Lý</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                <div class="stat-label">Hoàn Tất</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-value"><?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?> đ</div>
                <div class="stat-label">Tổng Doanh Thu</div>
            </div>
        </div>

        <form class="orders-filter" method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="orders-filter__field">
                <label for="q">Tìm Kiếm</label>
                <input type="text" id="q" name="q" placeholder="Mã đơn, email, tên khách hàng..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="orders-filter__field">
                <label for="status">Trạng Thái Đơn</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                    <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Hoàn tất</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
            <div class="orders-filter__field">
                <label for="payment">Trạng Thái Thanh Toán</label>
                <select id="payment" name="payment">
                    <option value="">Tất cả</option>
                    <option value="pending" <?php echo $paymentFilter === 'pending' ? 'selected' : ''; ?>>Chưa thanh toán</option>
                    <option value="paid" <?php echo $paymentFilter === 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                </select>
            </div>
            <div class="orders-filter__field">
                <label for="date_from">Từ Ngày</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            <div class="orders-filter__field">
                <label for="date_to">Đến Ngày</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            <div class="orders-filter__actions">
                <button type="submit" class="btn-filter-submit"><i class="fas fa-filter"></i> Lọc</button>
            </div>
        </form>

        <div class="orders-table-wrapper">
            <div class="orders-table" role="table" aria-label="Danh sách đơn hàng">
                <div class="order-row order-row--head" role="row">
                    <div class="order-col" role="columnheader">Khách Hàng</div>
                    <div class="order-col" role="columnheader">Tổng Tiền</div>
                    <div class="order-col" role="columnheader">Trạng Thái</div>
                    <div class="order-col" role="columnheader">Thanh Toán</div>
                    <div class="order-col" role="columnheader">Phương Thức</div>
                    <div class="order-col" role="columnheader">Ngày Đặt</div>
                    <div class="order-col" role="columnheader">Thao Tác</div>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="order-row" role="row" style="justify-content:center;">
                        <div style="grid-column:1/-1;text-align:center;color:rgba(0,0,0,0.5);padding:40px;">Chưa có đơn hàng</div>
                    </div>
                <?php else: foreach ($orders as $order):
                    $orderId = (string) ($order['order_id'] ?? '');
                    $finalAmount = (float) ($order['final_amount'] ?? $order['total_amount'] ?? 0);
                    $status = (string) ($order['status'] ?? 'pending');
                    $paymentStatus = (string) ($order['payment_status'] ?? 'pending');
                    $paymentMethod = (string) ($order['payment_method'] ?? 'N/A');
                    $createdAt = (string) ($order['created_at'] ?? '');
                    
                    $statusLabels = [
                        'pending' => 'Chờ xử lý',
                        'processing' => 'Đang xử lý',
                        'completed' => 'Hoàn tất',
                        'cancelled' => 'Đã hủy'
                    ];
                    $statusLabel = $statusLabels[$status] ?? 'N/A';
                    
                    $paymentLabels = [
                        'pending' => 'Chưa thanh toán',
                        'paid' => 'Đã thanh toán'
                    ];
                    $paymentLabel = $paymentLabels[$paymentStatus] ?? 'N/A';
                    
                    $methodLabels = [
                        'cod' => 'COD',
                        'bank_transfer' => 'Chuyển khoản',
                        'momo' => 'MoMo',
                        'vnpay' => 'VNPay'
                    ];
                    $methodLabel = $methodLabels[$paymentMethod] ?? $paymentMethod;
                ?>
                    <div class="order-row" role="row">
                        <div class="order-col" role="cell">
                            <div style="display:flex;flex-direction:column;gap:4px;">
                                <span class="order-id"><?php echo htmlspecialchars($orderId); ?></span>
                                <span class="order-category">Liên phẩm</span>
                            </div>
                        </div>
                        <div class="order-col" role="cell">
                            <div class="customer-info">
                                <span class="customer-name"><?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?></span>
                                <span class="customer-contact"><?php echo htmlspecialchars($order['email'] ?? ''); ?></span>
                                <span class="customer-contact"><?php echo htmlspecialchars($order['phone'] ?? ''); ?></span>
                            </div>
                        </div>
                        <div class="order-col" role="cell">
                            <strong style="color:var(--secondary);"><?php echo number_format($finalAmount, 0, ',', '.'); ?> đ</strong>
                        </div>
                        <div class="order-col" role="cell">
                            <span class="status-badge status-badge--<?php echo $status; ?>"><?php echo $statusLabel; ?></span>
                        </div>
                        <div class="order-col" role="cell">
                            <span class="payment-badge payment-badge--<?php echo $paymentStatus; ?>"><?php echo $paymentLabel; ?></span>
                        </div>
                        <div class="order-col" role="cell">
                            <span><?php echo htmlspecialchars($methodLabel); ?></span>
                        </div>
                        <div class="order-col" role="cell">
                            <span><?php echo $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : 'N/A'; ?></span>
                        </div>
                        <div class="order-col" role="cell">
                            <div class="order-actions">
                                <a href="<?php echo BASE_URL; ?>/admin/orders/view.php?id=<?php echo urlencode($orderId); ?>" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="pagination-wrap">
            <?php
            $current_page = $page;
            $total_pages = max(1, (int) ceil($totalOrders / $itemsPerPage));
            include __DIR__ . '/../../includes/components/pagination.php';
            ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
