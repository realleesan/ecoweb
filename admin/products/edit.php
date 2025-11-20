<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}


function adminSlugify(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }


    if (function_exists('transliterator_transliterate')) {
        $converted = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);
        if ($converted !== false && $converted !== null) {
            $text = $converted;
        }
    } elseif (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false && $converted !== null) {
            $text = $converted;
        }
    }


    $text = strtr($text, ['đ' => 'd', 'Đ' => 'd']);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
    $text = preg_replace('/[\s-]+/', '-', $text) ?? '';


    return trim($text, '-');
}


$errors = [];
$successMessage = '';
$product = null;
$categories = [];
$availableTags = [];
$selectedTags = [];


try {
    $pdo = getPDO();

    // Ensure additional columns exist
    try {
        $checkOrigin = $pdo->query("SHOW COLUMNS FROM products LIKE 'origin'");
        if ($checkOrigin->rowCount() === 0) {
            $pdo->exec('ALTER TABLE products ADD COLUMN origin VARCHAR(255) DEFAULT NULL');
        }
    } catch (Exception $e) {}

    try {
        $checkCare = $pdo->query("SHOW COLUMNS FROM products LIKE 'care_instructions'");
        if ($checkCare->rowCount() === 0) {
            $pdo->exec('ALTER TABLE products ADD COLUMN care_instructions TEXT DEFAULT NULL');
        }
    } catch (Exception $e) {}

    try {
        $checkExpiry = $pdo->query("SHOW COLUMNS FROM products LIKE 'expiry_months'");
        if ($checkExpiry->rowCount() === 0) {
            $pdo->exec('ALTER TABLE products ADD COLUMN expiry_months INT DEFAULT NULL');
        }
    } catch (Exception $e) {}

    try {
        $checkSize = $pdo->query("SHOW COLUMNS FROM products LIKE 'size'");
        if ($checkSize->rowCount() === 0) {
            $pdo->exec('ALTER TABLE products ADD COLUMN size VARCHAR(255) DEFAULT NULL');
        }
    } catch (Exception $e) {}

    try {
        $checkColor = $pdo->query("SHOW COLUMNS FROM products LIKE 'color'");
        if ($checkColor->rowCount() === 0) {
            $pdo->exec('ALTER TABLE products ADD COLUMN color VARCHAR(255) DEFAULT NULL');
        }
    } catch (Exception $e) {}

    try {
        $checkMaterial = $pdo->query("SHOW COLUMNS FROM products LIKE 'material'");
        if ($checkMaterial->rowCount() === 0) {
            $pdo->exec('ALTER TABLE products ADD COLUMN material VARCHAR(255) DEFAULT NULL');
        }
    } catch (Exception $e) {}

    try {
        $checkWarranty = $pdo->query("SHOW COLUMNS FROM products LIKE 'warranty'");
        if ($checkWarranty->rowCount() === 0) {
            $pdo->exec('ALTER TABLE products ADD COLUMN warranty VARCHAR(255) DEFAULT NULL');
        }
    } catch (Exception $e) {}

    $productStmt = $pdo->prepare('SELECT product_id, category_id, code, name, price, short_description, full_description, stock, rating, reviews_count, is_bestseller, created_at, origin, care_instructions, expiry_months, size, color, material, warranty FROM products WHERE product_id = :id');
    $productStmt->execute([':id' => $productId]);
    $product = $productStmt->fetch();


    if (!$product) {
        header('Location: ' . BASE_URL . '/admin/products/index.php');
        exit;
    }


    $catsStmt = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name ASC');
    $categories = $catsStmt->fetchAll();


    $availableTags = getAvailableProductTags($pdo);


    $selectedStmt = $pdo->prepare('SELECT tag FROM product_tags WHERE product_id = :id ORDER BY tag ASC');
    $selectedStmt->execute([':id' => $productId]);
    $selectedTags = $selectedStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $pdo = null;
    $errors[] = 'Không thể kết nối cơ sở dữ liệu.';
}


