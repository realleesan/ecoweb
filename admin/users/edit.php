<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$pdo = getPDO();

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/admin/users/index.php');
    exit;
}

$user = null;
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
} catch (Throwable $e) {
    $user = null;
}

if (!$user) {
    header('Location: ' . BASE_URL . '/admin/users/index.php');
    exit;
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'user';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($fullName)) {
        $errors[] = 'Họ và tên không được để trống.';
    }
    if (empty($username)) {
        $errors[] = 'Tên đăng nhập không được để trống.';
    }
    if (empty($email)) {
        $errors[] = 'Email không được để trống.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    // Kiểm tra username trùng
    if (empty($errors)) {
        try {
            $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :username AND user_id != :id');
            $checkStmt->execute([':username' => $username, ':id' => $userId]);
            if ($checkStmt->fetch()) {
                $errors[] = 'Tên đăng nhập đã tồn tại.';
            }
        } catch (Throwable $e) {
            $errors[] = 'Không thể kiểm tra tên đăng nhập.';
        }
    }

    // Kiểm tra email trùng
    if (empty($errors)) {
        try {
            $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email AND user_id != :id');
            $checkStmt->execute([':email' => $email, ':id' => $userId]);
            if ($checkStmt->fetch()) {
                $errors[] = 'Email đã tồn tại.';
            }
        } catch (Throwable $e) {
            $errors[] = 'Không thể kiểm tra email.';
        }
    }

    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare('
                UPDATE users 
                SET full_name = :full_name, 
                    username = :username, 
                    email = :email, 
                    phone = :phone, 
                    address = :address, 
                    role = :role, 
                    is_active = :is_active
                WHERE user_id = :id
            ');
            $updateStmt->execute([
                ':full_name' => $fullName,
                ':username' => $username,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':role' => $role,
                ':is_active' => $isActive,
                ':id' => $userId
            ]);
            $successMessage = 'Cập nhật thông tin người dùng thành công.';
            
            // Reload user data
            $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
        } catch (Throwable $e) {
            $errors[] = 'Không thể cập nhật thông tin người dùng.';
        }
    }
}

$fullName = (string) ($user['full_name'] ?? '');
$username = (string) ($user['username'] ?? '');
$email = (string) ($user['email'] ?? '');
$phone = (string) ($user['phone'] ?? '');
$address = (string) ($user['address'] ?? '');
$role = (string) ($user['role'] ?? 'user');
$isActive = (int) ($user['is_active'] ?? 0) === 1;

