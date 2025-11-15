<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$success = '';
$showForm = false;
$tokenInput = trim($_POST['token'] ?? ($_GET['token'] ?? ''));
$tokenRecord = null;

if ($tokenInput === '') {
    $error = 'Liên kết đặt lại mật khẩu không hợp lệ. Vui lòng yêu cầu lại.';
} else {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('
            SELECT prt.token_id, prt.user_id, prt.expires_at, prt.used_at, u.email, u.full_name
            FROM password_reset_tokens prt
            JOIN users u ON u.user_id = prt.user_id
            WHERE prt.token_hash = :token_hash
            LIMIT 1
        ');
        $stmt->execute([':token_hash' => hash('sha256', $tokenInput)]);
        $tokenRecord = $stmt->fetch();

        if (!$tokenRecord) {
            $error = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.';
        } elseif (!empty($tokenRecord['used_at'])) {
            $error = 'Liên kết đặt lại mật khẩu này đã được sử dụng. Vui lòng yêu cầu liên kết mới.';
        } elseif (new DateTimeImmutable($tokenRecord['expires_at']) < new DateTimeImmutable()) {
            $error = 'Liên kết đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu liên kết mới.';
        } else {
            $showForm = true;
        }
    } catch (Throwable $exception) {
        error_log('Reset password token validation error: ' . $exception->getMessage());
        $error = 'Không thể xử lý yêu cầu đặt lại mật khẩu. Vui lòng thử lại sau.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm && $tokenRecord) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirm'] ?? '';

    if (strlen($newPassword) < 8) {
        $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp.';
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $updateUserStmt = $pdo->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id');
            $updateUserStmt->execute([
                ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':user_id' => $tokenRecord['user_id'],
            ]);

            $markTokenStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE token_id = :token_id');
            $markTokenStmt->execute([':token_id' => $tokenRecord['token_id']]);

            $invalidateOthersStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL AND token_id <> :token_id');
            $invalidateOthersStmt->execute([
                ':user_id' => $tokenRecord['user_id'],
                ':token_id' => $tokenRecord['token_id'],
            ]);

            $pdo->commit();

            $success = 'Mật khẩu của bạn đã được cập nhật thành công. Bạn có thể đăng nhập với mật khẩu mới ngay bây giờ.';
            $showForm = false;
            $tokenInput = '';
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Reset password update error: ' . $exception->getMessage());
            $error = 'Không thể cập nhật mật khẩu. Vui lòng thử lại sau.';
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
            <h1>Đặt lại mật khẩu</h1>
            <p>Tạo mật khẩu mới để tiếp tục bảo vệ tài khoản của bạn.</p>
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

            <?php if ($showForm): ?>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenInput); ?>">

                    <div class="form-group">
                        <label for="password">Mật khẩu mới</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   placeholder="Nhập mật khẩu mới"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Xác nhận mật khẩu</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password"
                                   id="password_confirm"
                                   name="password_confirm"
                                   placeholder="Nhập lại mật khẩu"
                                   required>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Cập nhật mật khẩu
                        </button>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Quay lại đăng nhập
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="auth-footer" style="padding-top: 0; border-top: none;">
                    <p>Nếu bạn cần liên kết mới, hãy thực hiện lại quy trình <a href="<?php echo BASE_URL; ?>/auth/forgot-password.php">quên mật khẩu</a>.</p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="auth-footer">
                    <p><a href="<?php echo BASE_URL; ?>/auth/login.php">Đăng nhập ngay</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