$name = $product['name'] ?? '';
$slug = adminSlugify($name);
$code = $product['code'] ?? '';
$categoryIds = !empty($product['category_id']) ? [(int)$product['category_id']] : [];
$price = isset($product['price']) ? (float)$product['price'] : '';
$shortDescription = $product['short_description'] ?? '';
$fullDescription = $product['full_description'] ?? '';
$stock = isset($product['stock']) ? (int)$product['stock'] : '';
$isBestseller = isset($product['is_bestseller']) ? (int)$product['is_bestseller'] : 0;
$rating = isset($product['rating']) ? (float)$product['rating'] : 0;
$reviewsCount = isset($product['reviews_count']) ? (int)$product['reviews_count'] : 0;
$createdAt = $product['created_at'] ?? '';
$origin = $product['origin'] ?? '';
$careInstructions = $product['care_instructions'] ?? '';
$expiryYears = isset($product['expiry_months']) ? (int)($product['expiry_months'] / 12) : '';
$size = $product['size'] ?? '';
$color = $product['color'] ?? '';
$material = $product['material'] ?? '';
$warranty = $product['warranty'] ?? '';


if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slugInput = trim($_POST['slug'] ?? '');
    $slug = $slugInput !== '' ? adminSlugify($slugInput) : adminSlugify($name);
    $code = trim($_POST['code'] ?? '');
    $categoryIds = isset($_POST['category_ids']) && is_array($_POST['category_ids'])
        ? array_map('intval', $_POST['category_ids'])
        : [];
    $price = trim($_POST['price'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $fullDescription = trim($_POST['full_description'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $isBestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $origin = trim($_POST['origin'] ?? '');
    $careInstructions = trim($_POST['care_instructions'] ?? '');
    $expiryYears = trim($_POST['expiry_years'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $warranty = trim($_POST['warranty'] ?? '');
    $selectedTags = isset($_POST['tags']) && is_array($_POST['tags'])
        ? array_values(array_unique(array_map('trim', $_POST['tags'])))
        : [];


    if ($name === '') {
        $errors[] = 'Vui lòng nhập tên sản phẩm.';
    }
    if (empty($categoryIds)) {
        $errors[] = 'Vui lòng chọn ít nhất một danh mục.';
    }
    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $errors[] = 'Giá bán không hợp lệ.';
    }
    if ($stock === '' || !ctype_digit((string)$stock)) {
        $errors[] = 'Số lượng tồn kho không hợp lệ.';
    }
    if ($code === '') {
        $errors[] = 'Vui lòng nhập SKU của sản phẩm.';
    }
    if ($expiryYears !== '' && (!ctype_digit((string)$expiryYears) || (int)$expiryYears < 0)) {
        $errors[] = 'Hạn sử dụng không hợp lệ.';
    }


    if (empty($errors)) {
        try {
            $pdo->beginTransaction();


            $checkStmt = $pdo->prepare('SELECT product_id FROM products WHERE code = :code AND product_id <> :id');
            $checkStmt->execute([':code' => $code, ':id' => $productId]);
            if ($checkStmt->fetch()) {
                $errors[] = 'SKU đã tồn tại. Vui lòng chọn SKU khác.';
            }


            if (empty($errors)) {
                $updateStmt = $pdo->prepare('UPDATE products
                    SET category_id = :category_id,
                        code = :code,
                        name = :name,
                        price = :price,
                        short_description = :short_description,
                        full_description = :full_description,
                        stock = :stock,
                        is_bestseller = :is_bestseller,
                        origin = :origin,
                        care_instructions = :care_instructions,
                        expiry_months = :expiry_months,
                        size = :size,
                        color = :color,
                        material = :material,
                        warranty = :warranty
                    WHERE product_id = :id');
                $updateStmt->execute([
                    ':category_id' => !empty($categoryIds) ? $categoryIds[0] : $product['category_id'],
                    ':code' => $code,
                    ':name' => $name,
                    ':price' => (float)$price,
                    ':short_description' => $shortDescription,
                    ':full_description' => $fullDescription,
                    ':stock' => (int)$stock,
                    ':is_bestseller' => $isBestseller,
                    ':origin' => $origin,
                    ':care_instructions' => $careInstructions,
                    ':expiry_months' => $expiryYears !== '' ? (int)$expiryYears * 12 : null,
                    ':size' => $size,
                    ':color' => $color,
                    ':material' => $material,
                    ':warranty' => $warranty,
                    ':id' => $productId,
                ]);


                $tagValues = array_values(array_unique(array_filter($selectedTags, static fn($tag) => $tag !== '')));


                $deleteTags = $pdo->prepare('DELETE FROM product_tags WHERE product_id = :id');
                $deleteTags->execute([':id' => $productId]);


                if (!empty($tagValues)) {
                    $insertTag = $pdo->prepare('INSERT INTO product_tags (product_id, tag) VALUES (:pid, :tag)');
                    foreach ($tagValues as $tag) {
                        $insertTag->execute([
                            ':pid' => $productId,
                            ':tag' => $tag,
                        ]);
                    }
                }


                $pdo->commit();
                $successMessage = 'Đã cập nhật sản phẩm thành công.';


                $refreshStmt = $pdo->prepare('SELECT product_id, category_id, code, name, price, short_description, full_description, stock, rating, reviews_count, is_bestseller, created_at, origin, care_instructions, expiry_months, size, color, material, warranty FROM products WHERE product_id = :id');
                $refreshStmt->execute([':id' => $productId]);
                $product = $refreshStmt->fetch();


                $name = $product['name'] ?? $name;
                $slug = adminSlugify($name);
                $code = $product['code'] ?? $code;
                $categoryIds = !empty($product['category_id']) ? [(int)$product['category_id']] : $categoryIds;
                $price = isset($product['price']) ? (float)$product['price'] : $price;
                $shortDescription = $product['short_description'] ?? $shortDescription;
                $fullDescription = $product['full_description'] ?? $fullDescription;
                $stock = isset($product['stock']) ? (int)$product['stock'] : $stock;
                $isBestseller = isset($product['is_bestseller']) ? (int)$product['is_bestseller'] : $isBestseller;
                $rating = isset($product['rating']) ? (float)$product['rating'] : $rating;
                $reviewsCount = isset($product['reviews_count']) ? (int)$product['reviews_count'] : $reviewsCount;
                $createdAt = $product['created_at'] ?? $createdAt;
                $origin = $product['origin'] ?? $origin;
                $careInstructions = $product['care_instructions'] ?? $careInstructions;
                $expiryYears = isset($product['expiry_months']) ? (int)($product['expiry_months'] / 12) : $expiryYears;
                $size = $product['size'] ?? $size;
                $color = $product['color'] ?? $color;
                $material = $product['material'] ?? $material;
                $warranty = $product['warranty'] ?? $warranty;


                $selectedTags = $tagValues;
            } else {
                $pdo->rollBack();
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Không thể cập nhật sản phẩm. Vui lòng thử lại.';
        }
    }
}


include __DIR__ . '/../includes/header.php';
?>


<style>
    .edit-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .edit-layout { grid-template-columns: 1fr; } }


    .edit-content { background: var(--white); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; }
    .edit-header {
        padding: 22px <?php echo CONTAINER_PADDING; ?>;
        border-bottom: 1px solid rgba(0,0,0,0.08);
        background: linear-gradient(135deg, rgba(60,96,60,0.08), rgba(210,100,38,0.05));
    }
    .edit-header h1 { font-size: 24px; font-weight: 700; color: var(--primary); }
    .edit-header .breadcrumb { margin-top: 6px; color: #666; font-size: 13px; }
    .edit-header .breadcrumb a { color: var(--secondary); font-weight: 600; text-decoration: none; }
    .edit-header .breadcrumb a:hover { color: var(--primary); text-decoration: underline; }
    .edit-header .breadcrumb span { color: #777; font-weight: 500; }


    .page-body { padding: 26px <?php echo CONTAINER_PADDING; ?> 34px; }
    .page-grid { display: grid; grid-template-columns: 2.2fr 1fr; gap: <?php echo GRID_GAP_SMALL; ?>; }
    @media (max-width: <?php echo BREAKPOINT_LG; ?>) { .page-grid { grid-template-columns: 1fr; } }


    .card { background: #fff; border: 1px solid rgba(0,0,0,0.05); border-radius: 14px; box-shadow: 0 12px 28px rgba(0,0,0,0.04); margin-bottom: 18px; }
    .card-header { padding: 18px 24px 0; }
    .card-header h3 { font-size: 16px; font-weight: 700; color: var(--primary); margin: 0; display:flex; align-items:center; gap:8px; }
    .card-header h3 i { color: var(--secondary); }
    .card-body { padding: 18px 24px 24px; display: grid; gap: 16px; }
    .card-body .note { font-size: 12px; color: #999; }


    .form-field { display:flex; flex-direction:column; gap:6px; }
    .form-field label { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.05em; font-weight:600; }
    .form-field input[type="text"],
    .form-field input[type="number"],
    .form-field textarea { padding: 11px 14px; border: 1px solid #e0e0e0; border-radius: 10px; outline: none; font-size: 14px; transition: border-color .2s ease, box-shadow .2s ease; background:#fff; }
    .form-field textarea { min-height: 140px; resize: vertical; }
    .form-field input:focus,
    .form-field textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(231,181,81,0.25); }
    .form-field small { color:#999; font-size:12px; }


    .grid-2 { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:16px; }
    .grid-3 { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:16px; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }


    .checkbox-columns { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:12px 18px; }
    .checkbox-label { display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid #ececec; border-radius:10px; background:#fff; transition:all .2s ease; font-size:13px; color:#555; }
    .checkbox-label input { accent-color: var(--secondary); }
    .checkbox-label:hover { border-color: var(--secondary); background: rgba(222,171,70,0.1); }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .checkbox-columns { grid-template-columns: 1fr; } }


    .tags-list { display:flex; flex-wrap:wrap; gap:10px; max-height: 180px; overflow-y:auto; padding-right:8px; }
    .tag-option { display:flex; align-items:center; gap:6px; padding:8px 12px; border:1px solid #eee; border-radius:20px; font-size:12px; background:#fafafa; }
    .tag-option input { accent-color: var(--secondary); }
    .tags-empty { color:#999; font-size:13px; }


    .toggle { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border:1px solid rgba(0,0,0,0.05); border-radius:12px; background:#fff9ef; }
    .toggle .info { display:flex; flex-direction:column; gap:4px; }
    .toggle .info span { font-size:13px; color:#333; font-weight:600; }
    .toggle .info small { font-size:12px; color:#999; }


    .switch { position:relative; display:inline-block; width:48px; height:26px; }
    .switch input { opacity:0; width:0; height:0; }
    .slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#ccc; transition:.3s; border-radius:26px; }
    .slider:before { position:absolute; content:""; height:22px; width:22px; left:2px; bottom:2px; background:white; transition:.3s; border-radius:50%; }
    input:checked + .slider { background-color: var(--secondary); }
    input:checked + .slider:before { transform: translateX(22px); }


    .actions { display:flex; flex-wrap:wrap; gap:12px; }
    .btn { display:inline-flex; align-items:center; gap:8px; padding:11px 18px; border-radius:10px; border:none; cursor:pointer; font-weight:600; text-decoration:none; justify-content:center; }
    .btn i { font-size:14px; }
    .btn-primary { background: linear-gradient(135deg, var(--secondary), var(--primary)); color:#fff; box-shadow: 0 8px 20px rgba(50,115,69,0.25); }
    .btn-outline { background:#fff; color:var(--dark); border:1px solid #e0e0e0; }
    .btn-outline:hover { border-color: var(--secondary); color: var(--secondary); }
    .btn-danger { background:#fff1f0; color:#b4231a; border:1px solid rgba(244,106,94,0.35); }


    .image-preview { display:flex; align-items:center; gap:16px; }
    .image-placeholder { width:120px; height:120px; border-radius:12px; background:#f7f7f7; border:2px dashed #ddd; display:flex; align-items:center; justify-content:center; color:#bbb; font-size:13px; }


    .alert { border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:14px; }
    .alert-error { border:1px solid #f0c7c7; background:#fff2f0; color:#c74343; }
    .alert-success { border:1px solid #b7eb8f; background:#f6ffed; color:#1f5421; }


    .summary-list { display:flex; flex-direction:column; gap:12px; }
    .summary-item { display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border:1px dashed rgba(0,0,0,0.08); border-radius:12px; }
    .summary-item span { font-size:14px; color:#555; }
    .summary-item strong { font-size:14px; color:var(--primary); }


    .stat-block { display:flex; flex-direction:column; gap:4px; padding:12px 14px; border-radius:12px; background:rgba(198,235,197,0.35); border:1px solid rgba(198,235,197,0.7); color:#2f7a35; }
    .stat-block span { font-size:12px; text-transform:uppercase; letter-spacing:0.05em; }
    .stat-block strong { font-size:20px; font-weight:700; }


    .right-card .card-body { display:flex; flex-direction:column; gap:14px; }
    .right-card .actions { flex-direction:column; }
    .right-card textarea,
    .right-card input { background:#f9f9f9; }


    .scrollable { max-height: 200px; overflow-y:auto; padding-right:4px; }
    .scrollable::-webkit-scrollbar { width:6px; }
    .scrollable::-webkit-scrollbar-thumb { background:rgba(0,0,0,0.1); border-radius:3px; }


    .section-title { font-size:12px; font-weight:600; text-transform:uppercase; color:#888; letter-spacing:0.05em; }
    .readonly-input { padding: 10px 14px; border:1px dashed rgba(0,0,0,0.08); border-radius:10px; background:#fafafa; color:#777; font-size:13px; }


    .badge-info { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; background:rgba(210,100,38,0.1); color:var(--secondary); font-weight:600; font-size:13px; }


    .divider { height:1px; background:rgba(0,0,0,0.05); margin:6px 0; }


    .quick-links { display:flex; flex-direction:column; gap:10px; }
    .quick-links a { color:var(--secondary); font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .quick-links a:hover { text-decoration:underline; }


    .placeholder-field { border-style:dashed; color:#b5b5b5; font-style:italic; }
</style>


<div class="edit-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="edit-content">
        <div class="edit-header">
            <h1>Sửa Sản Phẩm</h1>
            <div class="breadcrumb"><a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a> / <a href="<?php echo BASE_URL; ?>/admin/products/index.php">Quản lý sản phẩm</a> / <span>Sửa</span></div>
        </div>


        <form method="post" action="" novalidate>
            <div class="page-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                    </div>
                <?php elseif ($successMessage !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>


                <div class="page-grid">
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-info-circle"></i> Thông Tin Cơ Bản</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-field">
                                    <label>Tên Sản Phẩm *</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Nhập tên sản phẩm" required />
                                </div>
                                <div class="grid-2">
                                    <div class="form-field">
                                        <label>Slug (URL)</label>
                                        <input type="text" name="slug" id="slug-input" value="<?php echo htmlspecialchars($slug); ?>" placeholder="Tự động tạo từ tên sản phẩm" />
                                        <small>Slug được dùng cho đường dẫn thân thiện, chưa áp dụng vào hệ thống.</small>
                                    </div>
                                    <div class="form-field">
                                        <label>SKU *</label>
                                        <input type="text" name="code" value="<?php echo htmlspecialchars($code); ?>" placeholder="VD: GH-001" required />
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Danh Mục *</label>
                                    <div class="checkbox-columns">
                                        <?php foreach ($categories as $cat): ?>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="category_ids[]" value="<?php echo (int)$cat['category_id']; ?>" <?php echo in_array((int)$cat['category_id'], $categoryIds, true) ? 'checked' : ''; ?> />
                                                <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <small>Chỉ danh mục đầu tiên được chọn sẽ được lưu vào sản phẩm.</small>
                                </div>
                                <div class="form-field">
                                    <label>Mô Tả Ngắn</label>
                                    <textarea name="short_description" placeholder="Tóm tắt nổi bật của sản phẩm"><?php echo htmlspecialchars($shortDescription); ?></textarea>
                                </div>
                                <div class="form-field">
                                    <label>Mô Tả Chi Tiết</label>
                                    <textarea name="full_description" placeholder="Mô tả chi tiết, công dụng, hướng dẫn sử dụng..."><?php echo htmlspecialchars($fullDescription); ?></textarea>
                                </div>
                            </div>
                        </div>


                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-tags"></i> Tags Sản Phẩm</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-field">
                                    <label>Tags</label>
                                    <?php if (!empty($availableTags)): ?>
                                        <div class="checkbox-columns">
                                            <?php foreach ($availableTags as $tag): ?>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($tag); ?>" <?php echo in_array($tag, $selectedTags, true) ? 'checked' : ''; ?> />
                                                    <span><?php echo htmlspecialchars($tag); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="tags-empty">Chưa có tag nào.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>


                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-dollar-sign"></i> Giá &amp; Tồn Kho</h3>
                            </div>
                            <div class="card-body">
                                <div class="grid-2">
                                    <div class="form-field">
                                        <label>Giá Bán *</label>
                                        <input type="number" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($price); ?>" placeholder="VD: 150000" required />
                                    </div>
                                    <div class="form-field">
                                        <label>Số Lượng Tồn Kho *</label>
                                        <input type="number" name="stock" min="0" step="1" value="<?php echo htmlspecialchars($stock); ?>" placeholder="VD: 10" required />
                                    </div>
                                </div>
                                <div class="toggle">
                                    <div class="info">
                                        <span>Sản phẩm nổi bật</span>
                                        <small>Kích hoạt để hiển thị trong các danh sách nổi bật.</small>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="is_bestseller" <?php echo $isBestseller ? 'checked' : ''; ?> />
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>


                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-image"></i> Hình Ảnh Sản Phẩm</h3>
                            </div>
                            <div class="card-body">
                                <div class="image-preview">
                                    <div class="image-placeholder">Ảnh chính</div>
                                    <div class="form-field" style="flex:1;">
                                        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/products/images.php?id=<?php echo $productId; ?>"><i class="fas fa-photo-video"></i> Quản lý Ảnh</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div>
                        <div class="card right-card">
                            <div class="card-header">
                                <h3><i class="fas fa-rocket"></i> Xuất Bản</h3>
                            </div>
                            <div class="card-body">
                                <div class="toggle" style="background:#f0fff4;">
                                    <div class="info">
                                        <span>Kích hoạt sản phẩm</span>
                                        <small>Tự động bật khi tồn kho &gt; 0.</small>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" <?php echo ($stock > 0) ? 'checked' : ''; ?> disabled />
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <div class="toggle">
                                    <div class="info">
                                        <span>Sản phẩm nổi bật</span>
                                        <small>Được đồng bộ với tùy chọn bên trái.</small>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" <?php echo $isBestseller ? 'checked' : ''; ?> disabled />
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <div class="divider"></div>
                                <div class="summary-list">
                                    <div class="summary-item">
                                        <span>Mã sản phẩm</span>
                                        <strong><?php echo htmlspecialchars($code); ?></strong>
                                    </div>
                                    <div class="summary-item">
                                        <span>Ngày tạo</span>
                                        <strong><?php echo $createdAt ? date(DATETIME_FORMAT, strtotime($createdAt)) : 'Đang cập nhật'; ?></strong>
                                    </div>
                                    <div class="summary-item">
                                        <span>Tồn kho</span>
                                        <strong><?php echo (int)$stock; ?> sản phẩm</strong>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="card right-card">
                            <div class="card-header">
                                <h3><i class="fas fa-leaf"></i> Thông Tin Bổ Sung</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-field">
                                    <label>Kích Thước</label>
                                    <input type="text" name="size" value="<?php echo htmlspecialchars($size); ?>" placeholder="VD: 30cm x 20cm x 100cm" />
                                </div>
                                <div class="form-field">
                                    <label>Màu Sắc</label>
                                    <input type="text" name="color" value="<?php echo htmlspecialchars($color); ?>" placeholder="VD: Xanh lá, Đỏ, Vàng" />
                                </div>
                                <div class="form-field">
                                    <label>Chất Liệu</label>
                                    <input type="text" name="material" value="<?php echo htmlspecialchars($material); ?>" placeholder="VD: Gỗ" />
                                </div>
                                <div class="form-field">
                                    <label>Bảo Hành</label>
                                    <input type="text" name="warranty" value="<?php echo htmlspecialchars($warranty); ?>" placeholder="VD: 12 tháng" />
                                </div>
                                <div class="form-field">
                                    <label>Xuất Xứ</label>
                                    <input type="text" name="origin" value="<?php echo htmlspecialchars($origin); ?>" placeholder="VD: Khánh Hòa, Việt Nam" />
                                </div>
                                <div class="form-field">
                                    <label>Hướng dẫn sử dụng</label>
                                    <textarea name="care_instructions" rows="3" placeholder="Nhập hướng dẫn sử dụng sản phẩm..."><?php echo htmlspecialchars($careInstructions); ?></textarea>
                                </div>
                                <div class="form-field">
                                    <label>Hạn sử dụng (năm)</label>
                                    <input type="number" name="expiry_years" min="0" step="1" value="<?php echo htmlspecialchars($expiryYears); ?>" placeholder="VD: 2" />
                                </div>
                            </div>
                        </div>


                        <div class="card right-card">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-bar"></i> Thống Kê</h3>
                            </div>
                            <div class="card-body">
                                <div class="stat-block">
                                    <span>Đánh giá trung bình</span>
                                    <strong><?php echo number_format((float)$rating, 1); ?></strong>
                                </div>
                                <div class="stat-block" style="background:rgba(255,215,160,0.3); border-color:rgba(255,215,160,0.7); color:#a45b16;">
                                    <span>Lượt đánh giá</span>
                                    <strong><?php echo (int)$reviewsCount; ?></strong>
                                </div>
                            </div>
                        </div>


                        <div class="card right-card">
                            <div class="card-body actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cập Nhật</button>
                                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/products/index.php"><i class="fas fa-arrow-left"></i> Quay Lại</a>
                                <button type="button" class="btn btn-danger" onclick="confirmDeleteProduct(event)"><i class="fas fa-trash"></i> Xóa Sản Phẩm</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    (function() {
        const nameInput = document.querySelector('input[name="name"]');
        const slugInput = document.getElementById('slug-input');
        if (!nameInput || !slugInput) return;


        const slugify = (text) => {
            if (!text) return '';
            text = text.normalize('NFD').replace(/[�-]/g, (c) => c);
            text = text.replace(/[đĐ]/g, 'd');
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            return text.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        };


        let manualEdit = slugInput.value !== '' && slugInput.value !== slugify(nameInput.value);


        nameInput.addEventListener('input', () => {
            if (manualEdit) return;
            slugInput.value = slugify(nameInput.value);
        });


        slugInput.addEventListener('input', () => {
            manualEdit = slugInput.value.trim() !== '';
        });
    })();


    function confirmDeleteProduct(event) {
        event.preventDefault();
        if (confirm('Bạn chắc chắn muốn xóa sản phẩm này? Tính năng xóa hiện chưa khả dụng.')) {
            alert('Chức năng xóa sẽ được bổ sung trong tương lai.');
        }
    }
</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>



