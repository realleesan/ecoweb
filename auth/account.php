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

$addressTypeLabels = [
    'home' => 'Nhà riêng',
    'office' => 'Văn phòng',
    'school' => 'Trường học'
];

$defaultAddress = null;
$defaultAddressText = '';

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT recipient_name, phone, street_address, ward, city, address_type FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC LIMIT 1');
    $stmt->execute(['user_id' => $user['user_id']]);
    $defaultAddress = $stmt->fetch();

    if ($defaultAddress) {
        $addressParts = array_filter([
            $defaultAddress['street_address'] ?? '',
            $defaultAddress['ward'] ?? '',
            $defaultAddress['city'] ?? ''
        ]);
        $defaultAddressText = implode(', ', $addressParts);
    }
} catch (Exception $e) {
    $defaultAddress = null;
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

    .account-info {
        display: grid;
        gap: 25px;
    }

    .info-group {
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 20px;
        align-items: center;
        padding: 20px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-group:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: var(--dark);
        font-size: 15px;
    }

    .info-value {
        color: var(--dark);
        font-size: 15px;
    }

    .info-value .address-meta {
        margin-top: 6px;
        color: #555;
        font-size: 14px;
    }

    .address-tag {
        display: inline-block;
        margin-top: 8px;
        background-color: var(--light);
        color: var(--primary);
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .manage-address-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 14px;
        font-weight: 600;
        color: var(--primary);
        text-decoration: none;
        font-size: 14px;
    }

    .manage-address-link:hover {
        text-decoration: underline;
    }

    .info-value.empty {
        color: #999;
        font-style: italic;
    }

    .btn-edit {
        display: inline-block;
        padding: 12px 30px;
        background-color: var(--primary);
        color: var(--white);
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        transition: background-color 0.3s ease;
        margin-top: 20px;
    }

    .btn-edit {
        cursor: pointer;
    }

    .btn-edit:hover {
        background-color: #2d4a2d;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s ease;
    }

    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .modal-content {
        background-color: var(--white);
        border-radius: 10px;
        padding: 40px;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
        animation: slideDown 0.3s ease;
        position: relative;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--light);
    }

    .modal-header h2 {
        font-size: 24px;
        color: var(--primary);
        margin-bottom: 5px;
    }

    .modal-header p {
        color: var(--dark);
        font-size: 14px;
    }

    .close-modal {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
        color: #999;
        cursor: pointer;
        transition: color 0.3s ease;
        background: none;
        border: none;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close-modal:hover {
        color: var(--dark);
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

    .form-group .help-text {
        font-size: 12px;
        color: #999;
        margin-top: 5px;
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

    .modal-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-save {
        flex: 1;
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

    .btn-save:hover {
        background-color: #2d4a2d;
    }

    .btn-cancel {
        padding: 12px 30px;
        background-color: #6c757d;
        color: var(--white);
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
    }

    .btn-cancel:hover {
        background-color: #5a6268;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .account-content {
            grid-template-columns: 1fr;
        }

        .account-main {
            padding: 25px 20px;
        }

        .info-group {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .info-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
    }
</style>

<div class="account-container">
    <div class="account-content">
        <?php include 'sidebar-account.php'; ?>
        
        <div class="account-main">
            <div class="account-main-header">
                <h1>Thông tin tài khoản</h1>
                <p>Quản lý thông tin cá nhân của bạn</p>
            </div>

            <div class="account-info">
                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-user"></i> Tên đăng nhập
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-envelope"></i> Email
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-id-card"></i> Họ và tên
                    </div>
                    <div class="info-value <?php echo empty($user['full_name']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Chưa cập nhật'; ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-phone"></i> Số điện thoại
                    </div>
                    <div class="info-value <?php echo empty($user['phone']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Chưa cập nhật'; ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-map-marker-alt"></i> Địa chỉ
                    </div>
                    <?php if ($defaultAddress): ?>
                        <div class="info-value">
                            <div>
                                <strong><?php echo htmlspecialchars($defaultAddress['recipient_name']); ?></strong>
                            </div>
                            <?php if (!empty($defaultAddressText)): ?>
                                <div class="address-meta">
                                    <?php echo htmlspecialchars($defaultAddressText); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($defaultAddress['phone'])): ?>
                                <div class="address-meta">
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($defaultAddress['phone']); ?>
                                </div>
                            <?php endif; ?>
                            <span class="address-tag">
                                <?php echo $addressTypeLabels[$defaultAddress['address_type']] ?? 'Địa chỉ'; ?>
                            </span>
                            <a class="manage-address-link" href="<?php echo BASE_URL; ?>/auth/addresses.php">
                                Quản lý sổ địa chỉ <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="info-value empty">
                            Chưa cập nhật
                            <a class="manage-address-link" href="<?php echo BASE_URL; ?>/auth/addresses.php">
                                Thêm địa chỉ ngay <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-shield-alt"></i> Vai trò
                    </div>
                    <div class="info-value">
                        <?php 
                        $roleLabels = [
                            'user' => 'Người dùng',
                            'admin' => 'Quản trị viên'
                        ];
                        echo $roleLabels[$user['role']] ?? 'Người dùng';
                        ?>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-edit" onclick="openEditModal()">
                <i class="fas fa-edit"></i> Chỉnh sửa thông tin
            </button>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeEditModal()">&times;</button>
        <div class="modal-header">
            <h2>Chỉnh sửa thông tin</h2>
            <p>Cập nhật thông tin cá nhân của bạn</p>
        </div>

        <div id="modalAlert"></div>

        <form id="editProfileForm">
            <div class="form-group">
                <label for="edit_full_name">
                    <i class="fas fa-id-card"></i> Họ và tên <span style="color: #dc3545;">*</span>
                </label>
                <input type="text" 
                       id="edit_full_name" 
                       name="full_name" 
                       placeholder="Nhập họ và tên"
                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                       required>
                <div class="help-text">Tên đăng nhập và email không thể thay đổi</div>
            </div>

            <div class="form-group">
                <label for="edit_phone">
                    <i class="fas fa-phone"></i> Số điện thoại
                </label>
                <input type="tel" 
                       id="edit_phone" 
                       name="phone" 
                       placeholder="Nhập số điện thoại (10-11 số)"
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                       pattern="[0-9]{10,11}"
                       maxlength="11">
                <div class="help-text">Ví dụ: 0987654321</div>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
                <button type="button" class="btn-cancel" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        // Clear alert
        document.getElementById('modalAlert').innerHTML = '';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            closeEditModal();
        }
    }

    // Handle form submission
    document.getElementById('editProfileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fullName = document.getElementById('edit_full_name').value.trim();
        const phone = document.getElementById('edit_phone').value.trim();
        const alertDiv = document.getElementById('modalAlert');
        
        // Clear previous alerts
        alertDiv.innerHTML = '';
        
        // Validate
        if (!fullName) {
            alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Vui lòng nhập họ và tên</div>';
            return;
        }
        
        if (phone && !/^[0-9]{10,11}$/.test(phone)) {
            alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Số điện thoại không hợp lệ</div>';
            return;
        }
        
        // Submit form
        fetch('<?php echo BASE_URL; ?>/api/update-profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                full_name: fullName,
                phone: phone
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                // Reload page after 1 second
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra khi cập nhật thông tin</div>';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>

