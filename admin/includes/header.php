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
        .admin-actions > a { color: var(--white); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; transition: background 0.3s ease; }
        .admin-actions > a:hover { background: rgba(255,255,255,0.15); }
        
        .admin-user-dropdown { position: relative; }
        .admin-username { font-weight: 600; display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: background 0.3s ease; }
        .admin-username:hover { background: rgba(255,255,255,0.15); }
        .admin-username i { font-size: 18px; }
        
        .dropdown-menu { position: absolute; top: calc(100% + 8px); right: 0; background: var(--white); border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); min-width: 220px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s 0.3s; z-index: 1000; overflow: hidden; }
        
        .admin-user-dropdown:hover .dropdown-menu,
        .dropdown-menu:hover { opacity: 1; visibility: visible; transform: translateY(0); transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s 0s; }
        
        .dropdown-menu::before { content: ''; position: absolute; top: -8px; right: 20px; width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid var(--white); }
        
        .dropdown-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; color: var(--dark); text-decoration: none; transition: background 0.2s ease; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item:hover { background: rgba(210,100,38,0.08); }
        .dropdown-item i { width: 20px; color: var(--secondary); font-size: 16px; }
        .dropdown-item span { font-weight: 500; font-size: 14px; }
        
        .dropdown-header { padding: 14px 18px; border-bottom: 2px solid rgba(210,100,38,0.15); background: rgba(255,247,237,0.5); }
        .dropdown-header-name { font-weight: 700; font-size: 15px; color: var(--dark); margin-bottom: 4px; }
        .dropdown-header-role { font-size: 12px; color: rgba(0,0,0,0.6); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        
        @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
            .admin-topbar { flex-direction: column; gap: 8px; padding: 10px; }
            .dropdown-menu { right: auto; left: 50%; transform: translateX(-50%) translateY(-10px); }
            .admin-user-dropdown:hover .dropdown-menu,
            .dropdown-menu:hover { transform: translateX(-50%) translateY(0); }
        }
    </style>
</head>
<body>
    <div class="admin-topbar">
        <a class="admin-brand" href="<?php echo BASE_URL; ?>/admin/index.php"><i class="fas fa-seedling"></i><span><?php echo BRAND_NAME; ?> Admin</span></a>
        <div class="admin-actions">
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="fas fa-home"></i> Trang người dùng</a>
            
            <div class="admin-user-dropdown">
                <div class="admin-username">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
                    <i class="fas fa-chevron-down" style="font-size:12px;"></i>
                </div>
                <div class="dropdown-menu">
                    <div class="dropdown-header">
                        <div class="dropdown-header-name"><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></div>
                        <div class="dropdown-header-role">Quản Trị Viên</div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/auth/account.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>Tài khoản (User)</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng xuất</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

