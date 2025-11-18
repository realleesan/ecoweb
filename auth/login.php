<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';


// Redirect if already logged in
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
} elseif (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


$error = '';
$success = '';


// Check if redirected from registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
}


// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
   
    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } else {
        $result = loginUser($usernameOrEmail, $password);
        if ($result['success']) {
            // Redirect based on role
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/index.php');
            } else {
                header('Location: ' . BASE_URL . '/index.php');
            }
            exit;
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


    .forgot-password {
        text-align: right;
        margin-bottom: 25px;
    }


    .forgot-password a {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--primary);
        text-decoration: none;
        transition: color 0.3s ease;
    }


    .forgot-password a:hover {
        color: var(--secondary);
        text-decoration: underline;
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
            <h1>Đăng Nhập</h1>
            <p>Chào mừng bạn trở lại!</p>
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
                    <label for="username_or_email">Tên đăng nhập hoặc Email</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text"
                               id="username_or_email"
                               name="username_or_email"
                               placeholder="Nhập tên đăng nhập hoặc email"
                               value="<?php echo isset($_POST['username_or_email']) ? htmlspecialchars($_POST['username_or_email']) : ''; ?>"
                               required>
                    </div>
                </div>


                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                               id="password"
                               name="password"
                               placeholder="Nhập mật khẩu"
                               required>
                    </div>
                </div>


                <div class="btn-group">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Đăng Nhập
                    </button>
                    <a href="<?php echo BASE_URL; ?>/auth/forgot-password.php" class="btn-link">Quên mật khẩu</a>
                </div>
            </form>


            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="<?php echo BASE_URL; ?>/auth/register.php">Đăng ký ngay</a></p>
            </div>
        </div>
    </div>
</main>


<?php include '../includes/footer.php'; ?>





