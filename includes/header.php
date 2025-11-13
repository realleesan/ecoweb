<?php
// Xác định base path
$current_dir = dirname($_SERVER['PHP_SELF']);
$base_path = '';
$public_path = 'public/';
if (strpos($current_dir, '/public') !== false || basename(dirname($_SERVER['PHP_SELF'])) == 'public') {
    $base_path = '../';
    $public_path = '';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECOWEB - Trồng cây gây rừng</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3C603C;
            --secondary: #D26426;
            --dark: #74493D;
            --light: #FFF7ED;
            --white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        h1, h2, h3, h4, h5, h6, .bold {
            font-weight: 600;
        }

        /* Top Bar Styles */
        .top-bar {
            background-color: var(--primary);
            padding: 8px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--white);
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 40px;
            margin-right: 10px;
        }

        .search-bar {
            flex-grow: 1;
            max-width: 500px;
            margin: 0 20px;
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
            max-width: 1200px;
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
        @media (max-width: 992px) {
            .top-bar {
                flex-direction: column;
                gap: 10px;
                padding: 10px;
            }

            .search-bar {
                width: 100%;
                max-width: 100%;
                margin: 10px 0;
            }

            .contact-info {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 576px) {
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
        <a href="<?php echo $base_path; ?>index.php" class="logo">
            <span style="font-size: 28px; font-weight: 700;">GROWHOPE</span>
        </a>
        
        <div class="search-bar">
            <input type="text" placeholder="Tìm kiếm sản phẩm...">
            <i class="fas fa-search"></i>
        </div>
        
        <div class="contact-info">
            <div class="hotline">
                <i class="fas fa-phone-alt"></i>
                <span>Hotline: 0123 456 789</span>
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
        <ul class="menu-list">
            <li><a href="<?php echo $base_path; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Trang chủ</a></li>
            <li><a href="<?php echo $base_path . $public_path; ?>about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">Giới thiệu</a></li>
            <li><a href="<?php echo $base_path . $public_path; ?>products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">Sản phẩm</a></li>
            <li><a href="<?php echo $base_path . $public_path; ?>categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">Danh mục</a></li>
            <li><a href="<?php echo $base_path . $public_path; ?>news.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'news.php' ? 'active' : ''; ?>">Tin tức</a></li>
            <li><a href="<?php echo $base_path . $public_path; ?>contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Liên hệ</a></li>
        </ul>
    </nav>