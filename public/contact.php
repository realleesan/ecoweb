<?php 
require_once '../includes/config.php';
include '../includes/header.php'; 
?>

<style>
    body {
        background-color: var(--light);
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
    }

    /* Contact Page Content */
    .contact-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 0 auto;
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
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
        max-width: <?php echo CONTAINER_MAX_WIDTH_XSMALL; ?>;
        margin: 0 auto;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: <?php echo GRID_GAP; ?>;
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
        max-width: <?php echo CONTAINER_MAX_WIDTH_SMALL; ?>;
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

    /* Responsive */
    @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
        .contact-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
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
            <p><?php echo CONTACT_ADDRESS; ?></p>
            <p style="margin-top: 15px;"><strong>Chi nhánh:</strong></p>
            <p><?php echo CONTACT_ADDRESS_BRANCH; ?></p>
        </div>

        <!-- Điện thoại -->
        <div class="contact-card">
            <div class="contact-card-icon">
                <i class="fas fa-phone-alt"></i>
            </div>
            <h3>Điện thoại</h3>
            <p><strong>Hotline:</strong></p>
            <p><a href="tel:<?php echo str_replace(' ', '', CONTACT_HOTLINE); ?>"><?php echo CONTACT_HOTLINE; ?></a></p>
            <p style="margin-top: 15px;"><strong>Điện thoại bàn:</strong></p>
            <p><a href="tel:<?php echo str_replace(' ', '', CONTACT_PHONE); ?>"><?php echo CONTACT_PHONE; ?></a></p>
            <p style="margin-top: 15px;"><strong>Thời gian làm việc:</strong></p>
            <p><?php echo CONTACT_WORKING_HOURS; ?></p>
        </div>

        <!-- Email -->
        <div class="contact-card">
            <div class="contact-card-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3>Email</h3>
            <p><strong>Email chính:</strong></p>
            <p><a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a></p>
            <p style="margin-top: 15px;"><strong>Hỗ trợ khách hàng:</strong></p>
            <p><a href="mailto:<?php echo CONTACT_EMAIL_SUPPORT; ?>"><?php echo CONTACT_EMAIL_SUPPORT; ?></a></p>
            <p style="margin-top: 15px;"><strong>Đối tác:</strong></p>
            <p><a href="mailto:<?php echo CONTACT_EMAIL_PARTNER; ?>"><?php echo CONTACT_EMAIL_PARTNER; ?></a></p>
        </div>

        <!-- Mạng xã hội -->
        <div class="contact-card">
            <div class="contact-card-icon">
                <i class="fas fa-share-alt"></i>
            </div>
            <h3>Mạng xã hội</h3>
            <p><strong>Facebook:</strong></p>
            <p><a href="<?php echo SOCIAL_FACEBOOK; ?>" target="_blank"><?php echo SOCIAL_FACEBOOK; ?></a></p>
            <p style="margin-top: 15px;"><strong>Instagram:</strong></p>
            <p><a href="<?php echo SOCIAL_INSTAGRAM; ?>" target="_blank"><?php echo SOCIAL_INSTAGRAM; ?></a></p>
            <p style="margin-top: 15px;"><strong>Zalo:</strong></p>
            <p><a href="tel:<?php echo str_replace(' ', '', SOCIAL_ZALO); ?>"><?php echo SOCIAL_ZALO; ?></a></p>
        </div>

        <!-- Giờ làm việc -->
        <div class="contact-card">
            <div class="contact-card-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h3>Giờ làm việc</h3>
            <p><strong>Văn phòng:</strong></p>
            <p><?php echo CONTACT_WORKING_HOURS; ?></p>
            <p>Chủ nhật: Nghỉ</p>
            <p style="margin-top: 15px;"><strong>Hotline 24/7:</strong></p>
            <p>Hỗ trợ khẩn cấp: <a href="tel:<?php echo str_replace(' ', '', CONTACT_HOTLINE); ?>"><?php echo CONTACT_HOTLINE; ?></a></p>
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
                <p><?php echo CONTACT_ADDRESS; ?></p>
                <p style="margin-top: 10px; font-size: 14px; color: var(--dark);">Bản đồ sẽ được tích hợp tại đây</p>
            </div>
        </div>
    </div>
</div>

<script>
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

<?php include '../includes/footer.php'; ?>
