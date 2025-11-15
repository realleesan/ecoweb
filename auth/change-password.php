<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';

// Require login
requireLogin();

// Get current user
$user = getCurrentUser();
if (!$user) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Mật khẩu mới và xác nhận mật khẩu không khớp';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    } else {
        // Change password
        $result = changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
        if ($result['success']) {
            $success = $result['message'];
            // Clear form on success
            $_POST = [];
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
    }

    .account-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 40px auto;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
    }

    .account-content {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
        align-items: start;
    }

    .account-main {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 40px;
    }

    .account-main-header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--light);
    }

    .account-main-header h1 {
        font-size: 28px;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .account-main-header p {
        color: var(--dark);
        font-size: 14px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--dark);
        font-size: 15px;
        margin-bottom: 8px;
    }

    .form-group label i {
        margin-right: 8px;
        color: var(--primary);
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        color: var(--dark);
        transition: border-color 0.3s ease;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
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
        pointer-events: none;
    }

    .input-icon input {
        padding-left: 45px;
        padding-right: 45px;
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--dark);
        opacity: 0.5;
        transition: opacity 0.3s ease;
        z-index: 10;
        pointer-events: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
    }

    .password-toggle:hover {
        opacity: 1;
    }

    .password-toggle i {
        pointer-events: none;
    }

    .password-strength {
        margin-top: 8px;
        font-size: 12px;
        color: #999;
    }

    .password-strength.weak {
        color: #dc3545;
    }

    .password-strength.medium {
        color: #ffc107;
    }

    .password-strength.strong {
        color: #28a745;
    }

    .btn-group {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-primary {
        padding: 12px 30px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
    }

    .btn-primary:hover {
        background-color: #2d4a2d;
    }

    .btn-secondary {
        padding: 12px 30px;
        background-color: #6c757d;
        color: var(--white);
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        text-decoration: none;
        display: inline-block;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-size: 14px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
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

    .alert i {
        margin-right: 8px;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .account-content {
            grid-template-columns: 1fr;
        }

        .account-main {
            padding: 25px 20px;
        }

        .btn-group {
            flex-direction: column;
        }

        .btn-primary,
        .btn-secondary {
            width: 100%;
        }
    }
</style>

<div class="account-container">
    <div class="account-content">
        <?php include 'sidebar-account.php'; ?>
        
        <div class="account-main">
            <div class="account-main-header">
                <h1>Đổi mật khẩu</h1>
                <p>Bảo vệ tài khoản của bạn bằng mật khẩu mạnh</p>
            </div>

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

            <form method="POST" action="" id="changePasswordForm">
                <div class="form-group">
                    <label for="current_password">
                        <i class="fas fa-lock"></i> Mật khẩu hiện tại
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               placeholder="Nhập mật khẩu hiện tại"
                               required>
                        <span class="password-toggle" data-target="current_password">
                            <i class="fas fa-eye" id="toggle_current_password"></i>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-lock"></i> Mật khẩu mới
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                               required
                               minlength="6"
                               oninput="checkPasswordStrength(this.value)">
                        <span class="password-toggle" data-target="new_password">
                            <i class="fas fa-eye" id="toggle_new_password"></i>
                        </span>
                    </div>
                    <div class="password-strength" id="password_strength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Xác nhận mật khẩu mới
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Nhập lại mật khẩu mới"
                               required
                               minlength="6"
                               oninput="checkPasswordMatch()">
                        <span class="password-toggle" data-target="confirm_password">
                            <i class="fas fa-eye" id="toggle_confirm_password"></i>
                        </span>
                    </div>
                    <div class="password-strength" id="password_match"></div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Đổi mật khẩu
                    </button>
                    <a href="<?php echo BASE_URL; ?>/auth/account.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const toggleId = 'toggle_' + inputId;
        const toggle = document.getElementById(toggleId);
        
        if (!input || !toggle) {
            console.error('Không tìm thấy input hoặc toggle icon');
            return;
        }
        
        if (input.type === 'password') {
            input.type = 'text';
            toggle.classList.remove('fa-eye');
            toggle.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            toggle.classList.remove('fa-eye-slash');
            toggle.classList.add('fa-eye');
        }
    }

    // Add event listeners to all password toggle buttons
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.password-toggle');
        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const targetInputId = this.getAttribute('data-target');
                if (targetInputId) {
                    togglePassword(targetInputId);
                }
            });
        });
    });

    function checkPasswordStrength(password) {
        const strengthDiv = document.getElementById('password_strength');
        
        if (password.length === 0) {
            strengthDiv.textContent = '';
            strengthDiv.className = 'password-strength';
            return;
        }
        
        let strength = 0;
        let text = '';
        let className = '';
        
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z\d]/.test(password)) strength++;
        
        if (strength <= 2) {
            text = 'Mật khẩu yếu';
            className = 'password-strength weak';
        } else if (strength <= 3) {
            text = 'Mật khẩu trung bình';
            className = 'password-strength medium';
        } else {
            text = 'Mật khẩu mạnh';
            className = 'password-strength strong';
        }
        
        strengthDiv.textContent = text;
        strengthDiv.className = className;
    }

    function checkPasswordMatch() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchDiv = document.getElementById('password_match');
        
        if (confirmPassword.length === 0) {
            matchDiv.textContent = '';
            matchDiv.className = 'password-strength';
            return;
        }
        
        if (newPassword === confirmPassword) {
            matchDiv.textContent = 'Mật khẩu khớp';
            matchDiv.className = 'password-strength strong';
        } else {
            matchDiv.textContent = 'Mật khẩu không khớp';
            matchDiv.className = 'password-strength weak';
        }
    }

    // Form validation
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
            return false;
        }
        
        if (newPassword.length < 6) {
            e.preventDefault();
            alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
            return false;
        }
    });
</script>

<?php include '../includes/footer.php'; ?>

