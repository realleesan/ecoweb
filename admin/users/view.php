<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$pdo = getPDO();

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/admin/users/index.php');
    exit;
}

$user = null;
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
} catch (Throwable $e) {
    $user = null;
}

if (!$user) {
    header('Location: ' . BASE_URL . '/admin/users/index.php');
    exit;
}

// Lấy thống kê đơn hàng
$orderStats = [
    'total_orders' => 0,
    'completed_orders' => 0,
    'total_spend' => 0,
    'total_reviews' => 0
];

try {
    $statsStmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_orders,
            SUM(COALESCE(final_amount, total_amount)) as total_spend
        FROM orders 
        WHERE user_id = :id
    ');
    $statsStmt->execute([':id' => $userId]);
    $stats = $statsStmt->fetch();
    if ($stats) {
        $orderStats['total_orders'] = (int) $stats['total_orders'];
        $orderStats['completed_orders'] = (int) $stats['completed_orders'];
        $orderStats['total_spend'] = (float) $stats['total_spend'];
    }
} catch (Throwable $e) {}

try {
    $reviewStmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = :id');
    $reviewStmt->execute([':id' => $userId]);
    $orderStats['total_reviews'] = (int) $reviewStmt->fetchColumn();
} catch (Throwable $e) {}

$username = (string) ($user['username'] ?? '');
$email = (string) ($user['email'] ?? '');
$fullName = (string) ($user['full_name'] ?? 'N/A');
$phone = (string) ($user['phone'] ?? 'N/A');
$address = (string) ($user['address'] ?? 'N/A');
$role = (string) ($user['role'] ?? 'user');
$isActive = (int) ($user['is_active'] ?? 0) === 1;
$createdAt = (string) ($user['created_at'] ?? '');
$initial = mb_substr($username, 0, 1, 'UTF-8');

