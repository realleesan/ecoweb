<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$keyword = isset($_GET['keyword']) ? trim((string) $_GET['keyword']) : '';
$statusFilter = isset($_GET['status']) ? trim((string) $_GET['status']) : '';

$errors = [];
$infoMessages = [];
$successMessages = [];
$coupons = [];
$totalCoupons = 0;
$missingCouponTable = false;

if (!function_exists('coupon_field')) {
    /**
     * Safely extract the first available key from coupon row.
     */
    function coupon_field(array $row, array $keys, $default = '')
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return $default;
    }
}

if (!function_exists('coupon_parse_date')) {
    function coupon_parse_date($value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable((string) $value);
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('coupon_normalize_status')) {
    function coupon_normalize_status(array $coupon): array
    {
        $now = new DateTimeImmutable('now');

        $rawStatus = strtolower(trim((string) coupon_field($coupon, ['status', 'state', 'coupon_status', 'current_status'], '')));
        $activeFlag = coupon_field($coupon, ['is_active', 'active', 'enabled', 'is_enabled'], null);
        $startDate = coupon_parse_date(coupon_field($coupon, ['start_date', 'start_at', 'valid_from', 'starts_at'], ''));
        $endDate = coupon_parse_date(coupon_field($coupon, ['end_date', 'end_at', 'valid_until', 'expires_at', 'expiry_date'], ''));

        $statusKey = '';

        if ($rawStatus !== '') {
            if (in_array($rawStatus, ['active', 'enabled', 'ongoing', 'published'], true)) {
                $statusKey = 'active';
            } elseif (in_array($rawStatus, ['inactive', 'disabled', 'paused'], true)) {
                $statusKey = 'inactive';
            } elseif (in_array($rawStatus, ['scheduled', 'upcoming', 'pending'], true)) {
                $statusKey = 'scheduled';
            } elseif (in_array($rawStatus, ['expired', 'ended', 'finished'], true)) {
                $statusKey = 'expired';
            } elseif (in_array($rawStatus, ['draft'], true)) {
                $statusKey = 'draft';
            }
        }

        if ($statusKey === '' && $activeFlag !== null) {
            $statusKey = (int) $activeFlag === 1 ? 'active' : 'inactive';
        }

        if ($statusKey === '') {
            if ($endDate && $endDate < $now) {
                $statusKey = 'expired';
            } elseif ($startDate && $startDate > $now) {
                $statusKey = 'scheduled';
            } else {
                $statusKey = 'active';
            }
        }

        if ($statusKey === 'active') {
            if ($endDate && $endDate < $now) {
                $statusKey = 'expired';
            } elseif ($startDate && $startDate > $now) {
                $statusKey = 'scheduled';
            }
        }

        $statusMap = [
            'active' => ['label' => 'Đang hoạt động', 'class' => 'status-badge status-badge--active'],
            'inactive' => ['label' => 'Tạm dừng', 'class' => 'status-badge status-badge--inactive'],
            'scheduled' => ['label' => 'Sắp diễn ra', 'class' => 'status-badge status-badge--scheduled'],
            'expired' => ['label' => 'Hết hạn', 'class' => 'status-badge status-badge--expired'],
            'draft' => ['label' => 'Bản nháp', 'class' => 'status-badge status-badge--draft'],
        ];

        return $statusMap[$statusKey] ?? ['label' => 'Không rõ', 'class' => 'status-badge status-badge--unknown', 'key' => 'unknown'];
    }
}

if (!function_exists('coupon_status_key')) {
    function coupon_status_key(array $coupon): string
    {
        $meta = coupon_normalize_status($coupon);
        if (isset($meta['key'])) {
            return (string) $meta['key'];
        }

        if (strpos($meta['class'], '--active') !== false) {
            return 'active';
        }
        if (strpos($meta['class'], '--inactive') !== false) {
            return 'inactive';
        }
        if (strpos($meta['class'], '--scheduled') !== false) {
            return 'scheduled';
        }
        if (strpos($meta['class'], '--expired') !== false) {
            return 'expired';
        }
        if (strpos($meta['class'], '--draft') !== false) {
            return 'draft';
        }

        return 'unknown';
    }
}

if (!function_exists('coupon_format_discount')) {
    function coupon_format_discount(array $coupon): string
    {
        $type = strtolower((string) coupon_field($coupon, ['discount_type', 'type', 'coupon_type'], ''));
        $value = coupon_field($coupon, ['discount_value', 'value', 'amount', 'discount_amount'], '');

        if ($type === 'percent' || $type === 'percentage') {
            return $value !== '' ? 'Giảm ' . rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',') . '%' : 'Giảm theo %';
        }

        if ($value === '' || !is_numeric($value)) {
            return 'Giảm giá theo đơn hàng';
        }

        return 'Giảm ' . number_format((float) $value, 0, ',', '.') . ' đ';
    }
}

