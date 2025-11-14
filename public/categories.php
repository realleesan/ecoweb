<?php
require_once '../includes/database.php';

// Kết nối database
$pdo = null;
try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

// Lọc và sắp xếp
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$valid_sorts = [
    'name_asc' => 'category_name ASC',
    'name_desc' => 'category_name DESC',
    'count_asc' => 'product_count ASC',
    'count_desc' => 'product_count DESC',
];
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $valid_sorts) ? $_GET['sort'] : 'name_asc';

// Phân trang
$items_per_page = 12; // 4 hàng x 3 cột = 12 items
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

$categories = [];
$total_categories = 0;
$query_params = [];
$where_clauses = [];

if ($search !== '') {
    $where_clauses[] = '(c.category_name LIKE :search OR c.description LIKE :search)';
    $query_params[':search'] = '%' . $search . '%';
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

if ($pdo) {
    try {
        // Đếm tổng số categories phù hợp điều kiện
        $count_sql = "SELECT COUNT(*) FROM categories c $where_sql";
        $count_stmt = $pdo->prepare($count_sql);
        foreach ($query_params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        $count_stmt->execute();
        $total_categories = (int) $count_stmt->fetchColumn();

        // Lấy dữ liệu categories cùng số lượng sản phẩm liên quan
        $order_sql = $valid_sorts[$sort];
        $data_sql = "SELECT c.category_id, c.category_name, c.slug, c.description, c.image, 
                            (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) AS product_count
                     FROM categories c
                     $where_sql
                     ORDER BY $order_sql
                     LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($data_sql);
        foreach ($query_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll();
    } catch (PDOException $e) {
        $categories = [];
        $total_categories = 0;
    }
}

$total_pages = $total_categories > 0 ? (int)ceil($total_categories / $items_per_page) : 0;

include '../includes/header.php';
?>

<style>
    /* Categories Page Styles - Matching Products Page */
    .products-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 5%;
    }

    .page-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 36px;
        color: var(--primary);
        margin-bottom: 30px;
        text-align: center;
        position: relative;
        padding-bottom: 20px;
    }

    .page-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 3px;
        background: var(--secondary);
    }

    .filters-section {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-group label {
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        color: var(--dark);
        font-size: 14px;
    }

    .filter-group select, 
    .filter-group input[type="text"] {
        padding: 8px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        color: var(--dark);
        background-color: var(--white);
        cursor: pointer;
    }

    .filter-group select:focus,
    .filter-group input[type="text"]:focus {
        outline: none;
        border-color: var(--primary);
    }

    .products-info {
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 16px;
        color: var(--dark);
        margin-bottom: 20px;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 32px;
        margin-bottom: 40px;
    }

    .product-card {
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        cursor: pointer;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .product-image {
        width: 100%;
        height: 200px;
        overflow: hidden;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.05);
    }

    .product-info {
        padding: 15px;
    }

    .product-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 3em;
    }

    .product-description {
        font-size: 14px;
        color: var(--gray);
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 4.5em;
    }

    .product-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        color: var(--gray);
    }

    .product-count {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .view-btn {
        display: inline-block;
        padding: 6px 12px;
        background-color: var(--primary);
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }

    .view-btn:hover {
        background-color: #2d4a2d;
    }

    .no-results {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px 20px;
    }

    .no-results i {
        font-size: 3rem;
        color: var(--gray);
        margin-bottom: 15px;
        opacity: 0.7;
    }

    .no-results h3 {
        font-size: 1.5rem;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 40px;
        flex-wrap: wrap;
        gap: 8px;
    }

    .pagination a, 
    .pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        color: var(--dark);
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .pagination a:hover {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    .pagination .current {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
        font-weight: 500;
    }

    .pagination .disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    /* Responsive Design */

    .search-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(60, 96, 60, 0.1);
    }

    .search-wrapper {
        position: relative;
    }

    .search-wrapper i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary);
        pointer-events: none;
    }

    .sort-select {
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 14px;
        padding: 10px 35px 10px 15px;
        border: 2px solid rgba(116, 73, 61, 0.2);
        border-radius: 25px;
        outline: none;
        background: var(--white);
        color: var(--dark);
        cursor: pointer;
        transition: all 0.3s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%233C603C' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        padding-right: 40px;
    }

    .sort-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(60, 96, 60, 0.1);
    }

    .filter-btn {
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 14px;
        padding: 10px 25px;
        background: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-btn:hover {
        background: var(--secondary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(210, 100, 38, 0.3);
    }

    .filter-btn i {
        font-size: 14px;
    }

    .results-count {
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 14px;
        color: var(--dark);
        opacity: 0.7;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .categories-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
    }

    @media (max-width: 992px) {
        .categories-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .page-header h1 {
            font-size: 36px;
        }

        .filters-section {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-group {
            width: 100%;
            justify-content: space-between;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
        }
    }

    @media (max-width: 576px) {
        .categories-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .page-header h1 {
            font-size: 28px;
        }

        .page-header p {
            font-size: 16px;
        }
    }

        .categories-page {
            padding: 40px 3%;
        }

        .filters-section {
            padding: 20px;
        }

        .filter-group {
            flex-direction: column;
            align-items: stretch;
        }

        .search-input {
            width: 100%;
            min-width: 100%;
        }

        .filter-btn {
            width: 100%;
            justify-content: center;
        }

        .results-count {
            text-align: center;
            width: 100%;
        }
</style>

<!-- Main Content -->
<main style="min-height: 60vh; padding: 60px 0; background-color: var(--white);">
    <div class="products-container">
        <h1 class="page-title">Danh Mục Sản Phẩm</h1>
        
        <!-- Filter Section -->
        <form method="GET" action="" id="filterForm">
            <div class="filters-section">
                <div class="filter-group">
                    <label for="search">Tìm kiếm:</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           placeholder="Tên danh mục..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           onkeypress="if(event.key === 'Enter') { this.form.submit(); }">
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sắp xếp:</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                        <option value="count_desc" <?php echo $sort == 'count_desc' ? 'selected' : ''; ?>>Nhiều sản phẩm nhất</option>
                        <option value="count_asc" <?php echo $sort == 'count_asc' ? 'selected' : ''; ?>>Ít sản phẩm nhất</option>
                    </select>
                </div>
                
                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i>
                    Lọc
                </button>
            </div>
        </form>
        
        <div class="products-info">
            Tìm thấy: <?php echo $total_categories; ?> danh mục
        </div>

        <?php if (empty($categories)): ?>
            <div class="no-results">
                <i class="fas fa-inbox"></i>
                <h3>Không tìm thấy danh mục nào</h3>
                <p>Xin lỗi, chúng tôi không tìm thấy danh mục nào phù hợp với tìm kiếm của bạn.</p>
                <a href="categories.php" class="view-btn" style="margin-top: 15px;">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách
                </a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (isset($category['image'])): ?>
                                <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['category_name']); ?>">
                            <?php else: ?>
                                <?php 
                                $category_name = $category['category_name'];
                                $first_letter = mb_substr($category_name, 0, 1, 'UTF-8');
                                ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; font-size: 48px; font-weight: bold;">
                                    <?php echo $first_letter; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                            <p class="product-description">
                                <?php echo htmlspecialchars($category['description'] ?? ''); ?>
                            </p>
                            <div class="product-meta">
                                <span class="product-count">
                                    <i class="fas fa-leaf"></i>
                                    <?php echo (int) ($category['product_count'] ?? 0); ?> sản phẩm
                                </span>
                                <a href="products.php?category=<?php echo isset($category['slug']) ? htmlspecialchars($category['slug']) : ''; ?>" class="view-btn">
                                    Xem thêm
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): 
        // Tạo query string cho phân trang
        $query_params = [];
        if (!empty($search)) $query_params['search'] = $search;
        if ($sort != 'name_asc') $query_params['sort'] = $sort;
        $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
    ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>" class="prev">
                    <i class="fas fa-chevron-left"></i> Trước
                </a>
            <?php else: ?>
                <span class="disabled">
                    <i class="fas fa-chevron-left"></i> Trước
                </span>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1): ?>
                <a href="?page=1<?php echo $query_string; ?>">1</a>
                <?php if ($start_page > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages; ?><?php echo $query_string; ?>"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>" class="next">
                    Sau <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled">
                    Sau <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>

