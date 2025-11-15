<?php
require_once __DIR__ . '/auth.php';

// Get current user
$user = getCurrentUser();
if (!$user) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get current page to highlight active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .account-sidebar {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .account-sidebar-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        padding: 30px 20px;
        text-align: center;
        color: var(--white);
    }

    .account-sidebar-header .avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 36px;
        border: 3px solid rgba(255, 255, 255, 0.3);
    }

    .account-sidebar-header h3 {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .account-sidebar-header p {
        font-size: 14px;
        opacity: 0.9;
    }

    .account-sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .account-sidebar-menu li {
        border-bottom: 1px solid #f0f0f0;
    }

    .account-sidebar-menu li:last-child {
        border-bottom: none;
    }

    .account-sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        color: var(--dark);
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 15px;
        font-weight: 500;
    }

    .account-sidebar-menu a:hover {
        background-color: var(--light);
        color: var(--primary);
        padding-left: 25px;
    }

    .account-sidebar-menu a.active {
        background-color: var(--light);
        color: var(--primary);
        border-left: 4px solid var(--primary);
        font-weight: 600;
    }

    .account-sidebar-menu a i {
        width: 24px;
        margin-right: 12px;
        font-size: 18px;
        text-align: center;
    }

    .account-sidebar-menu a.logout {
        color: #dc3545;
    }

    .account-sidebar-menu a.logout:hover {
        background-color: #fee;
        color: #c33;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .account-sidebar {
            margin-bottom: 20px;
        }
    }
</style>

<div class="account-sidebar">
    <div class="account-sidebar-header">
        <div class="avatar">
            <i class="fas fa-user"></i>
        </div>
        <h3><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <ul class="account-sidebar-menu">
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/account.php" class="<?php echo ($current_page == 'account.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>Thông tin tài khoản</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/cart.php" class="<?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Giỏ hàng</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/orders.php" class="<?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                <span>Đơn hàng</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/wishlist.php" class="<?php echo ($current_page == 'wishlist.php') ? 'active' : ''; ?>">
                <i class="fas fa-heart"></i>
                <span>Yêu thích</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/addresses.php" class="<?php echo ($current_page == 'addresses.php') ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i>
                <span>Sổ địa chỉ</span>
            </a>
        </li>
        <li>
            <a href="#" class="<?php echo ($current_page == 'land-management.php') ? 'active' : ''; ?>">
                <i class="fas fa-landmark"></i>
                <span>Quản lý đất đai</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/change-password.php" class="<?php echo ($current_page == 'change-password.php') ? 'active' : ''; ?>">
                <i class="fas fa-key"></i>
                <span>Đổi mật khẩu</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </li>
    </ul>
</div>

