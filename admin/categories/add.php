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
        // Ignore inability to alter table, continue execution.
    } finally {
        $checked = true;
    }
}

$errors = [];
$successMessage = '';
$formValues = [
    'category_name' => '',
    'slug' => '',
    'description' => '',
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $categoryName = isset($_POST['category_name']) ? trim((string) $_POST['category_name']) : '';
        $slugInput = isset($_POST['slug']) ? trim((string) $_POST['slug']) : '';
        $description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $formValues = [
            'category_name' => $categoryName,
            'slug' => $slugInput,
            'description' => $description,
            'is_active' => $isActive,
        ];

        if ($categoryName === '') {
            $errors[] = 'Vui lòng nhập tên danh mục.';
        }

        $slug = $slugInput === '' ? slugify($categoryName) : slugify($slugInput);
        if ($slug === '') {
            $slug = 'danh-muc-' . substr((string) time(), -6);
        }

        if (!$errors) {
            $slugCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = :slug');
            $slugCheck->execute([':slug' => $slug]);
            if ($slugCheck->fetchColumn() > 0) {
                $errors[] = 'Slug đã tồn tại. Vui lòng chọn slug khác.';
            }
        }

        if (!$errors) {
            try {
                $insertStmt = $pdo->prepare('INSERT INTO categories (category_name, slug, description, image, is_active) VALUES (:name, :slug, :description, :image, :is_active)');
                $insertStmt->execute([
                    ':name' => $categoryName,
                    ':slug' => $slug,
                    ':description' => $description !== '' ? $description : null,
                    ':image' => null,
                    ':is_active' => $isActive,
                ]);

                $successMessage = 'Tạo danh mục mới thành công.';
                $formValues = [
                    'category_name' => '',
                    'slug' => '',
                    'description' => '',
                    'is_active' => 1,
                ];
            } catch (Throwable $e) {
                $errors[] = 'Không thể tạo danh mục mới. Vui lòng thử lại.';
            }
        }
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
        display: flex;
        flex-direction: column;
        gap: 24px;
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

    .card__body {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .card__section {
        display: flex;
        flex-direction: column;
        gap: 18px;
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

    .form-field--toggle {
        flex-direction: row;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .form-field--toggle .activation-toggle {
        display: flex;
        align-items: center;
        flex-direction: row;
        gap: 12px;
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

    .actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-self: center;
        width: min(320px, 100%);
        margin: 16px auto 0;
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

    <main class="category-edit" aria-labelledby="category-create-title">
        <div class="category-edit__header">
            <h1 class="category-edit__title" id="category-create-title">Thêm Danh Mục</h1>
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                <span>/</span>
                <a href="<?php echo BASE_URL; ?>/admin/categories/index.php">Quản Lý Danh Mục</a>
                <span>/</span>
                <span>Thêm mới</span>
            </nav>
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
                    <strong>Không thể tạo danh mục:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="category-edit__form" novalidate>
            <div class="category-edit__grid">
                <section class="category-edit__main" aria-label="Thông tin danh mục">
                    <div class="card card--main">
                        <h2 class="card__title">Thông Tin Danh Mục</h2>
                        <div class="card__body">
                            <div class="card__section">
                                <div class="form-field">
                                    <label for="category_name">Tên Danh Mục <span>*</span></label>
                                    <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($formValues['category_name']); ?>" placeholder="Ví dụ: Cây Trầu Bà Đế Vương" required>
                                </div>
                                <div class="form-field">
                                    <label for="slug">Slug (URL)</label>
                                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($formValues['slug']); ?>" placeholder="cay-trau-ba-de-vuong">
                                    <small>Để trống sẽ tự động tạo từ tên danh mục.</small>
                                </div>
                                <div class="form-field">
                                    <label for="description">Mô Tả</label>
                                    <textarea id="description" name="description" placeholder="Nhập mô tả ngắn gọn về danh mục."><?php echo htmlspecialchars($formValues['description']); ?></textarea>
                                </div>
                                <div class="form-field form-field--toggle">
                                    <label for="is_active_toggle">Trạng Thái</label>
                                    <div class="activation-toggle">
                                        <label class="switch" id="is_active_toggle">
                                            <input type="checkbox" name="is_active" <?php echo (int) $formValues['is_active'] === 1 ? 'checked' : ''; ?>>
                                            <span class="switch__slider"></span>
                                            <span class="switch__label">Kích hoạt danh mục</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo Danh Mục</button>
                        <a class="btn btn-light" href="<?php echo BASE_URL; ?>/admin/categories/index.php"><i class="fas fa-arrow-left"></i> Quay Lại</a>
                    </div>
                </section>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('category_name');
        const slugInput = document.getElementById('slug');
        let slugManuallyEdited = false;

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
    });
</script>
