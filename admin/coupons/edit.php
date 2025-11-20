<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$couponId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($couponId <= 0) {
    header('Location: ' . BASE_URL . '/admin/coupons/index.php');
    exit;
}

function coupon_convert_datetime(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        return null;
    }
}

function coupon_convert_to_input(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format('Y-m-d\TH:i');
    } catch (Throwable $e) {
        return '';
    }
}

function coupon_parse_number($rawValue, bool $allowFloat = true): ?float
{
    if (!isset($rawValue)) {
        return null;
    }

    $value = trim((string) $rawValue);
    if ($value === '') {
        return null;
    }

    $value = str_replace(['₫', 'đ', 'Đ'], '', $value);
    $value = str_replace(' ', '', $value);

    if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } elseif (strpos($value, ',') !== false) {
        $value = str_replace(',', '.', $value);
    }

    $value = preg_replace('/[^0-9.\-]/', '', $value ?? '');

    if ($value === '' || ($allowFloat ? !is_numeric($value) : !preg_match('/^-?\d+$/', $value))) {
        return null;
    }

    return $allowFloat ? (float) $value : (float) (int) $value;
}

$errors = [];
$successMessage = '';
$coupon = null;

$formValues = [
    'coupon_code' => '',
    'coupon_name' => '',
    'description' => '',
    'discount_type' => 'percent',
    'discount_value' => '',
    'max_discount_value' => '',
    'min_order_value' => '',
    'usage_limit' => '',
    'per_customer_limit' => '',
    'status' => 'draft',
    'start_date' => '',
    'end_date' => '',
];

$pdo = null;
try {
    $pdo = getPDO();
} catch (Throwable $exception) {
    $errors[] = 'Không thể kết nối tới cơ sở dữ liệu. Vui lòng thử lại sau.';
}

