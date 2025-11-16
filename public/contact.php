<?php 
require_once '../includes/config.php';
require_once '../includes/database.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once '../library/phpmailer/src/Exception.php';
require_once '../library/phpmailer/src/PHPMailer.php';
require_once '../library/phpmailer/src/SMTP.php';

$formMessage = '';
$formMessageType = '';

// Xử lý form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    // Lấy dữ liệu từ form
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Vui lòng nhập họ và tên';
    }
    
    if (empty($email)) {
        $errors[] = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    }
    
    if (empty($phone)) {
        $errors[] = 'Vui lòng nhập số điện thoại';
    } else {
        $cleanPhone = preg_replace('/\s+/', '', $phone);
        if (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $cleanPhone)) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
    }
    
    if (empty($subject)) {
        $errors[] = 'Vui lòng chọn chủ đề';
    }
    
    if (empty($message)) {
        $errors[] = 'Vui lòng nhập nội dung tin nhắn';
    }
    
    // Nếu không có lỗi, xử lý
    if (empty($errors)) {
        try {
            $pdo = getPDO();
            
            // Lưu vào database
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, status) 
                VALUES (:name, :email, :phone, :subject, :message, 'new')
            ");
            
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':subject' => $subject,
                ':message' => $message
            ]);
            
            $messageId = $pdo->lastInsertId();
            
            // Gửi email bằng PHPMailer
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port = SMTP_PORT;
                $mail->CharSet = SMTP_CHARSET;
                
                // Recipients
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                // Gửi email đến admin để nhận tin nhắn liên hệ
                $mail->addAddress(CONTACT_EMAIL_RECEIVE); // Email admin thực tế để nhận tin nhắn
                $mail->addReplyTo($email, $name); // Email người gửi để reply
                
                // Subject mapping
                $subjectLabels = [
                    'product' => 'Hỏi về sản phẩm',
                    'order' => 'Hỏi về đơn hàng',
                    'support' => 'Hỗ trợ kỹ thuật',
                    'partnership' => 'Hợp tác',
                    'feedback' => 'Góp ý',
                    'other' => 'Khác'
                ];
                $subjectLabel = $subjectLabels[$subject] ?? $subject;
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Tin nhắn liên hệ mới: ' . $subjectLabel;
                
                // Email body với styling phù hợp
                $mail->Body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body {
                            font-family: ' . FONT_FAMILY . ', sans-serif;
                            line-height: 1.6;
                            color: #333;
                            background-color: #f5f5f5;
                            padding: 20px;
                        }
                        .email-container {
                            max-width: 600px;
                            margin: 0 auto;
                            background-color: #ffffff;
                            border-radius: 12px;
                            padding: 30px;
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        }
                        .email-header {
                            background-color: ' . COLOR_PRIMARY . ';
                            color: #ffffff;
                            padding: 20px;
                            border-radius: 8px 8px 0 0;
                            margin: -30px -30px 30px -30px;
                            text-align: center;
                        }
                        .email-header h1 {
                            margin: 0;
                            font-size: 24px;
                            font-weight: 700;
                        }
                        .info-row {
                            margin-bottom: 15px;
                            padding-bottom: 15px;
                            border-bottom: 1px solid #e0e0e0;
                        }
                        .info-label {
                            font-weight: 600;
                            color: ' . COLOR_PRIMARY . ';
                            margin-bottom: 5px;
                            font-size: 14px;
                        }
                        .info-value {
                            color: #333;
                            font-size: 14px;
                        }
                        .message-box {
                            background-color: ' . COLOR_LIGHT . ';
                            padding: 20px;
                            border-radius: 8px;
                            border-left: 4px solid ' . COLOR_SECONDARY . ';
                            margin-top: 20px;
                        }
                        .message-box .info-label {
                            margin-bottom: 10px;
                        }
                        .footer {
                            margin-top: 30px;
                            padding-top: 20px;
                            border-top: 1px solid #e0e0e0;
                            text-align: center;
                            color: #666;
                            font-size: 12px;
                        }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <div class="email-header">
                            <h1>Tin nhắn liên hệ mới từ ' . htmlspecialchars(SITE_NAME) . '</h1>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Họ và tên:</div>
                            <div class="info-value">' . htmlspecialchars($name) . '</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Số điện thoại:</div>
                            <div class="info-value"><a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Chủ đề:</div>
                            <div class="info-value">' . htmlspecialchars($subjectLabel) . '</div>
                        </div>
                        
                        <div class="message-box">
                            <div class="info-label">Nội dung tin nhắn:</div>
                            <div class="info-value" style="white-space: pre-wrap;">' . nl2br(htmlspecialchars($message)) . '</div>
                        </div>
                        
                        <div class="footer">
                            <p>Tin nhắn này được gửi từ form liên hệ trên website ' . SITE_NAME . '</p>
                            <p>Thời gian: ' . date(DATETIME_FORMAT) . '</p>
                            <p>ID tin nhắn: #' . $messageId . '</p>
                        </div>
                    </div>
                </body>
                </html>
                ';
                
                // Plain text version
                $mail->AltBody = "Tin nhắn liên hệ mới từ " . SITE_NAME . "\n\n" .
                    "Họ và tên: " . $name . "\n" .
                    "Email: " . $email . "\n" .
                    "Số điện thoại: " . $phone . "\n" .
                    "Chủ đề: " . $subjectLabel . "\n\n" .
                    "Nội dung:\n" . $message . "\n\n" .
                    "Thời gian: " . date(DATETIME_FORMAT) . "\n" .
                    "ID tin nhắn: #" . $messageId;
                
                // Gửi email đến admin
                if (!empty(SMTP_PASSWORD)) {
                    $mail->send();
                }
                
                // Gửi email cảm ơn tự động đến người dùng
                try {
                    $thankYouMail = new PHPMailer(true);
                    
                    // Server settings (tái sử dụng cấu hình)
                    $thankYouMail->isSMTP();
                    $thankYouMail->Host = SMTP_HOST;
                    $thankYouMail->SMTPAuth = true;
                    $thankYouMail->Username = SMTP_USERNAME;
                    $thankYouMail->Password = SMTP_PASSWORD;
                    $thankYouMail->SMTPSecure = SMTP_SECURE;
                    $thankYouMail->Port = SMTP_PORT;
                    $thankYouMail->CharSet = SMTP_CHARSET;
                    
                    // Recipients - gửi đến người dùng
                    $thankYouMail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $thankYouMail->addAddress($email, $name); // Email người dùng đã nhập
                    
                    // Content
                    $thankYouMail->isHTML(true);
                    $thankYouMail->Subject = 'Cảm ơn bạn đã liên hệ với ' . SITE_NAME;
                    
                    // Email cảm ơn với styling đẹp
                    $thankYouMail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body {
                                font-family: ' . FONT_FAMILY . ', sans-serif;
                                line-height: 1.6;
                                color: #333;
                                background-color: #f5f5f5;
                                padding: 20px;
                            }
                            .email-container {
                                max-width: 600px;
                                margin: 0 auto;
                                background-color: #ffffff;
                                border-radius: 12px;
                                padding: 30px;
                                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                            }
                            .email-header {
                                background: linear-gradient(135deg, ' . COLOR_PRIMARY . ' 0%, ' . COLOR_BG_GREEN . ' 100%);
                                color: #ffffff;
                                padding: 40px 20px;
                                border-radius: 8px 8px 0 0;
                                margin: -30px -30px 30px -30px;
                                text-align: center;
                            }
                            .email-header h1 {
                                margin: 0;
                                font-size: 28px;
                                font-weight: 700;
                            }
                            .email-header .icon {
                                font-size: 48px;
                                margin-bottom: 15px;
                            }
                            .content {
                                margin: 30px 0;
                            }
                            .greeting {
                                font-size: 18px;
                                color: ' . COLOR_PRIMARY . ';
                                font-weight: 600;
                                margin-bottom: 20px;
                            }
                            .message {
                                font-size: 16px;
                                color: #555;
                                line-height: 1.8;
                                margin-bottom: 25px;
                            }
                            .info-box {
                                background-color: ' . COLOR_LIGHT . ';
                                padding: 20px;
                                border-radius: 8px;
                                border-left: 4px solid ' . COLOR_SECONDARY . ';
                                margin: 25px 0;
                            }
                            .info-box p {
                                margin: 8px 0;
                                font-size: 14px;
                                color: #666;
                            }
                            .info-box strong {
                                color: ' . COLOR_PRIMARY . ';
                            }
                            .contact-info {
                                background-color: #f9f9f9;
                                padding: 20px;
                                border-radius: 8px;
                                margin: 25px 0;
                            }
                            .contact-info h3 {
                                color: ' . COLOR_PRIMARY . ';
                                font-size: 18px;
                                margin-bottom: 15px;
                                font-weight: 600;
                            }
                            .contact-info p {
                                margin: 5px 0;
                                font-size: 14px;
                                color: #555;
                            }
                            .contact-info a {
                                color: ' . COLOR_SECONDARY . ';
                                text-decoration: none;
                                font-weight: 500;
                            }
                            .contact-info a:hover {
                                text-decoration: underline;
                            }
                            .footer {
                                margin-top: 40px;
                                padding-top: 20px;
                                border-top: 2px solid #e0e0e0;
                                text-align: center;
                                color: #666;
                                font-size: 12px;
                            }
                            .footer p {
                                margin: 5px 0;
                            }
                            .signature {
                                margin-top: 30px;
                                text-align: right;
                                font-style: italic;
                                color: #888;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="email-container">
                            <div class="email-header">
                                <div class="icon">✓</div>
                                <h1>Cảm ơn bạn đã liên hệ!</h1>
                            </div>
                            
                            <div class="content">
                                <div class="greeting">Xin chào ' . htmlspecialchars($name) . ',</div>
                                
                                <div class="message">
                                    Chúng tôi đã nhận được tin nhắn liên hệ của bạn về chủ đề <strong>"' . htmlspecialchars($subjectLabel) . '"</strong>. 
                                    Chúng tôi rất trân trọng sự quan tâm của bạn và sẽ phản hồi trong thời gian sớm nhất.
                                </div>
                                
                                <div class="info-box">
                                    <p><strong>Thông tin tin nhắn của bạn:</strong></p>
                                    <p>• Chủ đề: ' . htmlspecialchars($subjectLabel) . '</p>
                                    <p>• Thời gian gửi: ' . date(DATETIME_FORMAT) . '</p>
                                    <p>• Mã tham chiếu: #' . $messageId . '</p>
                                </div>
                                
                                <div class="message">
                                    Đội ngũ của chúng tôi sẽ xem xét và phản hồi bạn qua email <strong>' . htmlspecialchars($email) . '</strong> 
                                    hoặc số điện thoại <strong>' . htmlspecialchars($phone) . '</strong> trong vòng 24-48 giờ làm việc.
                                </div>
                                
                                <div class="contact-info">
                                    <h3>Thông tin liên hệ của chúng tôi:</h3>
                                    <p><strong>Email:</strong> <a href="mailto:' . CONTACT_EMAIL . '">' . CONTACT_EMAIL . '</a></p>
                                    <p><strong>Hotline:</strong> <a href="tel:' . str_replace(' ', '', CONTACT_HOTLINE) . '">' . CONTACT_HOTLINE . '</a></p>
                                    <p><strong>Địa chỉ:</strong> ' . CONTACT_ADDRESS . '</p>
                                    <p><strong>Thời gian làm việc:</strong> ' . CONTACT_WORKING_HOURS . '</p>
                                </div>
                                
                                <div class="message">
                                    Nếu bạn có bất kỳ câu hỏi khẩn cấp nào, vui lòng liên hệ trực tiếp với chúng tôi qua hotline. 
                                    Chúng tôi luôn sẵn sàng hỗ trợ bạn!
                                </div>
                                
                                <div class="signature">
                                    <p>Trân trọng,<br>
                                    <strong>Đội ngũ ' . SITE_NAME . '</strong></p>
                                </div>
                            </div>
                            
                            <div class="footer">
                                <p><strong>' . SITE_NAME . '</strong> - ' . SITE_TAGLINE . '</p>
                                <p>Email này được gửi tự động, vui lòng không reply trực tiếp.</p>
                                <p>Nếu bạn không phải là người gửi tin nhắn này, vui lòng bỏ qua email này.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ';
                    
                    // Plain text version
                    $thankYouMail->AltBody = "Cảm ơn bạn đã liên hệ với " . SITE_NAME . "!\n\n" .
                        "Xin chào " . $name . ",\n\n" .
                        "Chúng tôi đã nhận được tin nhắn liên hệ của bạn về chủ đề \"" . $subjectLabel . "\". " .
                        "Chúng tôi rất trân trọng sự quan tâm của bạn và sẽ phản hồi trong thời gian sớm nhất.\n\n" .
                        "Thông tin tin nhắn của bạn:\n" .
                        "• Chủ đề: " . $subjectLabel . "\n" .
                        "• Thời gian gửi: " . date(DATETIME_FORMAT) . "\n" .
                        "• Mã tham chiếu: #" . $messageId . "\n\n" .
                        "Đội ngũ của chúng tôi sẽ xem xét và phản hồi bạn qua email " . $email . " " .
                        "hoặc số điện thoại " . $phone . " trong vòng 24-48 giờ làm việc.\n\n" .
                        "Thông tin liên hệ của chúng tôi:\n" .
                        "Email: " . CONTACT_EMAIL . "\n" .
                        "Hotline: " . CONTACT_HOTLINE . "\n" .
                        "Địa chỉ: " . CONTACT_ADDRESS . "\n" .
                        "Thời gian làm việc: " . CONTACT_WORKING_HOURS . "\n\n" .
                        "Trân trọng,\n" .
                        "Đội ngũ " . SITE_NAME . "\n\n" .
                        "---\n" .
                        SITE_NAME . " - " . SITE_TAGLINE . "\n" .
                        "Email này được gửi tự động, vui lòng không reply trực tiếp.";
                    
                    // Gửi email cảm ơn
                    if (!empty(SMTP_PASSWORD)) {
                        $thankYouMail->send();
                    }
                    
                } catch (Exception $e) {
                    // Nếu gửi email cảm ơn thất bại, chỉ log lỗi nhưng không ảnh hưởng đến kết quả
                    $errorMsg = isset($thankYouMail) ? $thankYouMail->ErrorInfo : $e->getMessage();
                    error_log("Thank you email Error: " . $errorMsg);
                }
                
                $formMessage = 'Cảm ơn bạn đã liên hệ! Chúng tôi đã nhận được tin nhắn của bạn và sẽ phản hồi trong thời gian sớm nhất.';
                $formMessageType = 'success';
                
            } catch (Exception $e) {
                // Nếu gửi email thất bại nhưng đã lưu vào DB, vẫn báo thành công
                // Log lỗi email (có thể ghi vào log file)
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
                
                $formMessage = 'Cảm ơn bạn đã liên hệ! Tin nhắn của bạn đã được lưu lại. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.';
                $formMessageType = 'success';
            }
            
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $formMessage = 'Có lỗi xảy ra khi xử lý tin nhắn. Vui lòng thử lại sau.';
            $formMessageType = 'error';
        } catch (Exception $e) {
            error_log("General Error: " . $e->getMessage());
            $formMessage = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            $formMessageType = 'error';
        }
    } else {
        $formMessage = implode('<br>', $errors);
        $formMessageType = 'error';
    }
}

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
        padding-top: 20px;
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
<?php
$page_title = "Liên Hệ";
include __DIR__ . '/../includes/components/page-header.php';
?>

