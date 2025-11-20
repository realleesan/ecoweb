<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

function slugify(string $value): string
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }

    if (class_exists('Transliterator')) {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower();');
        if ($transliterator) {
            $trimmed = $transliterator->transliterate($trimmed);
        }
    } else {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $trimmed);
        $trimmed = $converted !== false ? strtolower($converted) : strtolower($trimmed);
    }

    $trimmed = preg_replace('~[^\pL\d]+~u', '-', $trimmed ?? '');
    $trimmed = preg_replace('~[^-\w]+~', '', $trimmed ?? '');
    $trimmed = preg_replace('~-+~', '-', $trimmed ?? '');

    return trim((string) $trimmed, '-');
}

function ensureCategoryStatusColumn(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    try {
        $columnStmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'is_active'");
        $exists = $columnStmt && $columnStmt->fetch();

        if (!$exists) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER image");
        }
    } catch (Throwable $e) {
        // Nếu không thể thêm cột, giữ trạng thái đã kiểm tra để tránh lặp vô hạn
    } finally {
        $checked = true;
    }
}

$categoryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($categoryId <= 0) {
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

$errors = [];
$successMessage = '';
$deleteSuccessMessage = '';
$productCount = 0;
$category = null;
$formValues = [
    'category_name' => '',
    'slug' => '',
    'description' => '',
    'image' => '',
    'is_active' => 1,
];

try {
    $pdo = getPDO();
} catch (Throwable $exception) {
    $errors[] = 'Không thể kết nối tới cơ sở dữ liệu. Vui lòng thử lại sau.';
    $pdo = null;
}

if ($pdo) {
    ensureCategoryStatusColumn($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        try {
            $pdo->beginTransaction();

            $categoryStmt = $pdo->prepare('SELECT category_name FROM categories WHERE category_id = :id FOR UPDATE');
            $categoryStmt->execute([':id' => $categoryId]);
            $category = $categoryStmt->fetch();

            if (!$category) {
                $pdo->rollBack();
                $errors[] = 'Danh mục không tồn tại hoặc đã được xóa.';
            } else {
                $productCountStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
                $productCountStmt->execute([':id' => $categoryId]);
                $productCount = (int) $productCountStmt->fetchColumn();

                if ($productCount > 0) {
                    $pdo->rollBack();
                    $errors[] = 'Không thể xóa danh mục đang được sử dụng bởi ' . $productCount . ' sản phẩm.';
                } else {
                    $deleteStmt = $pdo->prepare('DELETE FROM categories WHERE category_id = :id');
                    $deleteStmt->execute([':id' => $categoryId]);
                    $pdo->commit();
                    $deleteSuccessMessage = 'Đã xóa danh mục "' . $category['category_name'] . '".';
                }
            }
        } catch (Throwable $deleteException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Không thể xóa danh mục. Vui lòng thử lại.';
        }
    }

    if ($deleteSuccessMessage === '') {
        $stmt = $pdo->prepare('SELECT category_id, category_name, slug, description, image, is_active, created_at FROM categories WHERE category_id = :id');
        $stmt->execute([':id' => $categoryId]);
        $category = $stmt->fetch();

        if (!$category) {
            header('Location: ' . BASE_URL . '/admin/categories/index.php');
            exit;
        }

        $formValues = [
            'category_name' => (string) $category['category_name'],
            'slug' => (string) $category['slug'],
            'description' => (string) ($category['description'] ?? ''),
            'image' => (string) ($category['image'] ?? ''),
            'is_active' => (int) ($category['is_active'] ?? 1),
        ];

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
        $countStmt->execute([':id' => $categoryId]);
        $productCount = (int) $countStmt->fetchColumn();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
            $categoryName = isset($_POST['category_name']) ? trim((string) $_POST['category_name']) : '';
            $slugInput = isset($_POST['slug']) ? trim((string) $_POST['slug']) : '';
            $description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
            $image = isset($_POST['image']) ? trim((string) $_POST['image']) : '';
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            $formValues = [
                'category_name' => $categoryName,
                'slug' => $slugInput,
                'description' => $description,
                'image' => $image,
                'is_active' => $isActive,
            ];

            if ($categoryName === '') {
                $errors[] = 'Vui lòng nhập tên danh mục.';
            }

            $slug = $slugInput === '' ? slugify($categoryName) : slugify($slugInput);
            if ($slug === '') {
                $slug = 'danh-muc-' . $categoryId;
            }

            if (!$errors) {
                $slugCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = :slug AND category_id <> :id');
                $slugCheck->execute([':slug' => $slug, ':id' => $categoryId]);
                if ($slugCheck->fetchColumn() > 0) {
                    $errors[] = 'Slug đã tồn tại. Vui lòng chọn slug khác.';
                }
            }

            if (!$errors) {
                try {
                    $updateStmt = $pdo->prepare('UPDATE categories SET category_name = :name, slug = :slug, description = :description, image = :image, is_active = :is_active WHERE category_id = :id');
                    $updateStmt->execute([
                        ':name' => $categoryName,
                        ':slug' => $slug,
                        ':description' => $description !== '' ? $description : null,
                        ':image' => $image !== '' ? $image : null,
                        ':is_active' => $isActive,
                        ':id' => $categoryId,
                    ]);

                    $successMessage = 'Cập nhật danh mục thành công.';

                    $stmt->execute([':id' => $categoryId]);
                    $category = $stmt->fetch();
                    $formValues = [
                        'category_name' => (string) $category['category_name'],
                        'slug' => (string) $category['slug'],
                        'description' => (string) ($category['description'] ?? ''),
                        'image' => (string) ($category['image'] ?? ''),
                        'is_active' => (int) ($category['is_active'] ?? 1),
                    ];
                } catch (Throwable $e) {
                    $errors[] = 'Không thể cập nhật danh mục. Vui lòng thử lại.';
                }
            }
        }
    }
}

