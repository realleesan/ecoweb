<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../library/phpmailer/src/Exception.php';
require_once __DIR__ . '/../library/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../library/phpmailer/src/SMTP.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$success = '';
$emailInput = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($emailInput)) {
        $error = 'Vui lòng nhập email đã đăng ký tài khoản.';
    } elseif (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ. Vui lòng kiểm tra lại.';
    }

    if (empty($error)) {
        $genericSuccess = 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu tới hộp thư của bạn.';

        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('SELECT user_id, full_name, email FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $emailInput]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

                $pdo->beginTransaction();

                $invalidateStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
                $invalidateStmt->execute([':user_id' => $user['user_id']]);

                $insertStmt = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, email, token_hash, expires_at) VALUES (:user_id, :email, :token_hash, :expires_at)');
                $insertStmt->execute([
                    ':user_id' => $user['user_id'],
                    ':email' => $user['email'],
                    ':token_hash' => $tokenHash,
                    ':expires_at' => $expiresAt,
                ]);

                $pdo->commit();

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $resetUrl = sprintf('%s://%s%s/auth/reset-password.php?token=%s', $scheme, $host, BASE_URL, urlencode($token));

                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USERNAME;
                    $mail->Password = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_SECURE;
                    $mail->Port = SMTP_PORT;
                    $mail->CharSet = SMTP_CHARSET;

                    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $mail->addAddress($user['email'], $user['full_name'] ?? $user['email']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Hướng dẫn đặt lại mật khẩu - ' . SITE_NAME;

                    $displayName = !empty($user['full_name']) ? $user['full_name'] : $user['email'];
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html lang="vi">
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: ' . FONT_FAMILY . ', sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
                            .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
                            .email-header { background: linear-gradient(135deg, ' . COLOR_PRIMARY . ', ' . COLOR_SECONDARY . '); color: #ffffff; padding: 30px; text-align: center; }
                            .email-header h1 { margin: 0; font-size: 26px; font-weight: 700; }
                            .email-body { padding: 30px; color: #333333; }
                            .greeting { font-size: 18px; font-weight: 600; color: ' . COLOR_PRIMARY . '; margin-bottom: 15px; }
                            .message { font-size: 15px; line-height: 1.7; margin-bottom: 25px; }
                            .button-wrapper { text-align: center; margin: 30px 0; }
                            .button { display: inline-block; padding: 14px 28px; background-color: ' . COLOR_PRIMARY . '; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; }
                            .button:hover { background-color: ' . COLOR_SECONDARY . '; }
                            .info-box { background-color: ' . COLOR_LIGHT . '; border-left: 4px solid ' . COLOR_SECONDARY . '; padding: 20px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
                            .info-box p { margin: 8px 0; }
                            .email-footer { background-color: #fafafa; padding: 20px 30px; text-align: center; font-size: 12px; color: #777777; }
                        </style>
                    </head>
                    <body>
                        <div class="email-container">
                            <div class="email-header">
                                <h1>Đặt lại mật khẩu</h1>
                            </div>
                            <div class="email-body">
                                <p class="greeting">Xin chào ' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . ',</p>
                                <div class="message">
                                    Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản trên ' . SITE_NAME . '. Vui lòng nhấp vào nút bên dưới để thiết lập mật khẩu mới. Liên kết sẽ hết hạn sau 60 phút.
                                </div>
                                <div class="button-wrapper">
                                    <a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '" class="button" target="_blank" rel="noopener">Đặt lại mật khẩu</a>
                                </div>
                                <div class="info-box">
                                    <p><strong>Email đăng nhập:</strong> ' . htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') . '</p>
                                    <p><strong>Thời gian yêu cầu:</strong> ' . date(DATETIME_FORMAT) . '</p>
                                    <p>Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email và mật khẩu hiện tại của bạn vẫn an toàn.</p>
                                </div>
                                <p class="message" style="font-size: 14px; color: #666666;">Để bảo mật, vui lòng không chia sẻ liên kết này cho bất kỳ ai.</p>
                            </div>
                            <div class="email-footer">
                                <p>' . SITE_NAME . ' - ' . SITE_TAGLINE . '</p>
                                <p>Email này được gửi tự động từ hệ thống. Vui lòng không trả lời trực tiếp.</p>
                            </div>
                        </div>
                    </body>
                    </html>';

                    $mail->AltBody = "Xin chào " . $displayName . "!\n\n" .
                        "Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản trên " . SITE_NAME . ".\n" .
                        "Vui lòng truy cập liên kết sau để đặt lại mật khẩu (hiệu lực trong 60 phút):\n" . $resetUrl . "\n\n" .
                        "Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.";

                    if (!empty(SMTP_PASSWORD)) {
                        $mail->send();
                    }
                } catch (PHPMailerException $mailException) {
                    error_log('PHPMailer password reset error: ' . $mailException->getMessage());
                }
            }

            $success = $genericSuccess;
            $emailInput = '';
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Forgot password error: ' . $exception->getMessage());
            $error = 'Có lỗi xảy ra khi xử lý yêu cầu. Vui lòng thử lại sau.';
        }
    }
}