if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM coupons WHERE coupon_id = :id');
        $stmt->execute([':id' => $couponId]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $coupon = null;
    }

    if (!$coupon) {
        header('Location: ' . BASE_URL . '/admin/coupons/index.php');
        exit;
    }

    $formValues = [
        'coupon_code' => (string) $coupon['coupon_code'],
        'coupon_name' => (string) $coupon['coupon_name'],
        'description' => (string) ($coupon['description'] ?? ''),
        'discount_type' => (string) $coupon['discount_type'],
        'discount_value' => rtrim(rtrim(number_format((float) $coupon['discount_value'], 2, '.', ''), '0'), '.'),
        'max_discount_value' => $coupon['max_discount_value'] !== null ? rtrim(rtrim(number_format((float) $coupon['max_discount_value'], 2, '.', ''), '0'), '.') : '',
        'min_order_value' => $coupon['min_order_value'] !== null ? rtrim(rtrim(number_format((float) $coupon['min_order_value'], 2, '.', ''), '0'), '.') : '',
        'usage_limit' => $coupon['usage_limit'] !== null ? (string) (int) $coupon['usage_limit'] : '',
        'per_customer_limit' => $coupon['per_customer_limit'] !== null ? (string) (int) $coupon['per_customer_limit'] : '',
        'status' => (string) $coupon['status'],
        'start_date' => coupon_convert_to_input($coupon['start_date'] ?? null),
        'end_date' => coupon_convert_to_input($coupon['end_date'] ?? null),
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formValues = [
            'coupon_code' => strtoupper(trim((string) ($_POST['coupon_code'] ?? ''))),
            'coupon_name' => trim((string) ($_POST['coupon_name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'discount_type' => strtolower(trim((string) ($_POST['discount_type'] ?? 'percent'))),
            'discount_value' => trim((string) ($_POST['discount_value'] ?? '')),
            'max_discount_value' => trim((string) ($_POST['max_discount_value'] ?? '')),
            'min_order_value' => trim((string) ($_POST['min_order_value'] ?? '')),
            'usage_limit' => trim((string) ($_POST['usage_limit'] ?? '')),
            'per_customer_limit' => trim((string) ($_POST['per_customer_limit'] ?? '')),
            'status' => strtolower(trim((string) ($_POST['status'] ?? 'draft'))),
            'start_date' => trim((string) ($_POST['start_date'] ?? '')),
            'end_date' => trim((string) ($_POST['end_date'] ?? '')),
        ];

        $couponCode = $formValues['coupon_code'];
        $couponName = $formValues['coupon_name'];
        $discountType = in_array($formValues['discount_type'], ['percent', 'amount'], true) ? $formValues['discount_type'] : 'percent';
        $status = in_array($formValues['status'], ['draft', 'active', 'inactive', 'scheduled', 'expired'], true) ? $formValues['status'] : 'draft';

        $discountValue = coupon_parse_number($formValues['discount_value']);
        $maxDiscountValue = coupon_parse_number($formValues['max_discount_value']);
        $minOrderValue = coupon_parse_number($formValues['min_order_value']);
        $usageLimit = coupon_parse_number($formValues['usage_limit'], false);
        $perCustomerLimit = coupon_parse_number($formValues['per_customer_limit'], false);
        $startDate = coupon_convert_datetime($formValues['start_date']);
        $endDate = coupon_convert_datetime($formValues['end_date']);

        if ($couponCode === '') {
            $errors[] = 'Vui lòng nhập mã giảm giá.';
        }
        if ($couponName === '') {
            $errors[] = 'Vui lòng nhập tên khuyến mại.';
        }

        if ($discountValue === null || $discountValue <= 0) {
            $errors[] = 'Giá trị giảm giá phải lớn hơn 0.';
        } elseif ($discountType === 'percent' && $discountValue > 100) {
            $errors[] = 'Giá trị phần trăm tối đa là 100%.';
        }

        if ($maxDiscountValue !== null && $maxDiscountValue < 0) {
            $errors[] = 'Giá trị giảm tối đa không hợp lệ.';
        }

        if ($minOrderValue !== null && $minOrderValue < 0) {
            $errors[] = 'Giá trị đơn hàng tối thiểu không hợp lệ.';
        }

        if ($usageLimit !== null && $usageLimit < 0) {
            $errors[] = 'Giới hạn lượt sử dụng không hợp lệ.';
        }

        if ($perCustomerLimit !== null && $perCustomerLimit < 0) {
            $errors[] = 'Giới hạn mỗi khách hàng không hợp lệ.';
        }

        if ($startDate && $endDate && $startDate > $endDate) {
            $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }

        if (!$errors) {
            $codeCheck = $pdo->prepare('SELECT COUNT(*) FROM coupons WHERE coupon_code = :coupon_code AND coupon_id <> :id');
            $codeCheck->execute([
                ':coupon_code' => $couponCode,
                ':id' => $couponId,
            ]);

            if ($codeCheck->fetchColumn() > 0) {
                $errors[] = 'Mã giảm giá đã tồn tại. Vui lòng chọn mã khác.';
            }
        }

        if (!$errors) {
            try {
                $update = $pdo->prepare('UPDATE coupons SET coupon_code = :coupon_code, coupon_name = :coupon_name, description = :description, discount_type = :discount_type, discount_value = :discount_value, max_discount_value = :max_discount_value, min_order_value = :min_order_value, usage_limit = :usage_limit, per_customer_limit = :per_customer_limit, status = :status, start_date = :start_date, end_date = :end_date WHERE coupon_id = :id');
                $update->execute([
                    ':coupon_code' => $couponCode,
                    ':coupon_name' => $couponName,
                    ':description' => $formValues['description'] !== '' ? $formValues['description'] : null,
                    ':discount_type' => $discountType,
                    ':discount_value' => $discountValue,
                    ':max_discount_value' => $maxDiscountValue !== null ? $maxDiscountValue : null,
                    ':min_order_value' => $minOrderValue !== null ? $minOrderValue : null,
                    ':usage_limit' => $usageLimit !== null ? (int) $usageLimit : null,
                    ':per_customer_limit' => $perCustomerLimit !== null ? (int) $perCustomerLimit : null,
                    ':status' => $status,
                    ':start_date' => $startDate,
                    ':end_date' => $endDate,
                    ':id' => $couponId,
                ]);

                $successMessage = 'Cập nhật mã giảm giá thành công.';

                $stmt->execute([':id' => $couponId]);
                $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
                $formValues = [
                    'coupon_code' => (string) $coupon['coupon_code'],
                    'coupon_name' => (string) $coupon['coupon_name'],
                    'description' => (string) ($coupon['description'] ?? ''),
                    'discount_type' => (string) $coupon['discount_type'],
                    'discount_value' => rtrim(rtrim(number_format((float) $coupon['discount_value'], 2, '.', ''), '0'), '.'),
                    'max_discount_value' => $coupon['max_discount_value'] !== null ? rtrim(rtrim(number_format((float) $coupon['max_discount_value'], 2, '.', ''), '0'), '.') : '',
                    'min_order_value' => $coupon['min_order_value'] !== null ? rtrim(rtrim(number_format((float) $coupon['min_order_value'], 2, '.', ''), '0'), '.') : '',
                    'usage_limit' => $coupon['usage_limit'] !== null ? (string) (int) $coupon['usage_limit'] : '',
                    'per_customer_limit' => $coupon['per_customer_limit'] !== null ? (string) (int) $coupon['per_customer_limit'] : '',
                    'status' => (string) $coupon['status'],
                    'start_date' => coupon_convert_to_input($coupon['start_date'] ?? null),
                    'end_date' => coupon_convert_to_input($coupon['end_date'] ?? null),
                ];
            } catch (Throwable $e) {
                $errors[] = 'Không thể cập nhật mã giảm giá. Vui lòng thử lại.';
            }
        }
    }
}

$createdAtDisplay = 'Không xác định';
if ($coupon && !empty($coupon['created_at'])) {
    try {
        $created = new DateTimeImmutable($coupon['created_at']);
        $createdAtDisplay = $created->format(DATETIME_FORMAT);
    } catch (Throwable $e) {
        $createdAtDisplay = (string) $coupon['created_at'];
    }
}

$updatedAtDisplay = 'Không xác định';
if ($coupon && !empty($coupon['updated_at'])) {
    try {
        $updated = new DateTimeImmutable($coupon['updated_at']);
        $updatedAtDisplay = $updated->format(DATETIME_FORMAT);
    } catch (Throwable $e) {
        $updatedAtDisplay = (string) $coupon['updated_at'];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-layout {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 20px auto 40px;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: <?php echo GRID_GAP; ?>;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .admin-layout {
            grid-template-columns: 1fr;
        }
    }

    .coupon-edit {
        display: flex;
        flex-direction: column;
        gap: 24px;
        background-color: var(--white);
        border-radius: 18px;
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
    }

    .coupon-edit__header {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .coupon-edit__title {
        font-size: 30px;
        font-weight: 700;
        color: var(--primary);
    }

    .breadcrumb {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 14px;
        flex-wrap: wrap;
    }

    .breadcrumb a {
        color: var(--secondary);
        text-decoration: none;
        font-weight: 600;
    }

    .breadcrumb span {
        color: rgba(0, 0, 0, 0.55);
    }

    .notice {
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .notice--success {
        background: rgba(63, 142, 63, 0.12);
        color: #2a6a2a;
        border: 1px solid rgba(63, 142, 63, 0.35);
    }

    .notice--error {
        background: rgba(210, 64, 38, 0.12);
        color: #a52f1c;
        border: 1px solid rgba(210, 64, 38, 0.35);
    }

    .notice ul {
        margin: 0;
        padding-left: 18px;
    }

    .coupon-form {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .coupon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
    }

    .coupon-card {
        background-color: var(--white);
        border: 1px solid rgba(210, 100, 38, 0.15);
        border-radius: 16px;
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .coupon-card__title {
        font-size: 18px;
        font-weight: 600;
        color: var(--dark);
    }

    .coupon-card__body {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .coupon-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        font-size: 14px;
    }

    .coupon-meta__item {
        background: rgba(255, 247, 237, 0.65);
        border-radius: 14px;
        padding: 14px 16px;
        border: 1px solid rgba(210, 100, 38, 0.18);
    }

    .coupon-meta__label {
        display: block;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 6px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-field label {
        font-size: 14px;
        font-weight: 600;
        color: var(--dark);
    }

    .form-field label span {
        color: rgba(210, 64, 38, 0.85);
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
        width: 100%;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        padding: 12px 14px;
        font-size: 14px;
        transition: border 0.2s ease, box-shadow 0.2s ease;
        background-color: #fff;
    }

    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.15);
    }

    textarea {
        min-height: 140px;
        resize: vertical;
    }

    .coupon-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-self: center;
        width: min(320px, 100%);
        margin: 0 auto;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 18px;
        border-radius: 999px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--secondary), #f7c76a);
        color: var(--white);
        box-shadow: 0 12px 24px rgba(210, 100, 38, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 30px rgba(210, 100, 38, 0.35);
    }

    .btn-light {
        background: rgba(0, 0, 0, 0.05);
        color: var(--dark);
        text-decoration: none;
    }

    .btn-light:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 26px rgba(0, 0, 0, 0.12);
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .coupon-edit {
            padding: 26px 20px;
        }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="coupon-edit" aria-labelledby="coupon-edit-title">
        <div class="coupon-edit__header">
            <h1 class="coupon-edit__title" id="coupon-edit-title">Chỉnh sửa mã giảm giá</h1>
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                <span>/</span>
                <a href="<?php echo BASE_URL; ?>/admin/coupons/index.php">Quản lý mã giảm giá</a>
                <span>/</span>
                <span>Chỉnh sửa</span>
            </nav>
        </div>

        <div class="coupon-card">
            <h2 class="coupon-card__title">Thông tin hệ thống</h2>
            <div class="coupon-card__body coupon-meta">
                <div class="coupon-meta__item">
                    <span class="coupon-meta__label">ID mã giảm giá</span>
                    <span>#<?php echo htmlspecialchars((string) $couponId); ?></span>
                </div>
                <div class="coupon-meta__item">
                    <span class="coupon-meta__label">Ngày tạo</span>
                    <span><?php echo htmlspecialchars($createdAtDisplay); ?></span>
                </div>
                <div class="coupon-meta__item">
                    <span class="coupon-meta__label">Cập nhật lần cuối</span>
                    <span><?php echo htmlspecialchars($updatedAtDisplay); ?></span>
                </div>
                <div class="coupon-meta__item">
                    <span class="coupon-meta__label">Lượt sử dụng</span>
                    <span><?php echo htmlspecialchars((string) ($coupon['used_count'] ?? 0)); ?></span>
                </div>
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
                    <strong>Không thể cập nhật mã giảm giá:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="coupon-form" novalidate>
            <div class="coupon-card">
                <h2 class="coupon-card__title">Thông tin khuyến mại</h2>
                <div class="coupon-card__body">
                    <div class="coupon-grid">
                        <div class="form-field">
                            <label for="coupon_code">Mã giảm giá <span>*</span></label>
                            <input type="text" id="coupon_code" name="coupon_code" value="<?php echo htmlspecialchars($formValues['coupon_code']); ?>" placeholder="Ví dụ: GREEN10" maxlength="50" required>
                        </div>
                        <div class="form-field">
                            <label for="coupon_name">Tên khuyến mại <span>*</span></label>
                            <input type="text" id="coupon_name" name="coupon_name" value="<?php echo htmlspecialchars($formValues['coupon_name']); ?>" placeholder="Nhập tên hiển thị" required>
                        </div>
                        <div class="form-field">
                            <label for="discount_type">Loại giảm giá</label>
                            <select id="discount_type" name="discount_type">
                                <option value="percent" <?php echo $formValues['discount_type'] === 'percent' ? 'selected' : ''; ?>>Theo phần trăm (%)</option>
                                <option value="amount" <?php echo $formValues['discount_type'] === 'amount' ? 'selected' : ''; ?>>Theo số tiền (đ)</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="discount_value">Giá trị giảm <span>*</span></label>
                            <input type="text" id="discount_value" name="discount_value" value="<?php echo htmlspecialchars($formValues['discount_value']); ?>" placeholder="Ví dụ: 10 hoặc 50000" required>
                            <small id="discount_value_hint">Nhập phần trăm giảm (tối đa 100)</small>
                        </div>
                        <div class="form-field">
                            <label for="max_discount_value">Giảm tối đa (đ)</label>
                            <input type="text" id="max_discount_value" name="max_discount_value" value="<?php echo htmlspecialchars($formValues['max_discount_value']); ?>" placeholder="Chỉ áp dụng cho mã giảm theo %">
                            <small>Tùy chọn. Để trống nếu không giới hạn.</small>
                        </div>
                        <div class="form-field">
                            <label for="min_order_value">Đơn hàng tối thiểu (đ)</label>
                            <input type="text" id="min_order_value" name="min_order_value" value="<?php echo htmlspecialchars($formValues['min_order_value']); ?>" placeholder="Ví dụ: 300000">
                        </div>
                        <div class="form-field">
                            <label for="usage_limit">Giới hạn tổng lượt</label>
                            <input type="number" min="0" id="usage_limit" name="usage_limit" value="<?php echo htmlspecialchars($formValues['usage_limit']); ?>" placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="form-field">
                            <label for="per_customer_limit">Giới hạn mỗi khách</label>
                            <input type="number" min="0" id="per_customer_limit" name="per_customer_limit" value="<?php echo htmlspecialchars($formValues['per_customer_limit']); ?>" placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="form-field">
                            <label for="status">Trạng thái</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo $formValues['status'] === 'draft' ? 'selected' : ''; ?>>Bản nháp</option>
                                <option value="active" <?php echo $formValues['status'] === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                                <option value="scheduled" <?php echo $formValues['status'] === 'scheduled' ? 'selected' : ''; ?>>Sắp diễn ra</option>
                                <option value="inactive" <?php echo $formValues['status'] === 'inactive' ? 'selected' : ''; ?>>Tạm dừng</option>
                                <option value="expired" <?php echo $formValues['status'] === 'expired' ? 'selected' : ''; ?>>Hết hạn</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="start_date">Bắt đầu áp dụng</label>
                            <input type="datetime-local" id="start_date" name="start_date" value="<?php echo htmlspecialchars($formValues['start_date']); ?>">
                        </div>
                        <div class="form-field">
                            <label for="end_date">Kết thúc áp dụng</label>
                            <input type="datetime-local" id="end_date" name="end_date" value="<?php echo htmlspecialchars($formValues['end_date']); ?>">
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="description">Mô tả chi tiết</label>
                        <textarea id="description" name="description" placeholder="Nhập điều kiện áp dụng, ghi chú..."><?php echo htmlspecialchars($formValues['description']); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="coupon-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
                <a class="btn btn-light" href="<?php echo BASE_URL; ?>/admin/coupons/index.php"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const discountTypeSelect = document.getElementById('discount_type');
        const discountValueHint = document.getElementById('discount_value_hint');

        function updateHint() {
            if (!discountTypeSelect || !discountValueHint) {
                return;
            }

            if (discountTypeSelect.value === 'percent') {
                discountValueHint.textContent = 'Nhập phần trăm giảm (tối đa 100).';
            } else {
                discountValueHint.textContent = 'Nhập số tiền giảm (đơn vị VNĐ).';
            }
        }

        updateHint();
        if (discountTypeSelect) {
            discountTypeSelect.addEventListener('change', updateHint);
        }
    });
</script>