$createdAtDisplay = 'Không xác định';
if ($category && !empty($category['created_at'])) {
    try {
        $created = new DateTime($category['created_at']);
        $createdAtDisplay = $created->format(DATETIME_FORMAT);
    } catch (Throwable $e) {
        $createdAtDisplay = $category['created_at'];
    }
}

$selectedImageParam = isset($_GET['selected_image']) ? trim((string) $_GET['selected_image']) : '';
if ($selectedImageParam !== '') {
    $formValues['image'] = $selectedImageParam;
}

$returnToUrl = BASE_URL . '/admin/categories/edit.php?id=' . $categoryId;
$imagePickerUrl = BASE_URL . '/admin/categories/images.php?mode=picker&category_id=' . $categoryId . '&return_to=' . rawurlencode($returnToUrl);

$imagePlaceholder = 'https://via.placeholder.com/320x200/FFF7ED/3C603C?text=Ch%C6%B0a+c%C3%B3+h%C3%ACnh+%E1%BA%A3nh';

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

    .category-edit {
        display: flex;
        flex-direction: column;
        gap: 24px;
        background-color: var(--white);
        border-radius: 18px;
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
    }

    .category-edit__header {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .category-edit__title {
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

    .category-edit__form {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .category-edit__grid {
        display: grid;
        gap: 24px;
        grid-template-columns: 2fr 1fr;
        align-items: start;
    }

    @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
        .category-edit__grid {
            grid-template-columns: 1fr;
        }
    }

    .category-edit__side {
        display: flex;
        flex-direction: column;
        gap: 18px;
        height: 100%;
        align-self: stretch;
    }

    .category-edit__actions {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .card {
        background-color: var(--white);
        border: 1px solid rgba(210, 100, 38, 0.15);
        border-radius: 16px;
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .card__title {
        font-size: 18px;
        font-weight: 600;
        color: var(--dark);
    }

    .card__subtitle {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark);
        margin-top: 12px;
        margin-bottom: 4px;
    }

    .card__section {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .card__section--image {
        gap: 20px;
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

    .form-field input[type="text"],
    .form-field input[type="url"],
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
    .form-field textarea:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.15);
    }

    .form-field small {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.6);
    }

    textarea {
        min-height: 140px;
        resize: vertical;
    }

    .category-image-card {
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(255, 247, 237, 0.65) 0%, #fff 100%);
        border: 1px solid rgba(210, 100, 38, 0.18);
        padding: 22px 24px 26px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .category-image-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .category-image-card__title {
        font-size: 18px;
        font-weight: 600;
        color: var(--dark);
        margin: 0;
    }

    .category-image-card__body {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .category-image-card__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 14px;
        padding: 32px 18px;
        border-radius: 16px;
        border: 1px dashed rgba(210, 100, 38, 0.25);
        background: rgba(255, 247, 237, 0.65);
    }

    .category-image-card__icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        background: rgba(210, 100, 38, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(210, 100, 38, 0.95);
        font-size: 26px;
    }

    .category-image-card__empty p {
        margin: 0;
        font-size: 15px;
        color: rgba(0, 0, 0, 0.6);
    }

    .category-image-card__preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
        padding: 18px;
        border-radius: 16px;
        border: 1px dashed rgba(210, 100, 38, 0.25);
        background: rgba(255, 247, 237, 0.65);
    }

    .category-image-card__preview-image {
        position: relative;
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .category-image-card__preview img {
        max-width: 100%;
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        display: block;
    }

    .category-image-card__remove {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        background: rgba(0, 0, 0, 0.55);
        color: var(--white);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
    }

    .category-image-card__remove:hover {
        background: rgba(210, 64, 38, 0.9);
        transform: translateY(-1px);
    }

    .category-image-card__preview-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .category-image-card__field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .is-hidden {
        display: none !important;
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

    .btn-soft {
        background: linear-gradient(135deg, rgba(210, 100, 38, 0.18), rgba(247, 199, 106, 0.4));
        color: var(--secondary);
        border: 1px solid rgba(210, 100, 38, 0.22);
    }

    .btn-soft:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 26px rgba(210, 100, 38, 0.18);
    }

    .btn-light {
        background: rgba(0, 0, 0, 0.05);
        color: var(--dark);
    }

    .btn-outline-danger {
        background: transparent;
        border: 1px solid rgba(210, 64, 38, 0.6);
        color: rgba(210, 64, 38, 0.95);
    }

    .btn-outline-danger:hover {
        background: rgba(210, 64, 38, 0.1);
    }

    .actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        width: 100%;
        align-items: stretch;
    }

    .metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 16px;
    }

    .metric {
        background: rgba(255, 247, 237, 0.8);
        border-radius: 14px;
        padding: 14px;
        text-align: center;
        border: 1px solid rgba(210, 100, 38, 0.15);
    }

    .metric__value {
        font-size: 24px;
        font-weight: 700;
        color: var(--secondary);
    }

    .metric__label {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.6);
    }

    .card--activation {
        background: rgba(255, 247, 237, 0.7);
        border: 1px solid rgba(210, 100, 38, 0.12);
    }

    .activation-toggle {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .switch {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        user-select: none;
        font-weight: 600;
        color: var(--dark);
        position: relative;
    }

    .switch input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .switch__slider {
        width: 46px;
        height: 24px;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.2);
        position: relative;
        transition: background 0.25s ease;
    }

    .switch__slider::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--white);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
        transition: transform 0.25s ease;
    }

    .switch input:checked + .switch__slider {
        background: #3c80ff;
    }

    .switch input:checked + .switch__slider::after {
        transform: translateX(22px);
    }

    .switch__label {
        font-size: 14px;
        color: var(--dark);
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .category-edit {
            padding: 26px 20px;
        }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="category-edit" aria-labelledby="category-edit-title">
        <div class="category-edit__header">
            <h1 class="category-edit__title" id="category-edit-title">Sửa Danh Mục</h1>
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                <span>/</span>
                <a href="<?php echo BASE_URL; ?>/admin/categories/index.php">Quản Lý Danh Mục</a>
                <span>/</span>
                <span>Sửa</span>
            </nav>
        </div>

        <?php if ($deleteSuccessMessage): ?>
            <div class="notice notice--success" role="status">
                <i class="fas fa-check-circle"></i>
                <div>
                    <?php echo htmlspecialchars($deleteSuccessMessage); ?>
                    <div style="margin-top:6px;">
                        <a class="btn btn-light" href="<?php echo BASE_URL; ?>/admin/categories/index.php"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

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
                    <strong>Không thể lưu thay đổi:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($deleteSuccessMessage === ''): ?>
        <form method="post" class="category-edit__form" novalidate>
            <div class="category-edit__grid">
                <section class="category-edit__main" aria-label="Thông tin danh mục">
                    <div class="card">
                        <h2 class="card__title">Thông Tin Danh Mục</h2>
                        <div class="form-field">
                            <label for="category_name">Tên Danh Mục <span>*</span></label>
                            <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($formValues['category_name']); ?>" placeholder="Ví dụ: Yến Sào Tinh Chế" required>
                        </div>
                        <div class="form-field">
                            <label for="slug">Slug (URL)</label>
                            <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($formValues['slug']); ?>" placeholder="yen-sao-tinh-che">
                            <small>Để trống sẽ tự động tạo từ tên danh mục.</small>
                        </div>
                        <div class="form-field">
                            <label for="description">Mô Tả</label>
                            <textarea id="description" name="description" placeholder="Nhập mô tả ngắn gọn về danh mục."><?php echo htmlspecialchars($formValues['description']); ?></textarea>
                        </div>
                        <div class="card__subtitle">Trạng Thái</div>
                        <div class="activation-toggle">
                            <label class="switch">
                                <input type="checkbox" name="is_active" <?php echo (int) $formValues['is_active'] === 1 ? 'checked' : ''; ?>>
                                <span class="switch__slider"></span>
                                <span class="switch__label">Kích hoạt danh mục</span>
                            </label>
                        </div>
                        <div class="card__section card__section--image">
                            <div class="category-image-card">
                                <div class="category-image-card__header">
                                    <h2 class="category-image-card__title">Hình Ảnh Danh Mục</h2>
                                    <a class="btn btn-soft" href="<?php echo BASE_URL; ?>/admin/categories/images.php">
                                        <i class="fas fa-images"></i> Quản Lý Ảnh
                                    </a>
                                </div>
                                <div class="category-image-card__body">
                                    <div class="category-image-card__empty <?php echo $formValues['image'] ? 'is-hidden' : ''; ?>" id="image-empty-state">
                                        <div class="category-image-card__icon">
                                            <i class="fas fa-image"></i>
                                        </div>
                                        <p>Chưa có hình ảnh</p>
                                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($imagePickerUrl); ?>">
                                            <i class="fas fa-plus"></i> Thêm Ảnh
                                        </a>
                                    </div>
                                    <div class="category-image-card__preview <?php echo $formValues['image'] ? '' : 'is-hidden'; ?>" id="image-preview-wrapper" data-placeholder="<?php echo htmlspecialchars($imagePlaceholder); ?>">
                                        <div class="category-image-card__preview-image">
                                            <img src="<?php echo htmlspecialchars($formValues['image'] ?: $imagePlaceholder); ?>" alt="Hình ảnh danh mục" id="image-preview-img">
                                            <button type="button" class="category-image-card__remove" id="image-remove-btn" aria-label="Xóa ảnh" <?php if (!$formValues['image']) echo 'disabled aria-hidden="true"'; ?>>
                                                <i class="fas fa-times"></i>
                                                <span class="sr-only">Xóa ảnh</span>
                                            </button>
                                        </div>
                                        <div class="category-image-card__preview-actions">
                                            <a class="btn btn-primary" href="<?php echo htmlspecialchars($imagePickerUrl); ?>">
                                                <i class="fas fa-plus"></i> Thêm Ảnh
                                            </a>
                                        </div>
                                    </div>
                                    <input type="hidden" id="image" name="image" value="<?php echo htmlspecialchars($formValues['image']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="category-edit__side" aria-label="Tùy chọn và thống kê">
                    <div class="card">
                        <h2 class="card__title">Thống Kê</h2>
                        <div class="metrics">
                            <div class="metric">
                                <div class="metric__value"><?php echo number_format($productCount); ?></div>
                                <div class="metric__label">Sản phẩm</div>
                            </div>
                        </div>
                        <div class="form-field">
                            <label>Ngày tạo</label>
                            <div><?php echo htmlspecialchars($createdAtDisplay); ?></div>
                        </div>
                    </div>

                    <div class="category-edit__actions">
                        <div class="actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cập Nhật</button>
                            <a class="btn btn-light" href="<?php echo BASE_URL; ?>/admin/categories/index.php"><i class="fas fa-arrow-left"></i> Quay Lại</a>
                            <button type="button" class="btn btn-outline-danger" id="delete-category-btn"><i class="fas fa-trash"></i> Xóa Danh Mục</button>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('category_name');
        const slugInput = document.getElementById('slug');
        const imageInput = document.getElementById('image');
        const imagePreviewImg = document.getElementById('image-preview-img');
        const imagePreviewWrapper = document.getElementById('image-preview-wrapper');
        const emptyState = document.getElementById('image-empty-state');
        const imagePlaceholder = imagePreviewWrapper ? imagePreviewWrapper.dataset.placeholder : '';
        const removeImageButton = document.getElementById('image-remove-btn');
        let slugManuallyEdited = false;
        const deleteButton = document.getElementById('delete-category-btn');
        const deleteForm = document.getElementById('delete-category-form');
        const deleteReason = document.getElementById('delete-category-id');

        function removeVietnameseDiacritics(text) {
            return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[Đđ]/g, 'd');
        }

        function toSlug(text) {
            return removeVietnameseDiacritics(text)
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }

        if (nameInput && slugInput) {
            const initialName = nameInput.value.trim();
            const initialSlug = slugInput.value.trim();
            const autoSlugFromInitialName = toSlug(initialName);
            slugManuallyEdited = initialSlug.length > 0 && initialSlug !== autoSlugFromInitialName;

            if (initialName === '') {
                slugInput.value = '';
                slugManuallyEdited = false;
            }

            nameInput.addEventListener('input', function () {
                const currentName = nameInput.value;
                if (currentName.trim() === '') {
                    slugManuallyEdited = false;
                    slugInput.value = '';
                    return;
                }
                if (slugManuallyEdited) {
                    return;
                }
                slugInput.value = toSlug(currentName);
            });

            slugInput.addEventListener('input', function () {
                const sanitized = toSlug(slugInput.value);
                if (slugInput.value !== sanitized) {
                    slugInput.value = sanitized;
                }
                const autoSlug = toSlug(nameInput.value);
                const trimmed = slugInput.value.trim();
                if (trimmed === '') {
                    slugManuallyEdited = false;
                } else {
                    slugManuallyEdited = trimmed !== autoSlug;
                }
            });
        }

        function updatePreview(value) {
            if (!imagePreviewImg || !imagePreviewWrapper || !emptyState) {
                return;
            }
            const trimmedValue = (value || '').trim();
            if (!trimmedValue) {
                imagePreviewImg.src = imagePlaceholder;
                imagePreviewWrapper.classList.add('is-hidden');
                emptyState.classList.remove('is-hidden');
                if (removeImageButton) {
                    removeImageButton.disabled = true;
                    removeImageButton.setAttribute('aria-hidden', 'true');
                }
                return;
            }
            imagePreviewImg.src = trimmedValue;
            imagePreviewWrapper.classList.remove('is-hidden');
            emptyState.classList.add('is-hidden');
            if (removeImageButton) {
                removeImageButton.disabled = false;
                removeImageButton.removeAttribute('aria-hidden');
            }
        }

        if (imageInput) {
            imageInput.addEventListener('input', function () {
                updatePreview(imageInput.value.trim());
            });
            updatePreview(imageInput.value.trim());
        }

        if (removeImageButton && imageInput) {
            removeImageButton.addEventListener('click', function () {
                imageInput.value = '';
                updatePreview('');
            });
        }

        if (deleteButton) {
            deleteButton.addEventListener('click', function (event) {
                event.preventDefault();
                const confirmed = window.confirm('Bạn có chắc chắn muốn xóa danh mục này?\nHành động này không thể hoàn tác.');
                if (!confirmed) {
                    return;
                }
                const form = document.createElement('form');
                form.method = 'post';
                form.className = 'sr-only';
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            });
        }
    });
</script>
