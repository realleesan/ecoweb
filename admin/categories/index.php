<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

$errors = [];
$successMessage = '';

$categories = [];
$totalCategories = 0;

try {
    $pdo = getPDO();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
        if ($action === 'delete') {
            $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
            if ($categoryId <= 0) {
                $errors[] = 'Danh mục không hợp lệ.';
            } else {
                try {
                    $categoryStmt = $pdo->prepare('SELECT category_name FROM categories WHERE category_id = :id');
                    $categoryStmt->execute([':id' => $categoryId]);
                    $category = $categoryStmt->fetch();

                    if (!$category) {
                        $errors[] = 'Danh mục không tồn tại hoặc đã được xóa.';
                    } else {
                        $productCountStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
                        $productCountStmt->execute([':id' => $categoryId]);
                        $productCount = (int) $productCountStmt->fetchColumn();

                        if ($productCount > 0) {
                            $errors[] = 'Không thể xóa danh mục đang được sử dụng bởi ' . $productCount . ' sản phẩm.';
                        } else {
                            $deleteStmt = $pdo->prepare('DELETE FROM categories WHERE category_id = :id');
                            $deleteStmt->execute([':id' => $categoryId]);
                            $successMessage = 'Đã xóa danh mục "' . $category['category_name'] . '".';
                        }
                    }
                } catch (Throwable $deleteException) {
                    $errors[] = 'Không thể xóa danh mục. Vui lòng thử lại.';
                }
            }
        }
    }

    $conditions = [];
    $params = [];

    if ($keyword !== '') {
        $conditions[] = '(c.category_name LIKE :keyword OR c.slug LIKE :keyword OR c.description LIKE :keyword)';
        $params[':keyword'] = '%' . $keyword . '%';
    }

    $innerWhere = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $sql = "
        SELECT
            c.category_id,
            c.category_name,
            c.slug,
            c.description,
            c.image,
            c.is_active,
            COALESCE(p.product_count, 0) AS product_count
        FROM categories c
        LEFT JOIN (
            SELECT category_id, COUNT(*) AS product_count
            FROM products
            GROUP BY category_id
        ) p ON p.category_id = c.category_id
        $innerWhere
    ";

    $outerConditions = [];
    if (in_array($statusFilter, ['active', 'inactive'], true)) {
        $outerConditions[] = 'c.is_active = :status';
        $params[':status'] = $statusFilter === 'active' ? 1 : 0;
    }

    if ($outerConditions) {
        $sql .= ' WHERE ' . implode(' AND ', $outerConditions);
    }

    $sql .= ' ORDER BY c.category_name ASC';

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $categories = $stmt->fetchAll();
    $totalCategories = count($categories);
} catch (Throwable $e) {
    $errors[] = 'Không thể tải danh sách danh mục. Vui lòng thử lại sau.';
    $categories = [];
    $totalCategories = 0;
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

    .admin-content {
        background-color: var(--white);
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    .categories-page__header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .categories-page__title-group h1 {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 6px;
    }

    .categories-page__title-group p {
        color: rgba(0, 0, 0, 0.55);
        font-size: 14px;
    }

    .breadcrumb {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 14px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }

    .breadcrumb a {
        color: var(--secondary);
        text-decoration: none;
        font-weight: 600;
    }

    .breadcrumb span {
        color: rgba(0, 0, 0, 0.55);
    }

    .btn-add-category {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--secondary), #f7c76a);
        color: var(--white);
        font-weight: 600;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 12px 25px rgba(210, 100, 38, 0.25);
    }

    .btn-add-category:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 30px rgba(210, 100, 38, 0.35);
    }

    .categories-filter {
        background-color: rgba(255, 247, 237, 0.9);
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid rgba(210, 100, 38, 0.15);
        display: grid;
        grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) auto;
        gap: 16px;
        align-items: end;
    }

    .categories-filter__field label {
        font-weight: 600;
        font-size: 13px;
        color: var(--dark);
        margin-bottom: 6px;
        display: block;
    }

    .categories-filter__field input,
    .categories-filter__field select {
        width: 100%;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        padding: 10px 14px;
        font-size: 14px;
        transition: border 0.2s ease, box-shadow 0.2s ease;
        background-color: var(--white);
    }

    .categories-filter__field input:focus,
    .categories-filter__field select:focus {
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.15);
        outline: none;
    }

    .categories-filter__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .btn-filter-submit {
        padding: 10px 18px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: var(--secondary);
        color: var(--white);
    }

    .btn-filter-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(210, 100, 38, 0.28);
    }

    .category-table-wrapper {
        border-radius: 14px;
        border: 1px solid #f0ebe3;
        overflow: hidden;
        background: var(--white);
    }

    .category-table-scroll {
        overflow-x: auto;
        overflow-y: hidden;
    }

    .category-table {
        width: 100%;
    }

    .category-row {
        display: grid;
        grid-template-columns: 70px minmax(150px, 1.8fr) minmax(120px, 1.1fr) minmax(60px, 0.6fr) minmax(90px, 0.7fr) minmax(110px, 0.8fr);
        gap: 10px;
        align-items: center;
        padding: 18px 20px;
        background-color: var(--white);
        border-bottom: 1px solid #f3f1ed;
    }

    .category-row:last-child {
        border-bottom: none;
    }

    .category-row--head {
        background-color: rgba(255, 247, 237, 0.75);
        font-weight: 600;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.75px;
        position: relative;
    }

    .category-row--head::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255, 247, 237, 0.75);
        z-index: 0;
    }

    .category-col {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .category-row--head .category-col {
        justify-content: flex-start;
        position: relative;
        z-index: 1;
    }

    .category-col--thumb {
        justify-content: center;
    }

    .category-row--head .category-col--thumb {
        justify-content: center;
    }

    .category-col--name,
    .category-col--slug {
        justify-content: flex-start;
    }

    .category-col--products,
    .category-col--status {
        justify-content: center;
    }

    .category-row--head .category-col--products,
    .category-row--head .category-col--status {
        justify-content: center;
    }

    .category-row--head .category-col--actions {
        justify-content: center;
        align-items: center;
    }

    .category-col--actions {
        justify-content: center;
        align-items: center;
        flex-direction: column;
        gap: 12px;
    }

    .category-thumb {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(210, 100, 38, 0.1);
        color: var(--secondary);
        font-size: 18px;
        overflow: hidden;
    }

    .category-thumb--image {
        background: rgba(0, 0, 0, 0.05);
        color: transparent;
        padding: 0;
    }

    .category-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .category-name {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .category-name strong {
        font-weight: 700;
        color: var(--dark);
    }

    .category-name span {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.55);
    }

    .badge-counter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        border-radius: 999px;
        font-weight: 600;
        color: var(--white);
    }

    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }

    .status-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 26px;
        border-radius: 999px;
        background: rgba(60, 96, 60, 0.15);
    }

    .status-toggle__icon {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(60, 96, 60, 0.9);
        color: var(--white);
        font-size: 10px;
    }

    .status-toggle--inactive {
        background: rgba(0, 0, 0, 0.08);
    }

    .status-toggle--inactive .status-toggle__icon {
        background: rgba(120, 120, 120, 0.65);
        color: var(--white);
    }

    .category-actions {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        width: 100%;
    }

    .category-actions a,
    .category-actions button {
        width: 36px;
        height: 36px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--dark);
        background: rgba(0, 0, 0, 0.06);
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        margin: 0 auto;
        border: none;
        cursor: pointer;
    }

    .category-actions a:hover,
    .category-actions button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 14px rgba(0, 0, 0, 0.12);
    }

    @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
        .category-row {
            grid-template-columns: 70px 1.5fr 1.1fr 0.7fr 0.6fr 0.7fr;
            padding: 16px;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .category-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-template-areas:
                'thumb name'
                'slug slug'
                'products status'
                'actions actions';
            gap: 12px 18px;
        }

        .category-row--head {
            display: none;
        }

        .category-col--thumb { grid-area: thumb; }
        .category-col--name { grid-area: name; }
        .category-col--slug { grid-area: slug; }
        .category-col--products { grid-area: products; }
        .category-col--status { grid-area: status; display: flex; align-items: center; justify-content: flex-start; }
        .category-col--actions { grid-area: actions; justify-self: stretch; }
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .categories-page__header {
            align-items: flex-start;
        }

        .btn-add-category {
            width: 100%;
            justify-content: center;
        }

        .categories-filter {
            grid-template-columns: 1fr;
        }

        .categories-filter__actions {
            justify-content: stretch;
        }

        .btn-filter-submit {
            width: 100%;
        }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content categories-page">
        <div class="categories-page__header">
            <div class="categories-page__title-group">
                <h1>Quản Lý Danh Mục</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <span>Quản Lý Danh Mục</span>
                </nav>
                <p>Tổng cộng: <?php echo $totalCategories; ?> danh mục</p>
            </div>
            <a class="btn-add-category" href="<?php echo BASE_URL; ?>/admin/categories/add.php">
                <i class="fas fa-plus"></i>
                <span>Thêm Danh Mục Mới</span>
            </a>
        </div>

        <?php if ($successMessage): ?>
            <div class="notice notice--success" role="status" style="display:flex;align-items:flex-start;gap:12px;border-radius:12px;padding:14px 18px;background:rgba(63,142,63,0.12);color:#2a6a2a;border:1px solid rgba(63,142,63,0.35);">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="notice notice--error" role="alert" style="display:flex;align-items:flex-start;gap:12px;border-radius:12px;padding:14px 18px;background:rgba(210,64,38,0.12);color:#a52f1c;border:1px solid rgba(210,64,38,0.35);">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Không thể thực hiện thao tác:</strong>
                    <ul style="margin:8px 0 0;padding-left:20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form class="categories-filter" method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="categories-filter__field">
                <label for="keyword">Tìm Kiếm</label>
                <input type="text" id="keyword" name="keyword" placeholder="Tên danh mục, slug..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="categories-filter__field">
                <label for="status">Trạng Thái</label>
                <select id="status" name="status">
                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
                </select>
            </div>
            <div class="categories-filter__actions">
                <button type="submit" class="btn-filter-submit"><i class="fas fa-filter"></i> Lọc</button>
            </div>
        </form>

        <div class="category-table-wrapper">
            <div class="category-table-scroll">
                <div class="category-table" role="table" aria-label="Danh sách danh mục">
                    <div class="category-row category-row--head" role="row">
                        <div class="category-col category-col--thumb" role="columnheader">Ảnh</div>
                        <div class="category-col category-col--name" role="columnheader">Danh mục</div>
                        <div class="category-col category-col--slug" role="columnheader">Slug</div>
                        <div class="category-col category-col--products" role="columnheader">Sản phẩm</div>
                        <div class="category-col category-col--status" role="columnheader">Trạng thái</div>
                        <div class="category-col category-col--actions" role="columnheader">Thao tác</div>
                    </div>

                    <?php foreach ($categories as $category):
                        $isActive = (int) ($category['is_active'] ?? 0) === 1;
                        $statusClass = $isActive ? 'status-dot--active' : 'status-dot--inactive';
                        $statusLabel = $isActive ? 'Hoạt động' : 'Không hoạt động';
                        $categoryImage = trim((string) ($category['image'] ?? ''));
                        $hasImage = $categoryImage !== '';
                        $thumbClass = 'category-thumb' . ($hasImage ? ' category-thumb--image' : '');
                        $categorySlug = trim((string) ($category['slug'] ?? ''));
                        $viewUrl = BASE_URL . '/public/products.php' . ($categorySlug !== '' ? '?category=' . rawurlencode($categorySlug) : '');
                    ?>
                        <div class="category-row" role="row">
                            <div class="category-col category-col--thumb" role="cell">
                                <span class="<?php echo $thumbClass; ?>">
                                    <?php if ($hasImage): ?>
                                        <img src="<?php echo htmlspecialchars($categoryImage); ?>" alt="<?php echo htmlspecialchars($category['category_name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-folder"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="category-col category-col--name" role="cell">
                                <strong class="category-name__title"><?php echo htmlspecialchars($category['category_name']); ?></strong>
                            </div>
                            <div class="category-col category-col--slug" role="cell">
                                <span><?php echo htmlspecialchars($category['slug']); ?></span>
                            </div>
                            <div class="category-col category-col--products" role="cell">
                                <?php echo (int) $category['product_count']; ?>
                            </div>
                            <div class="category-col category-col--status" role="cell">
                                <span class="status-toggle <?php echo $isActive ? '' : 'status-toggle--inactive'; ?>" aria-hidden="true">
                                    <span class="status-toggle__icon">
                                        <i class="fas <?php echo $isActive ? 'fa-check' : 'fa-minus'; ?>"></i>
                                    </span>
                                </span>
                                <span class="sr-only"><?php echo $statusLabel; ?></span>
                            </div>
                            <div class="category-col category-col--actions" role="cell">
                                <div class="category-actions">
                                    <a class="action-view" href="<?php echo htmlspecialchars($viewUrl); ?>" title="Xem trên website" target="_blank" rel="noopener"><i class="fas fa-eye"></i></a>
                                    <a class="action-edit" href="<?php echo BASE_URL; ?>/admin/categories/edit.php?id=<?php echo (int) $category['category_id']; ?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="action-delete" data-category-id="<?php echo (int) $category['category_id']; ?>" data-category-name="<?php echo htmlspecialchars($category['category_name']); ?>" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<form method="post" id="category-delete-form" class="sr-only">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="category_id" id="delete-category-id" value="">
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteForm = document.getElementById('category-delete-form');
        const deleteInput = document.getElementById('delete-category-id');
        const deleteButtons = document.querySelectorAll('.action-delete');

        deleteButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const categoryId = button.dataset.categoryId;
                const categoryName = button.dataset.categoryName || '';
                const confirmed = window.confirm('Bạn có chắc chắn muốn xóa danh mục "' + categoryName + '"?\nHành động này không thể hoàn tác.');
                if (!confirmed) {
                    return;
                }
                if (!deleteForm || !deleteInput) {
                    return;
                }
                deleteInput.value = categoryId;
                deleteForm.submit();
            });
        });
    });
</script>