if (!function_exists('coupon_usage_summary')) {
    function coupon_usage_summary(array $coupon): string
    {
        $used = (int) coupon_field($coupon, ['used_count', 'usage_count', 'times_used', 'redeemed'], 0);
        $limit = coupon_field($coupon, ['usage_limit', 'max_usage', 'limit_per_coupon', 'quantity'], null);

        if ($limit === null || $limit === '' || (int) $limit === 0) {
            return $used . ' lượt đã dùng';
        }

        return $used . ' / ' . (int) $limit . ' lượt';
    }
}

if (!function_exists('coupon_date_range')) {
    function coupon_date_range(array $coupon): string
    {
        $start = coupon_parse_date(coupon_field($coupon, ['start_date', 'start_at', 'valid_from', 'starts_at'], ''));
        $end = coupon_parse_date(coupon_field($coupon, ['end_date', 'end_at', 'valid_until', 'expires_at', 'expiry_date'], ''));

        $format = defined('DATE_FORMAT') ? DATE_FORMAT : 'd/m/Y';

        if (!$start && !$end) {
            return 'Không giới hạn thời gian';
        }

        if ($start && $end) {
            return $start->format($format) . ' - ' . $end->format($format);
        }

        if ($start) {
            return 'Từ ' . $start->format($format);
        }

        return 'Đến ' . $end->format($format);
    }
}

