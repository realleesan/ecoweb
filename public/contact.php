<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - ECOWEB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3C603C;
            --secondary: #D26426;
            --dark: #74493D;
            --light: #FFF7ED;
            --white: #FFFFFF;
            --bg-green: #9FBD48;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
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
            font-weight: 700;
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

        /* Contact Page Content */
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-header h1 {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .contact-header p {
            font-size: 16px;
            color: var(--dark);
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .contact-card {
            background-color: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 4px solid var(--secondary);
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .contact-card-icon {
            width: 60px;
            height: 60px;
            background-color: var(--bg-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .contact-card-icon i {
            font-size: 24px;
            color: var(--white);
        }

        .contact-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .contact-card p {
            font-size: 14px;
            color: var(--dark);
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .contact-card a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .contact-card a:hover {
            color: var(--primary);
        }

        .map-section {
            margin-top: 50px;
            background-color: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .map-section h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--bg-green);
        }

        .map-placeholder {
            color: var(--dark);
            font-size: 16px;
        }

        /* Contact Form */
        .contact-form-section {
            margin-bottom: 50px;
            background-color: var(--white);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .contact-form-section h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 30px;
            text-align: center;
        }

        .contact-form {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary);
            font-size: 14px;
        }

        .form-group label .required {
            color: var(--secondary);
            margin-left: 3px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s ease;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--bg-green);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .form-submit {
            text-align: center;
            margin-top: 30px;
        }

        .btn-submit {
            background-color: var(--secondary);
            color: var(--white);
            padding: 14px 40px;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(210, 100, 38, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .form-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .form-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .form-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: var(--white);
            padding: 50px 5% 20px;
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 30px;
            padding: 0 15px;
        }

        .footer-section h3 {
            color: var(--secondary);
            margin-bottom: 20px;
            font-size: 18px;
            position: relative;
            padding-bottom: 10px;
            font-weight: 700;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: var(--secondary);
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 8px;
        }

        .footer-section a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: var(--secondary);
        }

        .footer-section p {
            margin: 0 0 15px 0;
            color: #ddd;
            display: flex;
            align-items: flex-start;
            font-size: 14px;
        }

        .footer-section i {
            color: var(--secondary);
            margin-right: 10px;
            margin-top: 5px;
        }

        .copyright {
            text-align: center;
            padding: 20px 0 0;
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .copyright p {
            margin: 0;
            color: #aaa;
            font-size: 14px;
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

            .contact-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 576px) {
            .menu-list {
                flex-direction: column;
                text-align: center;
            }

            .contact-header h1 {
                font-size: 28px;
            }

            .contact-card {
                padding: 20px;
            }

            .map-container {
                height: 300px;
            }

            .contact-form-section {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <a href="../index.php" class="logo">
            <img src="../images/logo.png" alt="ECOWEB Logo" onerror="this.src='https://via.placeholder.com/40x40/3C603C/FFFFFF?text=ECOWEB'">
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
            <li><a href="../index.php">Trang chủ</a></li>
            <li><a href="about.php">Giới thiệu</a></li>
            <li><a href="products.php">Sản phẩm</a></li>
            <li><a href="news.php">Tin tức</a></li>
            <li><a href="contact.php" class="active">Liên hệ</a></li>
        </ul>
    </nav>

    <!-- Contact Page Content -->
    <div class="contact-container">
        <div class="contact-header">
            <h1>Liên hệ với chúng tôi</h1>
            <p>Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn. Hãy liên hệ với chúng tôi qua các kênh sau:</p>
        </div>

        <!-- Contact Form Section -->
        <div class="contact-form-section">
            <h2>Gửi tin nhắn cho chúng tôi</h2>
            <form class="contact-form" id="contactForm" method="POST" action="">
                <div id="formMessage" class="form-message"></div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Họ và tên <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required placeholder="Nhập họ và tên của bạn">
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" required placeholder="Nhập số điện thoại">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required placeholder="Nhập địa chỉ email">
                    </div>
                    <div class="form-group">
                        <label for="subject">Chủ đề <span class="required">*</span></label>
                        <select id="subject" name="subject" required>
                            <option value="">-- Chọn chủ đề --</option>
                            <option value="product">Hỏi về sản phẩm</option>
                            <option value="order">Hỏi về đơn hàng</option>
                            <option value="support">Hỗ trợ kỹ thuật</option>
                            <option value="partnership">Hợp tác</option>
                            <option value="feedback">Góp ý</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="message">Nội dung tin nhắn <span class="required">*</span></label>
                    <textarea id="message" name="message" required placeholder="Nhập nội dung tin nhắn của bạn..."></textarea>
                </div>

                <div class="form-submit">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        Gửi tin nhắn
                    </button>
                </div>
            </form>
        </div>

        <div class="contact-grid">
            <!-- Địa chỉ -->
            <div class="contact-card">
                <div class="contact-card-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>Địa chỉ</h3>
                <p><strong>Trụ sở chính:</strong></p>
                <p>123 Đường Số 1, Phường 2, Quận 3</p>
                <p>Thành phố Hồ Chí Minh, Việt Nam</p>
                <p style="margin-top: 15px;"><strong>Chi nhánh:</strong></p>
                <p>456 Đường ABC, Phường XYZ, Quận 1</p>
                <p>Thành phố Hồ Chí Minh, Việt Nam</p>
            </div>

            <!-- Điện thoại -->
            <div class="contact-card">
                <div class="contact-card-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h3>Điện thoại</h3>
                <p><strong>Hotline:</strong></p>
                <p><a href="tel:0123456789">0123 456 789</a></p>
                <p style="margin-top: 15px;"><strong>Điện thoại bàn:</strong></p>
                <p><a href="tel:0281234567">(028) 1234 567</a></p>
                <p style="margin-top: 15px;"><strong>Thời gian làm việc:</strong></p>
                <p>Thứ 2 - Thứ 6: 8:00 - 17:30</p>
                <p>Thứ 7: 8:00 - 12:00</p>
            </div>

            <!-- Email -->
            <div class="contact-card">
                <div class="contact-card-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Email</h3>
                <p><strong>Email chính:</strong></p>
                <p><a href="mailto:info@growhope.vn">info@growhope.vn</a></p>
                <p style="margin-top: 15px;"><strong>Hỗ trợ khách hàng:</strong></p>
                <p><a href="mailto:support@growhope.vn">support@growhope.vn</a></p>
                <p style="margin-top: 15px;"><strong>Đối tác:</strong></p>
                <p><a href="mailto:partner@growhope.vn">partner@growhope.vn</a></p>
            </div>

            <!-- Mạng xã hội -->
            <div class="contact-card">
                <div class="contact-card-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                <h3>Mạng xã hội</h3>
                <p><strong>Facebook:</strong></p>
                <p><a href="https://facebook.com/growhope" target="_blank">facebook.com/growhope</a></p>
                <p style="margin-top: 15px;"><strong>Instagram:</strong></p>
                <p><a href="https://instagram.com/growhope" target="_blank">@growhope</a></p>
                <p style="margin-top: 15px;"><strong>Zalo:</strong></p>
                <p><a href="tel:0123456789">0123 456 789</a></p>
            </div>

            <!-- Giờ làm việc -->
            <div class="contact-card">
                <div class="contact-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Giờ làm việc</h3>
                <p><strong>Văn phòng:</strong></p>
                <p>Thứ 2 - Thứ 6: 8:00 - 17:30</p>
                <p>Thứ 7: 8:00 - 12:00</p>
                <p>Chủ nhật: Nghỉ</p>
                <p style="margin-top: 15px;"><strong>Hotline 24/7:</strong></p>
                <p>Hỗ trợ khẩn cấp: <a href="tel:0123456789">0123 456 789</a></p>
            </div>

            <!-- Phương thức thanh toán -->
            <div class="contact-card">
                <div class="contact-card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3>Phương thức thanh toán</h3>
                <p>Chúng tôi chấp nhận thanh toán qua:</p>
                <p style="margin-top: 10px;">• Tiền mặt (COD)</p>
                <p>• Chuyển khoản ngân hàng</p>
                <p>• Thẻ tín dụng/ghi nợ</p>
                <p>• Ví điện tử (Momo, ZaloPay)</p>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h2>Bản đồ đường đi</h2>
            <div class="map-container">
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt" style="font-size: 48px; color: var(--bg-green); margin-bottom: 15px; display: block;"></i>
                    <p>123 Đường Số 1, Phường 2, Quận 3, TP.HCM</p>
                    <p style="margin-top: 10px; font-size: 14px; color: var(--dark);">Bản đồ sẽ được tích hợp tại đây</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <!-- Về GROWHOPE -->
            <div class="footer-section">
                <h3>Về GROWHOPE</h3>
                <ul>
                    <li><a href="../index.php">Trang chủ</a></li>
                    <li><a href="about.php">Giới thiệu</a></li>
                    <li><a href="products.php">Sản phẩm</a></li>
                    <li><a href="news.php">Tin tức</a></li>
                    <li><a href="contact.php">Liên hệ</a></li>
                </ul>
            </div>

            <!-- Sản phẩm của GROWHOPE -->
            <div class="footer-section">
                <h3>Sản phẩm của GROWHOPE</h3>
                <ul>
                    <li><a href="#">Tổ ong</a></li>
                    <li><a href="#">Cây táo</a></li>
                    <li><a href="#">Cây xoài</a></li>
                    <li><a href="#">Cây sầu riêng</a></li>
                    <li><a href="#">Cây chanh leo</a></li>
                </ul>
            </div>

            <!-- Chính sách -->
            <div class="footer-section">
                <h3>Chính sách</h3>
                <ul>
                    <li><a href="#">Chính sách bảo mật</a></li>
                    <li><a href="#">Chính sách riêng tư</a></li>
                    <li><a href="#">Chính sách thanh toán</a></li>
                    <li><a href="#">Chính sách vận chuyển</a></li>
                    <li><a href="#">Chính sách bảo hành</a></li>
                </ul>
            </div>

            <!-- Thông tin liên hệ -->
            <div class="footer-section">
                <h3>Thông tin liên hệ</h3>
                <p>
                    <i class="fas fa-map-marker-alt"></i>
                    <span>123 Đường Số 1, Phường 2, Quận 3, TP.HCM, Việt Nam</span>
                </p>
                <p>
                    <i class="fas fa-phone-alt"></i>
                    <span>0123 456 789</span>
                </p>
                <p>
                    <i class="fas fa-envelope"></i>
                    <span>info@growhope.vn</span>
                </p>
                
                <h4 style="color: var(--white); margin: 20px 0 15px 0; font-size: 16px; font-weight: 700;">Phương thức thanh toán</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=VISA" alt="Visa" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=MC" alt="Mastercard" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=COD" alt="COD" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=MOMO" alt="Momo" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="copyright">
            <p>&copy; 2023 GROWHOPE. Tất cả các quyền được bảo lưu.</p>
        </div>
    </footer>

    <script>
        // Add active class to current menu item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop() || 'contact.php';
            const menuItems = document.querySelectorAll('.menu-list li a');
            
            menuItems.forEach(item => {
                if (item.getAttribute('href') === currentPage || item.getAttribute('href').includes('contact')) {
                    item.classList.add('active');
                }
            });
        });

        // Form validation and submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formMessage = document.getElementById('formMessage');
            const formData = new FormData(form);
            
            // Basic validation
            const name = formData.get('name').trim();
            const email = formData.get('email').trim();
            const phone = formData.get('phone').trim();
            const subject = formData.get('subject');
            const message = formData.get('message').trim();
            
            if (!name || !email || !phone || !subject || !message) {
                showMessage('Vui lòng điền đầy đủ thông tin bắt buộc!', 'error');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage('Vui lòng nhập địa chỉ email hợp lệ!', 'error');
                return;
            }
            
            // Phone validation (Vietnamese phone number)
            const phoneRegex = /^(0|\+84)[0-9]{9,10}$/;
            const cleanPhone = phone.replace(/\s/g, '');
            if (!phoneRegex.test(cleanPhone)) {
                showMessage('Vui lòng nhập số điện thoại hợp lệ!', 'error');
                return;
            }
            
            // Simulate form submission (you can replace this with actual AJAX call)
            showMessage('Đang gửi tin nhắn...', 'success');
            
            // Simulate API call
            setTimeout(function() {
                showMessage('Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.', 'success');
                form.reset();
                
                // Scroll to message
                formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 1500);
        });
        
        function showMessage(text, type) {
            const formMessage = document.getElementById('formMessage');
            formMessage.textContent = text;
            formMessage.className = 'form-message ' + type;
            
            // Auto hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    formMessage.style.display = 'none';
                }, 5000);
            }
        }
    </script>
</body>
</html>
