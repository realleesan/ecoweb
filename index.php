<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECOWEB - Trồng cây gây rừng</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Top Bar Styles */
        .top-bar {
            background-color: var(--primary);
            padding: 10px 5%;
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
            gap: 20px;
        }

        .hotline {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .hotline i {
            color: var(--secondary);
        }

        .account {
            display: flex;
            align-items: center;
            gap: 5px;
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
            padding: 15px 10px;
            display: block;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
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
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="ECOWEB Logo" onerror="this.src='https://via.placeholder.com/40x40/3C603C/FFFFFF?text=ECOWEB'">
            <span>ECOWEB</span>
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
            <div class="account">
                <i class="far fa-user"></i>
                <span>Tài khoản</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-menu">
        <ul class="menu-list">
            <li><a href="index.php" class="active">Trang chủ</a></li>
            <li><a href="about.php">Giới thiệu</a></li>
            <li><a href="products.php">Sản phẩm</a></li>
            <li><a href="news.php">Tin tức</a></li>
            <li><a href="contact.php">Liên hệ</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div style="min-height: 80vh; background-color: var(--light); padding: 20px;">
        <!-- Main content will be here -->
    </div>

    <!-- Footer -->
    <footer style="background-color: var(--dark); color: var(--white); padding: 50px 5% 20px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; max-width: 1200px; margin: 0 auto;">
            <!-- Về GROWHOPE -->
            <div style="flex: 1; min-width: 200px; margin-bottom: 30px; padding: 0 15px;">
                <h3 style="color: var(--secondary); margin-bottom: 20px; font-size: 18px; position: relative; padding-bottom: 10px;">
                    Về GROWHOPE
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--secondary);"></span>
                </h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 8px;"><a href="index.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Trang chủ</a></li>
                    <li style="margin-bottom: 8px;"><a href="about.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Giới thiệu</a></li>
                    <li style="margin-bottom: 8px;"><a href="products.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Sản phẩm</a></li>
                    <li style="margin-bottom: 8px;"><a href="news.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Tin tức</a></li>
                    <li><a href="contact.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Liên hệ</a></li>
                </ul>
            </div>

            <!-- Sản phẩm của GROWHOPE -->
            <div style="flex: 1; min-width: 200px; margin-bottom: 30px; padding: 0 15px;">
                <h3 style="color: var(--secondary); margin-bottom: 20px; font-size: 18px; position: relative; padding-bottom: 10px;">
                    Sản phẩm của GROWHOPE
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--secondary);"></span>
                </h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Tổ ong</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Cây táo</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Cây xoài</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Cây sầu riêng</a></li>
                    <li><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Cây chanh leo</a></li>
                </ul>
            </div>

            <!-- Chính sách -->
            <div style="flex: 1; min-width: 200px; margin-bottom: 30px; padding: 0 15px;">
                <h3 style="color: var(--secondary); margin-bottom: 20px; font-size: 18px; position: relative; padding-bottom: 10px;">
                    Chính sách
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--secondary);"></span>
                </h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách bảo mật</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách riêng tư</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách thanh toán</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách vận chuyển</a></li>
                    <li><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách bảo hành</a></li>
                </ul>
            </div>

            <!-- Thông tin liên hệ -->
            <div style="flex: 1; min-width: 250px; margin-bottom: 30px; padding: 0 15px;">
                <h3 style="color: var(--secondary); margin-bottom: 20px; font-size: 18px; position: relative; padding-bottom: 10px;">
                    Thông tin liên hệ
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--secondary);"></span>
                </h3>
                <div style="margin-bottom: 20px;">
                    <p style="margin: 0 0 15px 0; color: #ddd; display: flex; align-items: flex-start;">
                        <i class="fas fa-map-marker-alt" style="color: var(--secondary); margin-right: 10px; margin-top: 5px;"></i>
                        <span>123 Đường Số 1, Phường 2, Quận 3, TP.HCM, Việt Nam</span>
                    </p>
                    <p style="margin: 0 0 15px 0; color: #ddd; display: flex; align-items: center;">
                        <i class="fas fa-phone-alt" style="color: var(--secondary); margin-right: 10px;"></i>
                        <span>0123 456 789</span>
                    </p>
                    <p style="margin: 0 0 15px 0; color: #ddd; display: flex; align-items: center;">
                        <i class="fas fa-envelope" style="color: var(--secondary); margin-right: 10px;"></i>
                        <span>info@growhope.vn</span>
                    </p>
                </div>
                
                <h4 style="color: var(--white); margin-bottom: 15px; font-size: 16px;">Phương thức thanh toán</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=VISA" alt="Visa" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=MC" alt="Mastercard" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=COD" alt="COD" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=MOMO" alt="Momo" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div style="text-align: center; padding: 20px 0 0; margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1);">
            <p style="margin: 0; color: #aaa; font-size: 14px;">
                &copy; 2023 GROWHOPE. Tất cả các quyền được bảo lưu.
            </p>
        </div>
    </footer>

    <script>
        // Add active class to current menu item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop() || 'index.php';
            const menuItems = document.querySelectorAll('.menu-list li a');
            
            menuItems.forEach(item => {
                if (item.getAttribute('href') === currentPage) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>