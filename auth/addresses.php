<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();

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

$errors = [];
$successMessage = '';
$defaultOldInput = [
    'recipient_name' => '',
    'phone' => '',
    'street_address' => '',
    'ward' => '',
    'city' => '',
    'address_type' => 'home',
    'is_default' => '0'
];
$oldInput = $defaultOldInput;

try {
    $pdo = getPDO();
} catch (Exception $e) {
    $pdo = null;
    $errors[] = 'Không thể kết nối tới cơ sở dữ liệu. Vui lòng thử lại sau.';
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $recipientName = trim($_POST['recipient_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $streetAddress = trim($_POST['street_address'] ?? '');
        $ward = trim($_POST['ward'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $addressType = $_POST['address_type'] ?? 'home';
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        $oldInput = [
            'recipient_name' => $recipientName,
            'phone' => $phone,
            'street_address' => $streetAddress,
            'ward' => $ward,
            'city' => $city,
            'address_type' => $addressType,
            'is_default' => (string)$isDefault
        ];

        if ($recipientName === '') {
            $errors[] = 'Vui lòng nhập họ tên người nhận.';
        }

        if ($phone === '') {
            $errors[] = 'Vui lòng nhập số điện thoại.';
        } elseif (!preg_match('/^[0-9\s+().-]{8,20}$/', $phone)) {
            $errors[] = 'Số điện thoại không hợp lệ.';
        }

        if ($streetAddress === '' || $ward === '' || $city === '') {
            $errors[] = 'Vui lòng nhập đầy đủ địa chỉ (số nhà, phường/xã, tỉnh/thành phố).';
        }

        if (!array_key_exists($addressType, $addressTypeLabels)) {
            $errors[] = 'Loại địa chỉ không hợp lệ.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($isDefault) {
                    $stmt = $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id');
                    $stmt->execute(['user_id' => $user['user_id']]);
                }

                $stmt = $pdo->prepare('INSERT INTO user_addresses (user_id, recipient_name, phone, street_address, ward, city, address_type, is_default) VALUES (:user_id, :recipient_name, :phone, :street_address, :ward, :city, :address_type, :is_default)');
                $stmt->execute([
                    'user_id' => $user['user_id'],
                    'recipient_name' => $recipientName,
                    'phone' => $phone,
                    'street_address' => $streetAddress,
                    'ward' => $ward,
                    'city' => $city,
                    'address_type' => $addressType,
                    'is_default' => $isDefault
                ]);

                $pdo->commit();
                $successMessage = 'Thêm địa chỉ mới thành công.';
                $oldInput = $defaultOldInput;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Không thể lưu địa chỉ. Vui lòng thử lại.';
            }
        }
    } elseif ($action === 'set_default') {
        $addressId = (int) ($_POST['address_id'] ?? 0);

        if ($addressId <= 0) {
            $errors[] = 'Địa chỉ không hợp lệ.';
        } else {
            $stmt = $pdo->prepare('SELECT address_id FROM user_addresses WHERE address_id = :address_id AND user_id = :user_id');
            $stmt->execute([
                'address_id' => $addressId,
                'user_id' => $user['user_id']
            ]);

            if (!$stmt->fetch()) {
                $errors[] = 'Không tìm thấy địa chỉ này.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id')
                        ->execute(['user_id' => $user['user_id']]);
                    $pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE address_id = :address_id AND user_id = :user_id')
                        ->execute([
                            'address_id' => $addressId,
                            'user_id' => $user['user_id']
                        ]);
                    $pdo->commit();
                    $successMessage = 'Đã cập nhật địa chỉ mặc định.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Không thể cập nhật địa chỉ mặc định.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $addressId = (int) ($_POST['address_id'] ?? 0);

        if ($addressId <= 0) {
            $errors[] = 'Địa chỉ không hợp lệ.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM user_addresses WHERE address_id = :address_id AND user_id = :user_id');
            $stmt->execute([
                'address_id' => $addressId,
                'user_id' => $user['user_id']
            ]);

            if ($stmt->rowCount() > 0) {
                $successMessage = 'Đã xóa địa chỉ.';
            } else {
                $errors[] = 'Không thể xóa địa chỉ này.';
            }
        }
    }
}

$addresses = [];
if ($pdo) {
    $stmt = $pdo->prepare('SELECT address_id, recipient_name, phone, street_address, ward, city, address_type, is_default, created_at FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC');
    $stmt->execute(['user_id' => $user['user_id']]);
    $addresses = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<style>
    body {
        background-color: var(--light);
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
        margin-bottom: 8px;
    }

    .account-main-header p {
        color: var(--dark);
    }

    .alert {
        padding: 14px 18px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert.success {
        background-color: #e9f7ef;
        color: #1e6e3f;
        border: 1px solid #c7ebd6;
    }

    .alert.error {
        background-color: #fdecea;
        color: #c0392b;
        border: 1px solid #f5c6cb;
    }

    .alert ul {
        margin: 0;
        padding-left: 20px;
    }

    .btn-add-address {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s ease;
        margin-bottom: 20px;
    }

    .btn-add-address:hover {
        background-color: #2d4a2d;
    }

    .address-form {
        display: none;
        margin-bottom: 30px;
        padding: 25px;
        border: 1px solid #f0f0f0;
        border-radius: 10px;
        background-color: #fafafa;
    }

    .address-form.visible {
        display: block;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        font-weight: 600;
        display: block;
        margin-bottom: 8px;
        color: var(--dark);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }

    .address-type-options {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .type-option {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border: 1px solid #ddd;
        border-radius: 999px;
        cursor: pointer;
        font-size: 14px;
    }

    .type-option input {
        margin: 0;
    }

    .checkbox-default {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        cursor: pointer;
    }

    .checkbox-default input {
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
        flex-shrink: 0;
    }

    .address-form-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-submit {
        padding: 12px 28px;
        border: none;
        background-color: var(--primary);
        color: var(--white);
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-cancel {
        padding: 12px 28px;
        border: 1px solid var(--primary);
        background-color: transparent;
        color: var(--primary);
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .address-list {
        display: grid;
        gap: 20px;
    }

    .address-card {
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 20px;
        display: grid;
        gap: 12px;
    }

    .address-card.default {
        border-color: var(--primary);
        background-color: #f4fff5;
    }

    .address-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .address-recipient {
        font-size: 16px;
        font-weight: 600;
    }

    .address-labels {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
    }

    .address-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background-color: var(--light);
        color: var(--primary);
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .default-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background-color: var(--primary);
        color: var(--white);
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .address-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .address-actions form {
        display: inline;
    }

    .btn-secondary,
    .btn-danger {
        padding: 10px 18px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-secondary {
        background-color: #e8f0ff;
        color: #1d4ed8;
    }

    .btn-danger {
        background-color: #fee2e2;
        color: #b91c1c;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        border: 1px dashed #ddd;
        border-radius: 10px;
        color: #777;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .account-content {
            grid-template-columns: 1fr;
        }

        .account-main {
            padding: 20px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .address-card-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="account-container">
    <div class="account-content">
        <?php include 'sidebar-account.php'; ?>

        <div class="account-main">
            <div class="account-main-header">
                <h1>Sổ địa chỉ</h1>
                <p>Quản lý địa chỉ giao hàng của bạn</p>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert success"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <button class="btn-add-address" id="toggleAddressForm" type="button">
                <i class="fas fa-plus"></i> Thêm địa chỉ mới
            </button>

            <form method="post" class="address-form <?php echo (!empty($errors) && (($_POST['action'] ?? '') === 'add')) ? 'visible' : ''; ?>" id="addressForm">
                <input type="hidden" name="action" value="add">

                <div class="form-row">
                    <div class="form-group">
                        <label for="recipient_name">Họ và tên</label>
                        <input type="text" id="recipient_name" name="recipient_name" value="<?php echo htmlspecialchars($oldInput['recipient_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($oldInput['phone']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="street_address">Địa chỉ nhà / số nhà</label>
                    <input type="text" id="street_address" name="street_address" value="<?php echo htmlspecialchars($oldInput['street_address']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ward">Phường / Xã</label>
                        <input type="text" id="ward" name="ward" value="<?php echo htmlspecialchars($oldInput['ward']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="city">Tỉnh / Thành phố</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($oldInput['city']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Loại địa chỉ</label>
                    <div class="address-type-options">
                        <?php foreach ($addressTypeLabels as $typeKey => $typeLabel): ?>
                            <label class="type-option">
                                <input type="radio" name="address_type" value="<?php echo $typeKey; ?>" <?php echo ($oldInput['address_type'] === $typeKey) ? 'checked' : ''; ?>>
                                <span><?php echo $typeLabel; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-default">
                        <input type="checkbox" name="is_default" value="1" <?php echo ($oldInput['is_default'] === '1') ? 'checked' : ''; ?>>
                        <span>Đặt địa chỉ này làm mặc định</span>
                    </label>
                </div>

                <div class="address-form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Lưu địa chỉ
                    </button>
                    <button type="button" class="btn-cancel" id="cancelForm">
                        Hủy bỏ
                    </button>
                </div>
            </form>

            <div class="address-list">
                <?php if (empty($addresses)): ?>
                    <div class="empty-state">
                        Chưa có địa chỉ nào. Hãy thêm địa chỉ đầu tiên của bạn!
                    </div>
                <?php else: ?>
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                            <div class="address-card-header">
                                <div>
                                    <div class="address-recipient"><?php echo htmlspecialchars($address['recipient_name']); ?></div>
                                    <div><?php echo htmlspecialchars($address['phone']); ?></div>
                                </div>
                                <div class="address-labels">
                                    <div class="address-tag">
                                        <i class="fas fa-tag"></i>
                                        <?php echo $addressTypeLabels[$address['address_type']] ?? 'Địa chỉ'; ?>
                                    </div>
                                    <?php if ($address['is_default']): ?>
                                        <span class="default-badge">
                                            <i class="fas fa-check"></i> Mặc định
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div>
                                <?php echo htmlspecialchars($address['street_address']); ?><br>
                                <?php echo htmlspecialchars($address['ward']); ?>, <?php echo htmlspecialchars($address['city']); ?>
                            </div>

                            <div class="address-actions">
                                <?php if (!$address['is_default']): ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="set_default">
                                        <input type="hidden" name="address_id" value="<?php echo (int) $address['address_id']; ?>">
                                        <button type="submit" class="btn-secondary">Đặt làm mặc định</button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="address_id" value="<?php echo (int) $address['address_id']; ?>">
                                    <button type="submit" class="btn-danger">Xóa</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('addressForm');
        const toggleBtn = document.getElementById('toggleAddressForm');
        const cancelBtn = document.getElementById('cancelForm');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                form.classList.toggle('visible');
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                form.classList.remove('visible');
            });
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
