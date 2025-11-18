<?php
require_once __DIR__ . '/../../includes/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo FONT_GOOGLE_URL; ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: <?php echo COLOR_PRIMARY; ?>;
            --secondary: <?php echo COLOR_SECONDARY; ?>;
            --dark: <?php echo COLOR_DARK; ?>;
            --light: <?php echo COLOR_LIGHT; ?>;
            --white: <?php echo COLOR_WHITE; ?>;
            --bg-green: <?php echo COLOR_BG_GREEN; ?>;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; }
        body { margin: 0; background-color: var(--light); color: var(--dark); }
        .admin-topbar { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 12px <?php echo CONTAINER_PADDING; ?>; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: var(--white); }
        .admin-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--white); font-weight: 700; font-size: 22px; }
        .admin-actions { display: flex; align-items: center; gap: 15px; }
        .admin-actions a { color: var(--white); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; transition: background 0.3s ease; }
        .admin-actions a:hover { background: rgba(255,255,255,0.15); }
        .admin-username { font-weight: 600; }
        @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
            .admin-topbar { flex-direction: column; gap: 8px; padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="admin-topbar">
        <a class="admin-brand" href="<?php echo BASE_URL; ?>/admin/index.php"><i class="fas fa-seedling"></i><span><?php echo BRAND_NAME; ?> Admin</span></a>
        <div class="admin-actions">
            <span class="admin-username"><i class="fas fa-user-circle"></i> <?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="fas fa-home"></i> Trang người dùng</a>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>

