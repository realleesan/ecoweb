<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';

// Require login
requireLogin();

// Get current user
$user = getCurrentUser();
if (!$user) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$addressTypeLabels = [
    'home' => 'Nhà riêng',
    'office' => 'Văn phòng',
    'school' => 'Trường học'
];

$defaultAddress = null;
$defaultAddressText = '';

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT recipient_name, phone, street_address, ward, city, address_type FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC LIMIT 1');
    $stmt->execute(['user_id' => $user['user_id']]);
    $defaultAddress = $stmt->fetch();

    if ($defaultAddress) {
        $addressParts = array_filter([
            $defaultAddress['street_address'] ?? '',
            $defaultAddress['ward'] ?? '',
            $defaultAddress['city'] ?? ''
        ]);
        $defaultAddressText = implode(', ', $addressParts);
    }
} catch (Exception $e) {
    $defaultAddress = null;
}

include '../includes/header.php';
?>

<style>
    body {
        background-color: var(--light);
        min-height: 100vh;
    }

    .account-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 40px auto;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
    }

    .account-content {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
        align-items: start;
    }

    .account-main {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 40px;
    }

    .account-main-header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--light);
    }

    .account-main-header h1 {
        font-size: 28px;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .account-main-header p {
        color: var(--dark);
        font-size: 14px;
    }

    .account-info {
        display: grid;
        gap: 25px;
    }

    .info-group {
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 20px;
        align-items: center;
        padding: 20px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-group:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: var(--dark);
        font-size: 15px;
    }

    .info-value {
        color: var(--dark);
        font-size: 15px;
    }

    .info-value .address-meta {
        margin-top: 6px;
        color: #555;
        font-size: 14px;
    }

    .address-tag {
        display: inline-block;
        margin-top: 8px;
        background-color: var(--light);
        color: var(--primary);
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .manage-address-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 14px;
        font-weight: 600;
        color: var(--primary);
        text-decoration: none;
        font-size: 14px;
    }

    .manage-address-link:hover {
        text-decoration: underline;
    }

    .info-value.empty {
        color: #999;
        font-style: italic;
    }

    .btn-edit {
        display: inline-block;
        padding: 12px 30px;
        background-color: var(--primary);
        color: var(--white);
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        transition: background-color 0.3s ease;
        margin-top: 20px;
    }

    .btn-edit:hover {
        background-color: #2d4a2d;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .account-content {
            grid-template-columns: 1fr;
        }

        .account-main {
            padding: 25px 20px;
        }

        .info-group {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .info-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
    }
</style>

<div class="account-container">
    <div class="account-content">
        <?php include 'sidebar-account.php'; ?>
        
        <div class="account-main">
            <div class="account-main-header">
                <h1>Thông tin tài khoản</h1>
                <p>Quản lý thông tin cá nhân của bạn</p>
            </div>

            <div class="account-info">
                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-user"></i> Tên đăng nhập
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-envelope"></i> Email
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-id-card"></i> Họ và tên
                    </div>
                    <div class="info-value <?php echo empty($user['full_name']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Chưa cập nhật'; ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-phone"></i> Số điện thoại
                    </div>
                    <div class="info-value <?php echo empty($user['phone']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Chưa cập nhật'; ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-map-marker-alt"></i> Địa chỉ
                    </div>
                    <?php if ($defaultAddress): ?>
                        <div class="info-value">
                            <div>
                                <strong><?php echo htmlspecialchars($defaultAddress['recipient_name']); ?></strong>
                            </div>
                            <?php if (!empty($defaultAddressText)): ?>
                                <div class="address-meta">
                                    <?php echo htmlspecialchars($defaultAddressText); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($defaultAddress['phone'])): ?>
                                <div class="address-meta">
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($defaultAddress['phone']); ?>
                                </div>
                            <?php endif; ?>
                            <span class="address-tag">
                                <?php echo $addressTypeLabels[$defaultAddress['address_type']] ?? 'Địa chỉ'; ?>
                            </span>
                            <a class="manage-address-link" href="<?php echo BASE_URL; ?>/auth/addresses.php">
                                Quản lý sổ địa chỉ <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="info-value empty">
                            Chưa cập nhật
                            <a class="manage-address-link" href="<?php echo BASE_URL; ?>/auth/addresses.php">
                                Thêm địa chỉ ngay <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-shield-alt"></i> Vai trò
                    </div>
                    <div class="info-value">
                        <?php 
                        $roleLabels = [
                            'user' => 'Người dùng',
                            'admin' => 'Quản trị viên'
                        ];
                        echo $roleLabels[$user['role']] ?? 'Người dùng';
                        ?>
                    </div>
                </div>
            </div>

            <a href="#" class="btn-edit">
                <i class="fas fa-edit"></i> Chỉnh sửa thông tin
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

