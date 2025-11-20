<?php
require_once __DIR__ . '/config.php';




// Check if user is logged in
$is_logged_in = false;
$current_user_name = 'Tài khoản';




if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




// Verify session is valid
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Double check session values exist
    if (isset($_SESSION['username']) || isset($_SESSION['full_name'])) {
        $is_logged_in = true;
        // Get display name
        if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
            $current_user_name = htmlspecialchars($_SESSION['full_name']);
        } elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
            $current_user_name = htmlspecialchars($_SESSION['username']);
        }
    } else {
        // Session has user_id but missing username/full_name - invalid session, clear it
        session_unset();
        session_destroy();
        session_start();
    }
}




// Get current page and set active states
$current_page = basename($_SERVER['PHP_SELF']);
$is_home = ($current_page == 'index.php' || $current_page == '');




// Determine current directory from script path
$script_path = $_SERVER['PHP_SELF'];
$is_public = (strpos($script_path, '/public/') !== false);
$is_auth = (strpos($script_path, '/auth/') !== false);
$is_views = (strpos($script_path, '/views/') !== false);
$is_admin = (strpos($script_path, '/admin/') !== false);
$is_root = !$is_public && !$is_auth && !$is_views && !$is_admin;




// Set paths based on current directory
if ($is_root) {
    // Root directory (index.php)
    $base_path = BASE_URL . '/public/';
    $index_link = BASE_URL . '/index.php';
} elseif ($is_public) {
    // Public directory
    $base_path = '';
    $index_link = BASE_URL . '/index.php';
} elseif ($is_auth) {
    // Auth directory
    $base_path = BASE_URL . '/public/';
    $index_link = BASE_URL . '/index.php';
} elseif ($is_views) {
    // Views directory
    $base_path = BASE_URL . '/public/';
    $index_link = BASE_URL . '/index.php';
} else {
    // Default fallback
    $base_path = BASE_URL . '/public/';
    $index_link = BASE_URL . '/index.php';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_TAGLINE; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo FONT_GOOGLE_URL; ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Leaflet CSS/JS (Open-source, free) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --primary: <?php echo COLOR_PRIMARY; ?>;
            --secondary: <?php echo COLOR_SECONDARY; ?>;
            --dark: <?php echo COLOR_DARK; ?>;
            --light: <?php echo COLOR_LIGHT; ?>;
            --white: <?php echo COLOR_WHITE; ?>;
            --bg-green: <?php echo COLOR_BG_GREEN; ?>;
        }




        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        }




        h1, h2, h3, h4, h5, h6, .bold {
            font-weight: 600;
        }




        /* Top Bar Styles */
        .top-bar {
            background-color: var(--primary);
            padding: 8px <?php echo CONTAINER_PADDING; ?>;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--white);
            gap: 20px;
        }




        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }




        .logo img {
            height: 40px;
            margin-right: 10px;
        }




        .search-bar {
            flex-grow: 1;
            max-width: 500px;
            margin-left: 20px;
            position: relative;
        }




        .search-bar input {
            width: 100%;
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            outline: none;
            padding-right: 40px;
        }




        .search-bar i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark);
        }




        .contact-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }




        .hotline, .account, .cart-icon {
            display: flex;
            align-items: center;
            gap: 5px;
            height: 100%;
            padding: 5px 0;
        }




        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 20px;
        }




        .cart-icon span {
            position: absolute;
            top: -5px;
            right: -10px;
            background: var(--secondary);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }




        .hotline i {
            color: var(--secondary);
        }




        .account {
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: opacity 0.3s ease;
        }




        .account:hover {
            opacity: 0.8;
        }




        .account i {
            font-size: 20px;
        }




        /* Admin dropdown */
        .admin-account { position: relative; }
        .admin-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: var(--white); border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); min-width: 220px; display: none; overflow: hidden; z-index: 1000; }
        .admin-account:hover .admin-dropdown { display: block; }
        .admin-dropdown a { display: flex; align-items: center; gap: 10px; padding: 12px 14px; text-decoration: none; color: var(--dark); transition: background 0.25s ease; }
        .admin-dropdown a:hover { background: var(--light); }
        .admin-dropdown .divider { height: 1px; background: #eee; margin: 0; }




        .land-management-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background-color: var(--white);
            color: var(--primary);
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            flex-shrink: 0;
            margin-right: 15px;
        }




        .land-management-btn:hover {
            background-color: var(--secondary);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }




        /* Navigation Menu */
        .main-menu {
            background-color: var(--dark);
            padding: 0;
            display: flex;
            justify-content: center;
        }




        .menu-list {
            list-style: none;
            display: flex;
            margin: 0 auto;
            padding: 0;
            width: 100%;
            max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
            justify-content: space-around;
        }




        .menu-list li {
            position: relative;
            flex: 1;
            text-align: center;
        }




        .menu-list li a {
            color: var(--white);
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
            font-size: 15px;
        }




        .menu-list li a:hover,
        .menu-list li a.active {
            background-color: var(--secondary);
        }




        /* Responsive */
        @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
            .top-bar {
                flex-direction: column;
                gap: 10px;
                padding: 10px;
            }




            .search-bar {
                width: 100%;
                max-width: 100%;
                margin: 0;
            }




            .land-management-btn {
                width: 100%;
                margin: 10px 0;
            }




            .contact-info {
                width: 100%;
                justify-content: space-between;
            }
        }




        @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
            .menu-list {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <a href="<?php echo $index_link; ?>" class="logo">
            <span style="font-size: 28px; font-weight: 700;"><?php echo BRAND_NAME; ?></span>
        </a>
       
        <form class="search-bar" method="GET" action="<?php echo $base_path; ?>search.php">
            <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button type="submit" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 0;">
                <i class="fas fa-search" style="color: var(--dark);"></i>
            </button>
        </form>
       
        <div style="flex: 1;"></div>
       
        <?php if ($is_logged_in): ?>
            <a href="<?php echo BASE_URL; ?>/auth/my-trees.php" class="land-management-btn" style="margin-right: 10px;">
                <i class="fas fa-tree"></i> Cây của tôi
            </a>
            <a href="<?php echo BASE_URL; ?>/auth/lands.php" class="land-management-btn">
                <i class="fas fa-landmark"></i> Quản lý đất đai
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/auth/login.php?next=<?php echo rawurlencode(BASE_URL . '/auth/lands.php'); ?>" class="land-management-btn">
                Quản lý đất đai
            </a>
        <?php endif; ?>
       
        <div class="contact-info">
            <div class="hotline">
                <i class="fas fa-phone-alt"></i>
                <span>Hotline: <?php echo CONTACT_HOTLINE; ?></span>
            </div>
            <a href="<?php echo $is_logged_in ? BASE_URL . '/auth/cart.php' : BASE_URL . '/auth/login.php'; ?>" class="cart-icon" style="text-decoration: none; color: inherit;">
                <i class="fas fa-shopping-cart"></i>
                <span id="cart-count">0</span>
            </a>
            <?php if ($is_logged_in && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="admin-account">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php" class="account" style="text-decoration: none; color: inherit;">
                        <i class="far fa-user"></i>
                        <span>Quản Trị Viên</span>
                    </a>
                    <div class="admin-dropdown">
                        <a href="<?php echo BASE_URL; ?>/auth/account.php"><i class="fas fa-user"></i> <span>Tài khoản (User)</span></a>
                        <div class="divider"></div>
                        <a href="<?php echo BASE_URL; ?>/admin/index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard (Admin)</span></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $is_logged_in ? BASE_URL . '/auth/account.php' : BASE_URL . '/auth/login.php'; ?>" class="account" style="text-decoration: none; color: inherit;">
                    <i class="far fa-user"></i>
                    <span><?php echo $current_user_name; ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>




    <!-- Main Navigation -->
    <nav class="main-menu">
        <?php
        // Get the current page filename
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <ul class="menu-list">
            <li><a href="<?php echo $index_link; ?>" class="<?php echo $is_home ? 'active' : ''; ?>">Trang chủ</a></li>
            <li><a href="<?php echo $base_path; ?>about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">Giới thiệu</a></li>
            <li><a href="<?php echo $base_path; ?>products.php" class="<?php echo ($current_page == 'products.php') ? 'active' : ''; ?>">Sản phẩm</a></li>
            <li><a href="<?php echo $base_path; ?>categories.php" class="<?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>">Danh mục</a></li>
            <li><a href="<?php echo $base_path; ?>news.php" class="<?php echo ($current_page == 'news.php') ? 'active' : ''; ?>">Tin tức</a></li>
            <li><a href="<?php echo $base_path; ?>contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">Liên hệ</a></li>
        </ul>
    </nav>




<script>
    // Update cart count on page load
    <?php if ($is_logged_in): ?>
    fetch('<?php echo BASE_URL; ?>/api/get-cart-count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCountElement = document.getElementById('cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = data.count;
                    if (data.count > 0) {
                        cartCountElement.style.display = 'flex';
                    } else {
                        cartCountElement.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error fetching cart count:', error);
        });
    <?php endif; ?>
</script>





