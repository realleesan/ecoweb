<?php 
require_once __DIR__ . '/config.php';

// Get current page and set active states
$current_page = basename($_SERVER['PHP_SELF']);
$is_home = ($current_page == 'index.php' || $current_page == '');

// Determine if we're in the public directory
$is_public = (strpos($_SERVER['PHP_SELF'], 'public') !== false);

// Set paths
$base_path = $is_public ? '' : BASE_URL . '/public/';
$index_link = $is_public ? BASE_URL . '/index.php' : BASE_URL . '/public/index.php';
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
            display: flex;
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
        }

        .account i {
            font-size: 20px;
        }

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
        
        <a href="#" class="land-management-btn">
            Quản lý đất đai
        </a>
        
        <div class="contact-info">
            <div class="hotline">
                <i class="fas fa-phone-alt"></i>
                <span>Hotline: <?php echo CONTACT_HOTLINE; ?></span>
            </div>
            <div class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span>0</span>
            </div>
            <div class="account">
                <i class="far fa-user"></i>
                <span>Tài khoản</span>
            </div>
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