include '../includes/header.php';
?>

<style>
    body {
        background-color: var(--light);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    main {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .auth-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH_XSMALL; ?>;
        width: 100%;
        background-color: var(--white);
        border-radius: 15px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .auth-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        padding: 40px;
        text-align: center;
        color: var(--white);
    }

    .auth-header h1 {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 32px;
        margin-bottom: 10px;
    }

    .auth-header p {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 16px;
        opacity: 0.9;
    }

    .auth-body {
        padding: 40px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-size: 14px;
        color: var(--dark);
        transition: border-color 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--primary);
    }

    .form-group input::placeholder {
        color: #999;
    }

    .input-icon {
        position: relative;
    }

    .input-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--dark);
        opacity: 0.5;
    }

    .input-icon input {
        padding-left: 45px;
    }

    .btn-group {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .btn-primary {
        flex: 1;
        padding: 14px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 8px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #2d4a2d;
    }

    .btn-link {
        padding: 14px 20px;
        background-color: transparent;
        color: var(--primary);
        border: 2px solid var(--primary);
        border-radius: 8px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .btn-link:hover {
        background-color: var(--primary);
        color: var(--white);
    }

    .auth-footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e0e0e0;
    }

    .auth-footer p {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .auth-footer a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .auth-footer a:hover {
        color: var(--secondary);
        text-decoration: underline;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-size: 14px;
    }

    .alert-error {
        background-color: #fee;
        color: #c33;
        border: 1px solid #fcc;
    }

    .alert-success {
        background-color: #efe;
        color: #3c3;
        border: 1px solid #cfc;
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        main {
            padding: 20px 10px;
        }

        .auth-container {
            max-width: 100%;
        }

        .auth-header {
            padding: 30px 20px;
        }

        .auth-body {
            padding: 30px 20px;
        }

        .btn-group {
            flex-direction: column;
        }

        .btn-primary,
        .btn-link {
            width: 100%;
        }
    }
</style>

<main>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Quên mật khẩu</h1>
            <p>Nhập email của bạn để nhận hướng dẫn đặt lại mật khẩu.</p>
        </div>

        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email đăng ký</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email"
                               id="email"
                               name="email"
                               placeholder="Nhập email đã sử dụng để đăng ký"
                               value="<?php echo htmlspecialchars($emailInput); ?>"
                               required>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Gửi hướng dẫn
                    </button>
                    <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn-link">
                        <i class="fas fa-arrow-left"></i> Quay lại đăng nhập
                    </a>
                </div>
            </form>

            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="<?php echo BASE_URL; ?>/auth/register.php">Đăng ký ngay</a></p>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