include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 30px; }
    .user-edit__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; }
    .user-edit__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 14px; flex-wrap: wrap; margin-bottom: 6px; }
    .breadcrumb a { color: var(--secondary); text-decoration: none; font-weight: 600; }
    .breadcrumb span { color: rgba(0,0,0,0.55); }
    .user-edit-content { display: grid; grid-template-columns: 1fr 320px; gap: 24px; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .user-edit-content { grid-template-columns: 1fr; } }
    .user-edit-card { background: var(--white); border-radius: 14px; border: 1px solid #f0ebe3; padding: 28px; }
    .user-edit-card__title { font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 24px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; font-size: 14px; color: var(--dark); margin-bottom: 8px; }
    .form-group label .required { color: #d64226; margin-left: 2px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; border-radius: 10px; border: 1px solid #e5e5e5; padding: 11px 14px; font-size: 14px; transition: border 0.2s ease, box-shadow 0.2s ease; background-color: var(--white); font-family: inherit; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.15); outline: none; }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: <?php echo BREAKPOINT_SM; ?>) { .form-row { grid-template-columns: 1fr; } }
    .form-actions { display: flex; flex-direction: column; gap: 12px; margin-top: 24px; }
    .btn-submit { width: 100%; padding: 12px 20px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; background: var(--secondary); color: var(--white); display: flex; align-items: center; justify-content: center; gap: 8px; }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(210, 100, 38, 0.28); }
    .btn-secondary { width: 100%; padding: 12px 20px; border-radius: 10px; border: 1px solid #e5e5e5; font-weight: 600; font-size: 14px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; background: var(--white); color: var(--dark); display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
    .btn-secondary:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(0,0,0,0.12); }
    .sidebar-card { background: var(--white); border-radius: 14px; border: 1px solid #f0ebe3; padding: 24px; margin-bottom: 20px; }
    .sidebar-card__title { font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 18px; }
    .toggle-switch { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; }
    .toggle-switch label { font-weight: 600; font-size: 14px; color: var(--dark); margin: 0; }
    .toggle-switch input[type="checkbox"] { width: 50px; height: 28px; appearance: none; background: rgba(0,0,0,0.15); border-radius: 999px; position: relative; cursor: pointer; transition: background 0.3s ease; }
    .toggle-switch input[type="checkbox"]:checked { background: rgba(60, 96, 60, 0.8); }
    .toggle-switch input[type="checkbox"]::before { content: ''; position: absolute; width: 22px; height: 22px; border-radius: 50%; background: var(--white); top: 3px; left: 3px; transition: left 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .toggle-switch input[type="checkbox"]:checked::before { left: 25px; }
    .role-select-group { display: flex; flex-direction: column; gap: 10px; }
    .role-option { padding: 12px 16px; border-radius: 8px; border: 2px solid #e5e5e5; cursor: pointer; transition: all 0.2s ease; background: var(--white); }
    .role-option:hover { border-color: var(--secondary); }
    .role-option.selected { border-color: var(--secondary); background: rgba(210, 100, 38, 0.08); }
    .role-option input[type="radio"] { display: none; }
    .role-option label { display: block; font-weight: 600; font-size: 14px; color: var(--dark); cursor: pointer; margin: 0; }
    .notice { display: flex; align-items: flex-start; gap: 12px; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
    .notice--success { background: rgba(63,142,63,0.12); color: #2a6a2a; border: 1px solid rgba(63,142,63,0.35); }
    .notice--error { background: rgba(210,64,38,0.12); color: #a52f1c; border: 1px solid rgba(210,64,38,0.35); }
    .notice i { margin-top: 2px; }
    .notice ul { margin: 8px 0 0; padding-left: 20px; }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content user-edit">
        <div class="user-edit__header">
            <div class="user-edit__title-group">
                <h1>Sửa Thông Tin Người Dùng</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <a href="<?php echo BASE_URL; ?>/admin/users/index.php">Quản Lý Người Dùng</a>
                    <span>/</span>
                    <span>Sửa</span>
                </nav>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="notice notice--success" role="status">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="notice notice--error" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Không thể cập nhật:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="user-edit-content">
                <div class="user-edit-card">
                    <h2 class="user-edit-card__title">Thông Tin Cơ Bản</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Họ<span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="username">Tên<span class="required">*</span></label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email<span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Số Điện Thoại</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="birth_date">Ngày Sinh</label>
                            <input type="date" id="birth_date" name="birth_date">
                        </div>

                        <div class="form-group">
                            <label for="gender">Giới Tính</label>
                            <select id="gender" name="gender">
                                <option value="">Chọn giới tính...</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Avatar URL</label>
                        <input type="text" id="avatar_url" name="avatar_url" placeholder="https://example.com/avatar.jpg">
                    </div>
                </div>

                <div>
                    <div class="sidebar-card">
                        <h3 class="sidebar-card__title">Trạng Thái Tài Khoản</h3>
                        
                        <div class="toggle-switch">
                            <label for="is_active">Kích hoạt tài khoản</label>
                            <input type="checkbox" id="is_active" name="is_active" <?php echo $isActive ? 'checked' : ''; ?>>
                        </div>
                    </div>

                    <div class="sidebar-card">
                        <h3 class="sidebar-card__title">Vai Trò</h3>
                        
                        <div class="role-select-group">
                            <div class="role-option <?php echo $role === 'admin' ? 'selected' : ''; ?>" onclick="selectRole(this, 'admin')">
                                <input type="radio" id="role_admin" name="role" value="admin" <?php echo $role === 'admin' ? 'checked' : ''; ?>>
                                <label for="role_admin">Admin</label>
                            </div>
                            <div class="role-option <?php echo $role === 'user' ? 'selected' : ''; ?>" onclick="selectRole(this, 'user')">
                                <input type="radio" id="role_user" name="role" value="user" <?php echo $role === 'user' ? 'checked' : ''; ?>>
                                <label for="role_user">User</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i>
                            Cập Nhật
                        </button>
                        <a href="<?php echo BASE_URL; ?>/admin/users/view.php?id=<?php echo $userId; ?>" class="btn-secondary">
                            <i class="fas fa-eye"></i>
                            Xem Chi Tiết
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/users/index.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Quay Lại
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    function selectRole(element, roleValue) {
        document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        document.getElementById('role_' + roleValue).checked = true;
    }
</script>