try {
    $pdo = getPDO();

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'coupons'");
    $couponTableExists = $tableCheck !== false && $tableCheck->rowCount() > 0;

    if (!$couponTableExists) {
        $missingCouponTable = true;
        $infoMessages[] = 'Bảng dữ liệu "coupons" chưa tồn tại. Vui lòng tạo bảng hoặc chạy migration phù hợp để quản lý mã giảm giá.';
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon_id'])) {
            $deleteId = (int) $_POST['delete_coupon_id'];

            if ($deleteId <= 0) {
                $errors[] = 'Mã giảm giá cần xóa không hợp lệ.';
            } else {
                try {
                    $deleteStmt = $pdo->prepare('DELETE FROM coupons WHERE coupon_id = :id');
                    $deleteStmt->execute([':id' => $deleteId]);

                    if ($deleteStmt->rowCount() > 0) {
                        $successMessages[] = 'Đã xóa mã giảm giá thành công.';
                    } else {
                        $errors[] = 'Mã giảm giá không tồn tại hoặc đã bị xóa.';
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Không thể xóa mã giảm giá. Vui lòng thử lại.';
                }
            }
        }

        $stmt = $pdo->query('SELECT * FROM coupons');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as $coupon) {
            if ($keyword !== '') {
                $haystack = strtolower(
                    trim(
                        coupon_field($coupon, ['coupon_code', 'code'], '') . ' ' .
                        coupon_field($coupon, ['coupon_name', 'name', 'title'], '') . ' ' .
                        coupon_field($coupon, ['description', 'note', 'details'], '')
                    )
                );

                if (strpos($haystack, strtolower($keyword)) === false) {
                    continue;
                }
            }

            if ($statusFilter !== '') {
                $statusKey = coupon_status_key($coupon);
                if ($statusKey !== $statusFilter) {
                    continue;
                }
            }

            $coupons[] = $coupon;
        }

        $totalCoupons = count($coupons);
    }
} catch (Throwable $exception) {
    $errors[] = 'Không thể tải danh sách mã giảm giá. Vui lòng thử lại sau.';
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-layout {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 20px auto;
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

    .coupons-page {
        background: var(--white);
        border-radius: 16px;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.08);
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        display: flex;
        flex-direction: column;
        gap: 28px;
    }

    .coupons-page__header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
    }

    .coupons-page__title {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .coupons-page__title h1 {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
    }

    .breadcrumb {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.55);
    }

    .breadcrumb a {
        text-decoration: none;
        color: var(--secondary);
        font-weight: 600;
    }

    .coupons-page__info {
        color: rgba(0, 0, 0, 0.55);
        font-size: 14px;
    }

    .btn-add-coupon {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--secondary), #f7c76a);
        box-shadow: 0 15px 30px rgba(210, 100, 38, 0.25);
        color: var(--white);
        text-decoration: none;
        font-weight: 600;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-add-coupon:hover {
        transform: translateY(-2px);
        box-shadow: 0 22px 38px rgba(210, 100, 38, 0.35);
    }

    .notice {
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        font-size: 14px;
    }

    .notice--error {
        background: rgba(210, 64, 38, 0.12);
        border: 1px solid rgba(210, 64, 38, 0.35);
        color: #a52f1c;
    }

    .notice--info {
        background: rgba(60, 96, 60, 0.08);
        border: 1px solid rgba(60, 96, 60, 0.25);
        color: #305830;
    }

    .coupon-filter {
        background: rgba(255, 247, 237, 0.9);
        border: 1px solid rgba(210, 100, 38, 0.15);
        border-radius: 16px;
        padding: 20px;
        display: grid;
        grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) auto auto;
        gap: 16px;
        align-items: end;
    }

    .coupon-filter__field label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--dark);
    }

    .coupon-filter__field input,
    .coupon-filter__field select {
        width: 100%;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        padding: 10px 14px;
        font-size: 14px;
        background: var(--white);
        transition: border 0.2s ease, box-shadow 0.2s ease;
    }

    .coupon-filter__field input:focus,
    .coupon-filter__field select:focus {
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.15);
        outline: none;
    }

    .coupon-filter__actions {
        display: flex;
        gap: 10px;
    }

    .btn-filter-submit,
    .btn-filter-reset {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-filter-submit {
        background: linear-gradient(135deg, var(--secondary), #f6c05d);
        color: var(--white);
    }

    .btn-filter-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(210, 100, 38, 0.25);
    }

    .btn-filter-reset {
        background: var(--white);
        color: var(--secondary);
        border: 1px solid rgba(210, 100, 38, 0.25);
    }

    .btn-filter-reset:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 18px rgba(210, 100, 38, 0.18);
    }

    .coupon-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        width: 100%;
        justify-items: stretch;
    }

    .coupon-card {
        border: 1px solid #f0ebe3;
        border-radius: 14px;
        padding: 18px 20px;
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 60px);
        gap: 16px;
        align-items: flex-start;
        background: var(--white);
        width: 100%;
        box-sizing: border-box;
    }

    .coupon-card__header {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .coupon-card__code {
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
    }

    .coupon-card__name {
        font-size: 14px;
        color: rgba(0, 0, 0, 0.6);
    }

    .coupon-card__label {
        font-size: 12px;
        text-transform: uppercase;
        color: rgba(0, 0, 0, 0.45);
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }

    .coupon-card__value {
        font-weight: 600;
        color: var(--dark);
        font-size: 15px;
    }

    .coupon-card__actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        justify-content: flex-start;
        align-items: stretch;
    }

    .coupon-card__actions a,
    .coupon-card__actions button {
        width: 100%;
        min-width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--dark);
        background: rgba(0, 0, 0, 0.06);
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;
        font-weight: 600;
        gap: 6px;
    }

    .coupon-card__actions a:hover,
    .coupon-card__actions button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 18px rgba(0, 0, 0, 0.18);
    }

    .coupon-card__actions button {
        background: rgba(192, 57, 43, 0.1);
        color: #c0392b;
    }

    .coupon-card__actions button:hover {
        background: rgba(192, 57, 43, 0.18);
    }

    .coupon-card__delete-form {
        margin: 0;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
    }

    .status-badge::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .status-badge--active {
        color: #2f7f2f;
        background: rgba(47, 127, 47, 0.12);
    }

    .status-badge--active::before {
        background: #2f7f2f;
    }

    .status-badge--inactive {
        color: #7a7a7a;
        background: rgba(0, 0, 0, 0.08);
    }

    .status-badge--inactive::before {
        background: #7a7a7a;
    }

    .status-badge--scheduled {
        color: #c87b1f;
        background: rgba(200, 123, 31, 0.12);
    }

    .status-badge--scheduled::before {
        background: #c87b1f;
    }

    .status-badge--expired {
        color: #c0392b;
        background: rgba(192, 57, 43, 0.12);
    }

    .status-badge--expired::before {
        background: #c0392b;
    }

    .status-badge--draft {
        color: #3c5b96;
        background: rgba(60, 91, 150, 0.12);
    }

    .status-badge--draft::before {
        background: #3c5b96;
    }

    .status-badge--unknown {
        color: #6c6c6c;
        background: rgba(0, 0, 0, 0.08);
    }

    .status-badge--unknown::before {
        background: #6c6c6c;
    }

    .coupon-empty {
        background: rgba(255, 247, 237, 0.75);
        border: 1px dashed rgba(210, 100, 38, 0.32);
        border-radius: 16px;
        padding: 32px 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: center;
        text-align: center;
        color: rgba(0, 0, 0, 0.55);
    }

    .coupon-empty__icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        background: rgba(210, 100, 38, 0.14);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--secondary);
        font-size: 22px;
    }

    .coupon-empty__title {
        font-weight: 600;
        font-size: 16px;
        color: var(--dark);
    }

    .coupon-empty .btn-add-coupon {
        box-shadow: none;
        margin-top: 6px;
    }

    @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
        .coupon-card {
            grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1fr) auto;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .coupon-filter {
            grid-template-columns: 1fr;
        }

        .coupon-filter__actions {
            justify-content: flex-start;
        }

        .coupon-card {
            grid-template-columns: 1fr;
            align-items: flex-start;
        }

        .coupon-card__actions {
            flex-direction: row;
            justify-content: flex-start;
            align-items: center;
        }

        .coupon-card__actions a,
        .coupon-card__actions button {
            width: auto;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .btn-add-coupon {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="coupons-page">
        <header class="coupons-page__header">
            <div class="coupons-page__title">
                <h1>Quản Lý Mã Giảm Giá</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <span>Quản Lý Mã Giảm Giá</span>
                </nav>
                <p class="coupons-page__info">Tổng cộng: <?php echo $totalCoupons; ?> mã giảm giá</p>
            </div>
            <a class="btn-add-coupon" href="<?php echo BASE_URL; ?>/admin/coupons/add.php">
                <i class="fas fa-plus"></i>
                <span>Thêm Mã Giảm Giá Mới</span>
            </a>
        </header>

        <?php if ($errors): ?>
            <div class="notice notice--error" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Không thể tải dữ liệu:</strong>
                    <ul style="margin:8px 0 0; padding-left:20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($successMessages): ?>
            <?php foreach ($successMessages as $message): ?>
                <div class="notice notice--success" role="status">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($infoMessages): ?>
            <?php foreach ($infoMessages as $message): ?>
                <div class="notice notice--info" role="status">
                    <i class="fas fa-info-circle"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form class="coupon-filter" method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="coupon-filter__field">
                <label for="keyword">Tìm Kiếm</label>
                <input type="text" id="keyword" name="keyword" placeholder="Mã code, tên mã giảm giá..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="coupon-filter__field">
                <label for="status">Trạng Thái</label>
                <select id="status" name="status">
                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                    <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>>Sắp diễn ra</option>
                    <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Hết hạn</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Tạm dừng</option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Bản nháp</option>
                </select>
            </div>
            <div class="coupon-filter__actions">
                <button type="submit" class="btn-filter-submit"><i class="fas fa-search"></i> Lọc</button>
            </div>
            <div class="coupon-filter__actions">
                <a class="btn-filter-reset" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <i class="fas fa-redo"></i>
                    Làm mới
                </a>
            </div>
        </form>

        <?php if (!$missingCouponTable && $totalCoupons > 0): ?>
            <div class="coupon-list" role="list" aria-label="Danh sách mã giảm giá">
                <?php foreach ($coupons as $coupon):
                    $code = coupon_field($coupon, ['coupon_code', 'code'], 'Mã không xác định');
                    $name = coupon_field($coupon, ['coupon_name', 'name', 'title'], '');
                    $statusMeta = coupon_normalize_status($coupon);
                    $statusKey = coupon_status_key($coupon);
                    $statusMeta['key'] = $statusKey;
                ?>
                    <article class="coupon-card" role="listitem">
                        <div class="coupon-card__header">
                            <span class="coupon-card__code"><?php echo htmlspecialchars($code); ?></span>
                            <?php if ($name !== ''): ?>
                                <span class="coupon-card__name"><?php echo htmlspecialchars($name); ?></span>
                            <?php endif; ?>
                            <span class="<?php echo htmlspecialchars($statusMeta['class']); ?>">
                                <?php echo htmlspecialchars($statusMeta['label']); ?>
                            </span>
                        </div>
                        <div>
                            <div class="coupon-card__label">Giá trị ưu đãi</div>
                            <div class="coupon-card__value"><?php echo htmlspecialchars(coupon_format_discount($coupon)); ?></div>
                        </div>
                        <div>
                            <div class="coupon-card__label">Thời gian hiệu lực</div>
                            <div class="coupon-card__value"><?php echo htmlspecialchars(coupon_date_range($coupon)); ?></div>
                        </div>
                        <div>
                            <div class="coupon-card__label">Lượt sử dụng</div>
                            <div class="coupon-card__value"><?php echo htmlspecialchars(coupon_usage_summary($coupon)); ?></div>
                        </div>
                        <div class="coupon-card__actions">
                            <a href="<?php echo BASE_URL; ?>/admin/coupons/edit.php?id=<?php echo urlencode((string) coupon_field($coupon, ['coupon_id', 'id'], '')); ?>" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="post" class="coupon-card__delete-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?');">
                                <input type="hidden" name="delete_coupon_id" value="<?php echo htmlspecialchars((string) coupon_field($coupon, ['coupon_id', 'id'], '')); ?>">
                                <button type="submit" title="Xóa" aria-label="Xóa mã giảm giá">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php elseif ($missingCouponTable || $totalCoupons === 0): ?>
            <div class="coupon-empty" role="status">
                <div class="coupon-empty__icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="coupon-empty__title">Không tìm thấy mã giảm giá nào</div>
                <p>Hãy điều chỉnh bộ lọc hoặc thêm mã giảm giá mới.</p>
                <a class="btn-add-coupon" href="<?php echo BASE_URL; ?>/admin/coupons/add.php">
                    <i class="fas fa-plus"></i>
                    Thêm Mã Giảm Giá Mới
                </a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

