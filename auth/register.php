<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } elseif (strlen($username) < 3) {
        $error = 'Tên đăng nhập phải có ít nhất 3 ký tự';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        $result = registerUser($username, $email, $password, $fullName, $phone);
        if ($result['success']) {
            // Auto login after registration
            $loginResult = loginUser($username, $password);
            if ($loginResult['success']) {
                // Redirect immediately on success
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            } else {
                // If auto login fails, redirect to login page
                header('Location: ' . BASE_URL . '/auth/login.php?registered=1');
                exit;
            }
        } else {
            $error = $result['message'];
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

    .form-group label .required {
        color: var(--secondary);
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

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .btn-primary {
        width: 100%;
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
        margin-top: 10px;
    }

    .btn-primary:hover {
        background-color: #2d4a2d;
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

    .password-hint {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-size: 12px;
        color: #666;
        margin-top: 5px;
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

        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<main>
    <div class="auth-container">
        <div class="auth-header">
        <h1>Đăng Ký</h1>
        <p>Tạo tài khoản mới để bắt đầu</p>
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
                <p style="margin-top: 10px;">Đang chuyển hướng...</p>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Tên đăng nhập <span class="required">*</span></label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Nhập tên đăng nhập"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           required
                           minlength="3">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="Nhập địa chỉ email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mật khẩu <span class="required">*</span></label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Nhập mật khẩu"
                               required
                               minlength="6">
                    </div>
                    <div class="password-hint">Tối thiểu 6 ký tự</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu <span class="required">*</span></label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Nhập lại mật khẩu"
                               required
                               minlength="6">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Họ và tên</label>
                    <div class="input-icon">
                        <i class="fas fa-id-card"></i>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               placeholder="Nhập họ và tên"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <div class="input-icon">
                        <i class="fas fa-phone"></i>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               placeholder="Nhập số điện thoại"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-user-plus"></i> Đăng Ký
            </button>
        </form>

        <div class="auth-footer">
            <p>Đã có tài khoản? <a href="<?php echo BASE_URL; ?>/auth/login.php">Đăng nhập ngay</a></p>
        </div>
    </div>
</main>

<script>
    // Validate password match
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (password !== confirmPassword && confirmPassword.length > 0) {
            this.setCustomValidity('Mật khẩu xác nhận không khớp');
        } else {
            this.setCustomValidity('');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>