<div class="contact-container">
    <p style="text-align: center; color: var(--dark); font-size: 16px; margin-bottom: 30px; max-width: <?php echo CONTAINER_MAX_WIDTH_XSMALL; ?>; margin-left: auto; margin-right: auto;">
        Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn. Hãy liên hệ với chúng tôi qua các kênh sau:
    </p>

    <!-- Contact Form Section -->
    <div class="contact-form-section">
        <h2>Gửi tin nhắn cho chúng tôi</h2>
        <form class="contact-form" id="contactForm" method="POST" action="">
            <?php if (!empty($formMessage)): ?>
                <div id="formMessage" class="form-message <?php echo $formMessageType; ?>">
                    <?php echo $formMessage; ?>
                </div>
            <?php else: ?>
                <div id="formMessage" class="form-message"></div>
            <?php endif; ?>
            
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
    // Form validation before submission
    document.getElementById('contactForm').addEventListener('submit', function(e) {
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
            e.preventDefault();
            showMessage('Vui lòng điền đầy đủ thông tin bắt buộc!', 'error');
            return false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            showMessage('Vui lòng nhập địa chỉ email hợp lệ!', 'error');
            return false;
        }
        
        // Phone validation (Vietnamese phone number)
        const phoneRegex = /^(0|\+84)[0-9]{9,10}$/;
        const cleanPhone = phone.replace(/\s/g, '');
        if (!phoneRegex.test(cleanPhone)) {
            e.preventDefault();
            showMessage('Vui lòng nhập số điện thoại hợp lệ!', 'error');
            return false;
        }
        
        // If validation passes, allow form to submit normally
        // Show loading message
        showMessage('Đang gửi tin nhắn...', 'success');
    });
    
    function showMessage(text, type) {
        const formMessage = document.getElementById('formMessage');
        formMessage.textContent = text;
        formMessage.className = 'form-message ' + type;
        formMessage.style.display = 'block';
        
        // Scroll to message
        formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto hide after 5 seconds for success messages (only if not from server)
        if (type === 'success' && !formMessage.classList.contains('success')) {
            setTimeout(function() {
                formMessage.style.display = 'none';
            }, 5000);
        }
    }
    
    // Auto-scroll to message if there's a server message on page load
    <?php if (!empty($formMessage)): ?>
    window.addEventListener('DOMContentLoaded', function() {
        const formMessage = document.getElementById('formMessage');
        if (formMessage && formMessage.textContent.trim()) {
            formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // If success, reset form after showing message
            <?php if ($formMessageType === 'success'): ?>
            setTimeout(function() {
                document.getElementById('contactForm').reset();
            }, 100);
            <?php endif; ?>
        }
    });
    <?php endif; ?>
</script>

<?php
$cta_heading = 'Sẵn sàng bắt đầu hành trình phủ xanh cùng chúng tôi?';
$cta_description = 'Hãy khám phá các sản phẩm và dịch vụ của chúng tôi ngay hôm nay!';
$cta_button_text = 'Xem sản phẩm';
$cta_button_link = BASE_URL . '/public/products.php';
include '../includes/components/cta-section.php';
?>
<?php include '../includes/footer.php'; ?>
