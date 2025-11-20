<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


$name = '';
$code = '';
$category_ids = [];
$price = '';
$short_description = '';
$full_description = '';
$stock = '';
$is_bestseller = 0;
$selected_tags = [];
$available_tags = [];
$errors = [];


try {
    $pdo = getPDO();
    $catsStmt = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name ASC');
    $categories = $catsStmt->fetchAll();


    $available_tags = getAvailableProductTags($pdo);
} catch (Exception $e) {
    $categories = [];
    $available_tags = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $category_ids = isset($_POST['category_ids']) && is_array($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : [];
    $price = trim($_POST['price'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $full_description = trim($_POST['full_description'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $selected_tags = isset($_POST['tags']) && is_array($_POST['tags']) ? array_map('trim', $_POST['tags']) : [];


    if ($name === '') $errors[] = 'Vui lòng nhập tên sản phẩm';
    if (empty($category_ids)) $errors[] = 'Vui lòng chọn ít nhất một danh mục';
    if ($price === '' || !preg_match('/^\d+$/', $price)) $errors[] = 'Giá bán không hợp lệ';
    if ($stock === '' || !ctype_digit($stock)) $errors[] = 'Số lượng tồn kho không hợp lệ';


    if (empty($errors)) {
        try {
            $pdo = getPDO();
            $check = $pdo->prepare('SELECT product_id FROM products WHERE code = :code');
            $check->execute([':code' => $code]);
            $exists = $check->fetch();
            if ($exists) {
                $errors[] = 'SKU đã tồn tại';
            } else {
                $ins = $pdo->prepare('INSERT INTO products (category_id, code, name, price, short_description, full_description, stock, rating, reviews_count, is_bestseller) VALUES (:category_id, :code, :name, :price, :short_description, :full_description, :stock, 0, 0, :is_bestseller)');
                $ins->execute([
                    ':category_id' => !empty($category_ids) ? $category_ids[0] : 0,
                    ':code' => $code,
                    ':name' => $name,
                    ':price' => (int)$price,
                    ':short_description' => $short_description,
                    ':full_description' => $full_description,
                    ':stock' => (int)$stock,
                    ':is_bestseller' => $is_bestseller,
                ]);
                $newId = (int)$pdo->lastInsertId();


                $tagValues = array_values(array_unique(array_filter($selected_tags, static fn($tag) => $tag !== '')));
                if (!empty($tagValues)) {
                    $tagIns = $pdo->prepare('INSERT INTO product_tags (product_id, tag) VALUES (:pid, :tag)');
                    foreach ($tagValues as $t) {
                        if ($t !== '') {
                            $tagIns->execute([':pid' => $newId, ':tag' => $t]);
                        }
                    }
                }


                header('Location: ' . BASE_URL . '/admin/products/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Không thể lưu sản phẩm';
        }
    }
}


include __DIR__ . '/../includes/header.php';
?>


<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); overflow: hidden; }
    .admin-page-header { padding: 18px <?php echo CONTAINER_PADDING; ?>; border-bottom: 2px solid #f1f1f1; background-color: var(--white); }
    .admin-page-header h1 { font-size: 22px; color: var(--primary); font-weight: 700; }
    .breadcrumb { margin-top: 6px; color: #777; font-size: 13px; }
    .breadcrumb a { color: var(--secondary); font-weight: 600; text-decoration: none; }
    .breadcrumb a:hover { color: var(--primary); text-decoration: underline; }
    .breadcrumb span { color: #777; font-weight: 500; }


    .page-body { display: grid; grid-template-columns: 2fr 1fr; gap: <?php echo GRID_GAP_SMALL; ?>; padding: 20px <?php echo CONTAINER_PADDING; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .page-body { grid-template-columns: 1fr; } }


    .card { background: var(--white); border: 1px solid #eee; border-radius: 12px; padding: 16px; }
    .card h3 { font-size: 16px; color: var(--primary); font-weight: 700; margin-bottom: 12px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
    .form-field { display: flex; flex-direction: column; gap: 6px; }
    .form-field label { font-size: 12px; color: #666; }
    .form-field input[type="text"], .form-field input[type="number"], .form-field textarea, .form-field select { padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 8px; outline: none; background: #fff; }
    .form-field textarea { min-height: 120px; }
    .checkbox-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 16px; align-items: start; }
    .checkbox-label { display: flex; align-items: flex-start; gap: 8px; line-height: 1.4; }
    .checkbox-label input { margin-top: 2px; }
    .actions { display: flex; gap: 10px; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 8px; text-decoration: none; cursor: pointer; border: none; }
    .btn-primary { background: linear-gradient(135deg, var(--secondary), var(--primary)); color: var(--white); }
    .btn-outline { background: #fff; color: var(--dark); border: 1px solid #e0e0e0; }
    .toggle { display: flex; align-items: center; gap: 10px; }
    .error { background: #fff2f0; color: #cf1322; border: 1px solid #f0c7c7; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; }
    @media (max-width: <?php echo BREAKPOINT_SM; ?>) { .checkbox-columns { grid-template-columns: 1fr; } }
</style>


<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Thêm Sản Phẩm Mới</h1>
            <div class="breadcrumb"><a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a> / <a href="<?php echo BASE_URL; ?>/admin/products/index.php">Quản lý sản phẩm</a> / <span>Thêm mới</span></div>
        </div>
        <div class="page-body">
            <div class="left">
                <form method="post" action="">
                    <?php if (!empty($errors)): ?>
                        <div class="error">
                            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                        </div>
                    <?php endif; ?>


                    <div class="card">
                        <h3>Thông Tin Cơ Bản</h3>
                        <div class="form-field">
                            <label>Tên Sản Phẩm *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" />
                        </div>
                        <div class="grid-2">
                            <div class="form-field">
                                <label>SKU *</label>
                                <input type="text" name="code" value="<?php echo htmlspecialchars($code); ?>" />
                            </div>
                            <div class="form-field">
                                <label>Danh Mục *</label>
                                <div class="checkbox-columns">
                                    <?php foreach ($categories as $c): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="category_ids[]" value="<?php echo (int)$c['category_id']; ?>" <?php echo in_array((int)$c['category_id'], $category_ids) ? 'checked' : ''; ?> />
                                            <?php echo htmlspecialchars($c['category_name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-field">
                            <label>Mô Tả Ngắn</label>
                            <textarea name="short_description"><?php echo htmlspecialchars($short_description); ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>Mô Tả Chi Tiết</label>
                            <textarea name="full_description"><?php echo htmlspecialchars($full_description); ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>Tags</label>
                            <?php if (empty($available_tags)): ?>
                                <p class="muted">Chưa có tag nào. <a href="<?php echo BASE_URL; ?>/admin/products/tags.php">Quản lý tags</a>.</p>
                            <?php else: ?>
                                <div class="checkbox-columns">
                                    <?php foreach ($available_tags as $t): ?>
                                        <label class="checkbox-label"><input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($t); ?>" <?php echo in_array($t, $selected_tags) ? 'checked' : ''; ?> /> <span><?php echo htmlspecialchars($t); ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>


                    <div class="card" style="margin-top: 16px;">
                        <h3>Giá & Tồn Kho</h3>
                        <div class="grid-3">
                            <div class="form-field">
                                <label>Giá Bán *</label>
                                <input type="number" name="price" step="1" min="0" required inputmode="numeric" value="<?php echo htmlspecialchars($price); ?>" />
                            </div>
                            <div class="form-field">
                                <label>Số Lượng Tồn Kho *</label>
                                <input type="number" name="stock" min="0" value="<?php echo htmlspecialchars($stock); ?>" />
                            </div>
                            <div class="form-field">
                                <label>Sản phẩm nổi bật</label>
                                <div class="toggle"><input type="checkbox" name="is_bestseller" <?php echo $is_bestseller ? 'checked' : ''; ?> /> <span>Kích hoạt nổi bật</span></div>
                            </div>
                        </div>
                    </div>


                    <div class="actions" style="margin-top: 16px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Sản Phẩm</button>
                        <a href="<?php echo BASE_URL; ?>/admin/products/index.php" class="btn btn-outline"><i class="fas fa-times"></i> Hủy</a>
                    </div>
                </form>
            </div>
            <div class="right">
                <div class="card">
                    <h3>Xuất Bản</h3>
                    <div class="toggle"><input type="checkbox" checked disabled /> <span>Kích hoạt sản phẩm</span></div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>