include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 30px; }
    .user-view__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; }
    .user-view__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 14px; flex-wrap: wrap; margin-bottom: 6px; }
    .breadcrumb a { color: var(--secondary); text-decoration: none; font-weight: 600; }
    .breadcrumb span { color: rgba(0,0,0,0.55); }
    .user-view-content { display: grid; grid-template-columns: 1fr 300px; gap: 24px; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .user-view-content { grid-template-columns: 1fr; } }
    .user-info-card { background: var(--white); border-radius: 14px; border: 1px solid #f0ebe3; padding: 28px; }
    .user-info-card__title { font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
    .user-info-card__title i { color: var(--secondary); }
    .user-avatar-section { display: flex; flex-direction: column; align-items: center; gap: 14px; margin-bottom: 28px; }
    .user-avatar-large { width: 120px; height: 120px; border-radius: 50%; background: rgba(210, 100, 38, 0.1); color: var(--secondary); display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 700; }
    .user-status-badge { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    .user-status-badge--active { background: rgba(60, 96, 60, 0.15); color: #2a6a2a; }
    .user-status-badge--inactive { background: rgba(0,0,0,0.08); color: rgba(0,0,0,0.6); }
    .user-info-list { display: flex; flex-direction: column; gap: 18px; }
    .user-info-item { display: grid; grid-template-columns: 140px 1fr; gap: 12px; align-items: start; }
    .user-info-item__label { font-weight: 600; color: rgba(0,0,0,0.7); font-size: 14px; }
    .user-info-item__value { color: var(--dark); font-size: 14px; word-break: break-word; }
    .user-role-badge { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; }
    .user-role-badge--admin { background: rgba(210, 100, 38, 0.15); color: var(--secondary); }
    .user-role-badge--user { background: rgba(0,0,0,0.08); color: rgba(0,0,0,0.7); }
    .user-actions-card { background: var(--white); border-radius: 14px; border: 1px solid #f0ebe3; padding: 24px; }
    .user-actions-card__title { font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .user-actions-card__title i { color: var(--secondary); }
    .user-action-btn { width: 100%; padding: 12px 20px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px; text-decoration: none; }
    .user-action-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(0,0,0,0.15); }
    .user-action-btn--primary { background: var(--secondary); color: var(--white); }
    .user-action-btn--secondary { background: rgba(0,0,0,0.06); color: var(--dark); }
    .user-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 24px; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .user-stats-grid { grid-template-columns: repeat(2, 1fr); } }
    .user-stat-box { background: rgba(255, 247, 237, 0.5); border-radius: 12px; padding: 20px; text-align: center; border: 1px solid rgba(210, 100, 38, 0.1); }
    .user-stat-box__value { font-size: 32px; font-weight: 700; color: var(--secondary); margin-bottom: 8px; }
    .user-stat-box__label { font-size: 13px; color: rgba(0,0,0,0.6); font-weight: 600; }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content user-view">
        <div class="user-view__header">
            <div class="user-view__title-group">
                <h1>Chi Tiết Người Dùng</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <a href="<?php echo BASE_URL; ?>/admin/users/index.php">Quản Lý Người Dùng</a>
                    <span>/</span>
                    <span>Chi Tiết</span>
                </nav>
            </div>
        </div>

        <div class="user-view-content">
            <div class="user-info-card">
                <h2 class="user-info-card__title">
                    <i class="fas fa-user"></i>
                    Thông Tin Người Dùng
                </h2>

                <div class="user-avatar-section">
                    <div class="user-avatar-large">
                        <?php echo htmlspecialchars($initial); ?>
                    </div>
                    <span class="user-status-badge <?php echo $isActive ? 'user-status-badge--active' : 'user-status-badge--inactive'; ?>">
                        <?php echo $isActive ? 'Đang hoạt động' : 'Ngừng hoạt động'; ?>
                    </span>
                </div>

                <div class="user-info-list">
                    <div class="user-info-item">
                        <div class="user-info-item__label">Họ và Tên:</div>
                        <div class="user-info-item__value"><?php echo htmlspecialchars($fullName); ?></div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Email:</div>
                        <div class="user-info-item__value"><?php echo htmlspecialchars($email); ?></div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Username:</div>
                        <div class="user-info-item__value">@<?php echo htmlspecialchars($username); ?></div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Số Điện Thoại:</div>
                        <div class="user-info-item__value"><?php echo htmlspecialchars($phone); ?></div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Ngày Sinh:</div>
                        <div class="user-info-item__value">N/A</div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Giới Tính:</div>
                        <div class="user-info-item__value">N/A</div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Cấp Độ:</div>
                        <div class="user-info-item__value">
                            <span class="user-role-badge <?php echo $role === 'admin' ? 'user-role-badge--admin' : 'user-role-badge--user'; ?>">
                                <?php echo $role === 'admin' ? 'Admin' : 'User'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Điểm Tích Lũy:</div>
                        <div class="user-info-item__value">0 điểm</div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Vai Trò:</div>
                        <div class="user-info-item__value">
                            <span class="user-role-badge <?php echo $role === 'admin' ? 'user-role-badge--admin' : 'user-role-badge--user'; ?>">
                                <?php echo $role === 'admin' ? 'Quản trị' : 'Người dùng'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Email Verified:</div>
                        <div class="user-info-item__value">
                            <span class="user-status-badge user-status-badge--active">Đã xác thực</span>
                        </div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Ngày Tạo:</div>
                        <div class="user-info-item__value"><?php echo $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : 'N/A'; ?></div>
                    </div>

                    <div class="user-info-item">
                        <div class="user-info-item__label">Đăng Nhập Cuối:</div>
                        <div class="user-info-item__value">N/A</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="user-actions-card">
                    <h2 class="user-actions-card__title">
                        <i class="fas fa-cog"></i>
                        Thao Tác
                    </h2>

                    <a href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo $userId; ?>" class="user-action-btn user-action-btn--primary">
                        <i class="fas fa-edit"></i>
                        Sửa Thông Tin
                    </a>

                    <a href="<?php echo BASE_URL; ?>/admin/users/index.php" class="user-action-btn user-action-btn--secondary">
                        <i class="fas fa-arrow-left"></i>
                        Quay Lại
                    </a>
                </div>
            </div>
        </div>

        <div class="user-stats-grid">
            <div class="user-stat-box">
                <div class="user-stat-box__value"><?php echo $orderStats['total_orders']; ?></div>
                <div class="user-stat-box__label">Tổng Đơn Hàng</div>
            </div>

            <div class="user-stat-box">
                <div class="user-stat-box__value"><?php echo $orderStats['completed_orders']; ?></div>
                <div class="user-stat-box__label">Đơn Đã Thanh Toán</div>
            </div>

            <div class="user-stat-box">
                <div class="user-stat-box__value"><?php echo number_format($orderStats['total_spend'], 0, ',', '.'); ?> đ</div>
                <div class="user-stat-box__label">Tổng Chi Tiêu</div>
            </div>

            <div class="user-stat-box">
                <div class="user-stat-box__value"><?php echo $orderStats['total_reviews']; ?></div>
                <div class="user-stat-box__label">Đánh Giá</div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
