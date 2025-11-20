<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


$errors = [];
$successMessage = '';
$pdo = null;


$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;


try {
    $pdo = getPDO();
    $catsStmt = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name ASC');
    $categories = $catsStmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}


if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';


    if (isset($_POST['current_q'])) {
        $q = trim((string) $_POST['current_q']);
    }
    if (isset($_POST['current_category'])) {
        $categoryId = (int) $_POST['current_category'];
    }
    if (isset($_POST['current_status'])) {
        $status = trim((string) $_POST['current_status']);
    }
    if (isset($_POST['current_page'])) {
        $page = max(1, (int) $_POST['current_page']);
    }


    if ($action === 'delete_product') {
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;


        if ($productId <= 0) {
            $errors[] = 'Sản phẩm không hợp lệ.';
        } else {
            $imageFilesToDelete = [];
            try {
                $pdo->beginTransaction();


                $selectStmt = $pdo->prepare('SELECT product_id, name FROM products WHERE product_id = :id');
                $selectStmt->execute([':id' => $productId]);
                $productRow = $selectStmt->fetch();


                if (!$productRow) {
                    $errors[] = 'Sản phẩm không tồn tại hoặc đã bị xóa.';
                    $pdo->rollBack();
                } else {
                    $imageStmt = $pdo->prepare('SELECT image_url FROM product_images WHERE product_id = :id');
                    $imageStmt->execute([':id' => $productId]);
                    $imageUrls = $imageStmt->fetchAll(PDO::FETCH_COLUMN);


                    foreach ($imageUrls as $imageUrl) {
                        if (!is_string($imageUrl) || $imageUrl === '') {
                            continue;
                        }


                        $parsedPath = parse_url($imageUrl, PHP_URL_PATH);
                        $candidatePath = $parsedPath !== null ? $parsedPath : $imageUrl;
                        if ($candidatePath === '') {
                            continue;
                        }


                        $candidatePath = str_replace('\\', '/', $candidatePath);
                        $candidatePath = ltrim($candidatePath, '/');


                        if (strpos($candidatePath, 'uploads/products/') !== 0) {
                            continue;
                        }
                        if (strpos($candidatePath, '..') !== false) {
                            continue;
                        }


                        $absolutePath = BASE_PATH . '/' . $candidatePath;
                        if (!in_array($absolutePath, $imageFilesToDelete, true)) {
                            $imageFilesToDelete[] = $absolutePath;
                        }
                    }

                    $orderStmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = :id');
                    $orderStmt->execute([':id' => $productId]);
                    $orderCount = (int) $orderStmt->fetchColumn();


                    if ($orderCount > 0) {
                        $errors[] = 'Không thể xóa vì sản phẩm đã xuất hiện trong đơn hàng.';
                        $pdo->rollBack();
                    } else {
                        $deletionQueries = [
                            'DELETE FROM product_images WHERE product_id = :id',
                            'DELETE FROM product_tags WHERE product_id = :id',
                            'DELETE FROM cart WHERE product_id = :id',
                            'DELETE FROM wishlist WHERE product_id = :id',
                            'DELETE FROM product_reviews WHERE product_id = :id',
                        ];


                        foreach ($deletionQueries as $sqlDelete) {
                            $stmt = $pdo->prepare($sqlDelete);
                            $stmt->execute([':id' => $productId]);
                        }


                        $deleteProduct = $pdo->prepare('DELETE FROM products WHERE product_id = :id');
                        $deleteProduct->execute([':id' => $productId]);


                        if ($deleteProduct->rowCount() === 0) {
                            $errors[] = 'Không thể xóa sản phẩm. Vui lòng thử lại.';
                            $pdo->rollBack();
                        } else {
                            $pdo->commit();
                            $successMessage = 'Đã xóa sản phẩm "' . $productRow['name'] . '" thành công.';
                        }
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Không thể xóa sản phẩm. Vui lòng thử lại.';
            }


            if ($successMessage !== '' && !empty($imageFilesToDelete)) {
                foreach (array_unique($imageFilesToDelete) as $filePath) {
                    if (is_string($filePath) && $filePath !== '' && is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }


        if (!empty($errors)) {
            $successMessage = '';
        }
    }
}


$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q OR p.code LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category_id';
    $params[':category_id'] = $categoryId;
}
if ($status === 'active') {
    $where[] = 'p.stock > 0';
} elseif ($status === 'out') {
    $where[] = 'p.stock <= 0';
}
$whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';


$perPage = defined('PAGINATION_PRODUCTS_PER_PAGE') ? (int)PAGINATION_PRODUCTS_PER_PAGE : 16;
$offset = ($page - 1) * $perPage;


$totalProducts = 0;
try {
    $countSql = 'SELECT COUNT(*) AS cnt FROM products p ' . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $row = $countStmt->fetch();
    $totalProducts = (int)($row['cnt'] ?? 0);
} catch (Exception $e) {
    $totalProducts = 0;
}


$products = [];
try {
    $sql = 'SELECT p.product_id, p.code, p.name, p.price, p.stock, p.rating, p.reviews_count, p.created_at, c.category_name,
                   (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.product_id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.created_at ASC LIMIT 1) AS primary_image_url,
                   (SELECT pi.alt_text FROM product_images pi WHERE pi.product_id = p.product_id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.created_at ASC LIMIT 1) AS primary_image_alt
            FROM products p INNER JOIN categories c ON p.category_id = c.category_id
            ' . $whereSql . '
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $products = [];
}


$tagCounts = [];
if (!empty($products)) {
    $ids = array_column($products, 'product_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        $tagStmt = $pdo->prepare('SELECT product_id, COUNT(*) AS cnt FROM product_tags WHERE product_id IN (' . $placeholders . ') GROUP BY product_id');
        foreach ($ids as $i => $id) {
            $tagStmt->bindValue($i + 1, (int)$id, PDO::PARAM_INT);
        }
        $tagStmt->execute();
        $rows = $tagStmt->fetchAll();
        foreach ($rows as $r) {
            $tagCounts[(int)$r['product_id']] = (int)$r['cnt'];
        }
    } catch (Exception $e) {
        $tagCounts = [];
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


    .filters { display: grid; grid-template-columns: 2fr 1fr 1fr auto; align-items: end; gap: 12px; padding: 16px <?php echo CONTAINER_PADDING; ?>; background-color: var(--white); border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
    .filters .field { display: flex; flex-direction: column; gap: 6px; }
    .filters .field label { font-size: 12px; color: #666; }
    .filters .field input, .filters .field select { padding: 8px 10px; height: 38px; border: 1px solid #e0e0e0; border-radius: 8px; outline: none; background: #fff; }
    .filters .actions { display: flex; gap: 10px; justify-content: flex-end; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border-radius: 8px; text-decoration: none; cursor: pointer; border: none; }
    .btn-primary { background: var(--secondary); color: var(--white); }
    .btn-outline { background: #fff; color: var(--dark); border: 1px solid #e0e0e0; }


    .list-wrapper { padding: 20px <?php echo CONTAINER_PADDING; ?>; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 12px 10px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
    .table th { font-size: 13px; color: #666; font-weight: 600; }
    .product-cell { display: flex; align-items: center; gap: 12px; }
    .product-avatar { width: 48px; height: 48px; border-radius: 8px; background-color: #f4f4f4; display: flex; align-items: center; justify-content: center; color: #999; font-weight: 600; overflow: hidden; position: relative; }
    .product-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .avatar-placeholder { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; }
    .avatar-placeholder.is-hidden { display: none; }
    .sku { font-size: 12px; color: #777; }
    .price { color: var(--primary); font-weight: 600; }
    .status-badge { display: inline-flex; align-items: center; justify-content: center; gap: 0; height: 24px; padding: 0 10px; border-radius: 20px; font-size: 12px; }
    .status-badge i { font-size: 14px; line-height: 1; }
    .status-active { background: #e6f7ea; color: #237a3f; }
    .status-out { background: #fff2f0; color: #cf1322; }
    .col-status, .col-tags, .col-actions { text-align: center; }
    .col-status { width: 90px; }
    .col-tags { width: 70px; }
    .tag-count { display: inline-flex; align-items: center; justify-content: center; height: 24px; line-height: 24px; font-weight: 600; min-width: 24px; }
    .actions-cell { display: flex; flex-direction: column; gap: 8px; align-items: center; justify-content: center; }
    .col-actions { width: 80px; }
    .table td.col-tags, .table th.col-tags, .table td.col-status, .table th.col-status { text-align: center; padding-left: 0; }
    .icon-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid #e0e0e0; color: var(--dark); background: #fff; text-decoration: none; }
    button.icon-btn { cursor: pointer; padding: 0; }
    .icon-btn:hover { background: var(--light); }
    .top-actions { padding: 16px <?php echo CONTAINER_PADDING; ?>; display: flex; justify-content: flex-end; gap: 10px; }
    @media (max-width: <?php echo BREAKPOINT_SM; ?>) { .filters { flex-wrap: wrap; } .actions-cell { flex-wrap: wrap; } }
    .alert { border-radius: 12px; padding: 12px 14px; margin: 0 <?php echo CONTAINER_PADDING; ?> 16px; font-size: 14px; }
    .alert-success { border: 1px solid #b7eb8f; background: #f6ffed; color: #1f5421; }
    .alert-error { border: 1px solid #f0c7c7; background: #fff2f0; color: #c74343; }
</style>


<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Quản Lý Sản Phẩm</h1>
            <div class="breadcrumb"><a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a> / <span>Quản lý sản phẩm</span></div>
        </div>


        <div class="top-actions">
            <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/products/tags.php"><i class="fas fa-tags"></i> Quản lý Tags</a>
            <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/admin/products/add.php"><i class="fas fa-plus"></i> Thêm Sản Phẩm Mới</a>
        </div>


        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
            </div>
        <?php elseif ($successMessage !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>


        <form class="filters" method="get" action="">
            <div class="field" style="flex: 1; min-width: 200px;">
                <label>Tìm kiếm</label>
                <input type="text" name="q" placeholder="Tên sản phẩm, SKU..." value="<?php echo htmlspecialchars($q); ?>" />
            </div>
            <div class="field">
                <label>Danh mục</label>
                <select name="category">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['category_id']; ?>" <?php echo ($categoryId === (int)$cat['category_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="" <?php echo $status === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="out" <?php echo $status === 'out' ? 'selected' : ''; ?>>Hết hàng</option>
                </select>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> Lọc</button>
            </div>
        </form>


        <div class="list-wrapper">
            <div class="products-info" style="margin-bottom: 12px; color: #555;">Tổng cộng: <?php echo $totalProducts; ?> sản phẩm</div>
            <table class="table" aria-label="Danh sách sản phẩm">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>
                        <th>Đánh giá</th>
                        <th class="col-tags">Tags</th>
                        <th class="col-status">Trạng thái</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 20px; color: #777;">Không có sản phẩm nào phù hợp</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                        $active = ((int)$p['stock'] > 0);
                        $tagCount = $tagCounts[$p['product_id']] ?? 0;
                        ?>
                        <tr>
                            <td>
                                <?php
                                $primaryImageUrl = $p['primary_image_url'] ?? '';
                                $primaryImageAlt = trim((string) ($p['primary_image_alt'] ?? ''));
                                if ($primaryImageAlt === '') {
                                    $primaryImageAlt = 'Ảnh của ' . ($p['name'] ?? '');
                                }
                                ?>
                                <div class="product-avatar">
                                    <?php if (!empty($primaryImageUrl)): ?>
                                        <img src="<?php echo htmlspecialchars($primaryImageUrl); ?>" alt="<?php echo htmlspecialchars($primaryImageAlt); ?>" onerror="this.nextElementSibling.classList.remove('is-hidden'); this.remove();">
                                    <?php endif; ?>
                                    <span class="avatar-placeholder<?php echo !empty($primaryImageUrl) ? ' is-hidden' : ''; ?>">IMG</span>
                                </div>
                            </td>
                            <td>
                                <div class="product-cell">
                                    <div>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($p['name']); ?></div>
                                        <div class="sku">SKU: <?php echo htmlspecialchars($p['code']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($p['category_name']); ?></td>
                            <td class="price"><?php echo number_format((float)$p['price'], 0, ',', '.'); ?>₫</td>
                            <td><?php echo (int)$p['stock']; ?></td>
                            <td><?php echo number_format((float)$p['rating'], 1); ?> (<?php echo (int)$p['reviews_count']; ?>)</td>
                            <td class="col-tags"><span class="tag-count"><?php echo (int)$tagCount; ?></span></td>
                            <td class="col-status">
                                <?php if ($active): ?>
                                    <span class="status-badge status-active" title="Hoạt động" aria-label="Hoạt động"><i class="fas fa-check-circle"></i></span>
                                <?php else: ?>
                                    <span class="status-badge status-out" title="Hết hàng" aria-label="Hết hàng"><i class="fas fa-times-circle"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell col-actions">
                                <a class="icon-btn" title="Xem" href="<?php echo BASE_URL; ?>/views/products-detail.php?id=<?php echo (int)$p['product_id']; ?>"><i class="fas fa-eye"></i></a>
                                <a class="icon-btn" title="Sửa" href="<?php echo BASE_URL; ?>/admin/products/edit.php?id=<?php echo (int)$p['product_id']; ?>"><i class="fas fa-edit"></i></a>
                                <button type="button" class="icon-btn js-delete-product" data-product-id="<?php echo (int)$p['product_id']; ?>" data-product-name="<?php echo htmlspecialchars($p['name']); ?>" title="Xóa"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>


            <?php
            $totalPages = max(1, (int)ceil($totalProducts / $perPage));
            if ($totalPages > 1):
                $baseUrl = $_SERVER['PHP_SELF'];
                $query = $_GET;
                ?>
                <div class="pagination" style="margin-top: 16px; display:flex; gap:6px;">
                    <?php for ($pnum = 1; $pnum <= $totalPages; $pnum++):
                        $query['page'] = $pnum;
                        $href = $baseUrl . '?' . http_build_query($query);
                        $isActive = ($pnum === $page);
                        ?>
                        <a href="<?php echo htmlspecialchars($href); ?>" class="icon-btn" style="<?php echo $isActive ? 'background:var(--secondary); color:#fff; border-color:var(--secondary);' : ''; ?> width:auto; padding:6px 10px;"><?php echo $pnum; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            <form id="delete-product-form" method="post" action="" style="display:none;">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="product_id" value="">
                <input type="hidden" name="current_q" value="<?php echo htmlspecialchars($q); ?>">
                <input type="hidden" name="current_category" value="<?php echo (int)$categoryId; ?>">
                <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($status); ?>">
                <input type="hidden" name="current_page" value="<?php echo (int)$page; ?>">
            </form>
        </div>
    </div>
</div>


<script>
    (function () {
        const form = document.getElementById('delete-product-form');
        if (!form) {
            return;
        }


        const productIdInput = form.querySelector('input[name="product_id"]');
        const deleteButtons = document.querySelectorAll('.js-delete-product');


        deleteButtons.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const productId = btn.getAttribute('data-product-id');
                const productName = btn.getAttribute('data-product-name') || 'sản phẩm này';


                if (!productId) {
                    return;
                }


                if (!confirm(`Bạn chắc chắn muốn xóa "${productName}"? Hành động này không thể hoàn tác.`)) {
                    return;
                }


                productIdInput.value = productId;
                form.submit();
            });
        });
    })();
</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>

