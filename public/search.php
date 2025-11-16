<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, products, categories, news

$products = [];
$categories = [];
$news = [];
$productCount = 0;
$categoryCount = 0;
$newsCount = 0;
$totalResults = 0;

if ($pdo && !empty($searchQuery)) {
    try {
        $searchTerm = '%' . $searchQuery . '%';
        
        // Search Products (by name, code, and tags)
        if ($filterType === 'all' || $filterType === 'products') {
            $productStmt = $pdo->prepare('SELECT DISTINCT p.product_id, p.code, p.name, p.price, p.short_description, p.category_id, c.slug AS category_slug, c.category_name
                                        FROM products p
                                        INNER JOIN categories c ON p.category_id = c.category_id
                                        LEFT JOIN product_tags pt ON p.product_id = pt.product_id
                                        WHERE (p.name LIKE :searchTerm OR p.code LIKE :searchTerm OR pt.tag LIKE :searchTerm)
                                        ORDER BY p.created_at DESC');
            $productStmt->execute(['searchTerm' => $searchTerm]);
            $products = $productStmt->fetchAll();
            $productCount = count($products);
        }
        
        // Search Categories (by name)
        if ($filterType === 'all' || $filterType === 'categories') {
            $categoryStmt = $pdo->prepare('SELECT category_id, category_name, slug, description, image
                                          FROM categories
                                          WHERE category_name LIKE :searchTerm
                                          ORDER BY category_name ASC');
            $categoryStmt->execute(['searchTerm' => $searchTerm]);
            $categories = $categoryStmt->fetchAll();
            $categoryCount = count($categories);
        }
        
        // Search News (by title, content, and tags)
        if ($filterType === 'all' || $filterType === 'news') {
            $newsStmt = $pdo->prepare('SELECT DISTINCT n.news_id, n.title, n.slug, n.publish_date, n.excerpt, n.description, n.category
                                      FROM news n
                                      LEFT JOIN news_tags nt ON n.news_id = nt.news_id
                                      WHERE (n.title LIKE :searchTerm OR n.content LIKE :searchTerm OR n.excerpt LIKE :searchTerm OR n.description LIKE :searchTerm OR nt.tag LIKE :searchTerm)
                                      ORDER BY n.publish_date DESC');
            $newsStmt->execute(['searchTerm' => $searchTerm]);
            $news = $newsStmt->fetchAll();
            $newsCount = count($news);
        }
        
        $totalResults = $productCount + $categoryCount + $newsCount;
    } catch (PDOException $e) {
        $products = [];
        $categories = [];
        $news = [];
        $productCount = 0;
        $categoryCount = 0;
        $newsCount = 0;
        $totalResults = 0;
    }
}

$productData = array_map(function ($product) {
    return [
        'id' => (int) $product['product_id'],
        'code' => $product['code'],
        'name' => $product['name'],
        'price' => (float) $product['price'],
        'description' => $product['short_description'],
        'category' => $product['category_slug'],
        'categoryName' => $product['category_name'],
    ];
}, $products);

include '../includes/header.php';
?>

<style>
    /* Search Page Styles */
    .search-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 0 auto;
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        padding-top: 20px;
    }


    .search-header {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 16px;
        color: var(--dark);
        margin-bottom: 30px;
        text-align: center;
    }

    .search-query {
        font-weight: 600;
        color: var(--primary);
    }

    /* Filter Buttons */
    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
    }

    .filter-btn {
        padding: 10px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 5px;
        background-color: var(--white);
        color: var(--dark);
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .filter-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .filter-btn.active {
        background-color: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    /* Results Section */
    .results-section {
        margin-bottom: 40px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e0e0;
    }

    .section-title {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 24px;
        color: var(--primary);
    }

    .section-count {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
    }

    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: <?php echo GRID_GAP_SMALL; ?>;
        margin-bottom: 30px;
    }

    .product-card {
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        height: 160px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .product-image-placeholder {
        color: var(--dark);
        font-size: 14px;
    }

    .product-info {
        padding: 16px;
    }

    .product-name {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 18px;
        color: var(--primary);
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 50px;
    }

    .product-name a {
        color: var(--dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .product-name a:hover {
        color: var(--primary);
    }

    .product-price {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 600;
        font-size: 20px;
        color: var(--secondary);
        margin-bottom: 8px;
    }

    .product-description {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 12px;
        line-height: 1.5;
    }

    .add-to-cart-btn {
        width: 100%;
        padding: 12px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 5px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: background-color 0.3s ease;
    }

    .add-to-cart-btn:hover {
        background-color: #2d4a2d;
    }

    /* Categories Grid */
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: <?php echo GRID_GAP_SMALL; ?>;
        margin-bottom: 30px;
    }

    .category-card {
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .category-image {
        width: 100%;
        height: 160px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .category-info {
        padding: 16px;
    }

    .category-name {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 18px;
        color: var(--primary);
        margin-bottom: 8px;
    }

    .category-name a {
        color: var(--dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .category-name a:hover {
        color: var(--primary);
    }

    .category-description {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 12px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .view-category-btn {
        width: 100%;
        padding: 12px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 5px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: background-color 0.3s ease;
    }

    .view-category-btn:hover {
        background-color: #2d4a2d;
    }

    /* News Grid */
    .news-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: <?php echo GRID_GAP_SMALL; ?>;
        margin-bottom: 30px;
    }

    .news-card {
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .news-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .news-image {
        width: 100%;
        height: 180px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 48px;
    }

    .news-content {
        padding: 16px;
    }

    .news-date {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 12px;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .news-title {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 18px;
        color: var(--primary);
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 50px;
    }

    .news-title a {
        color: var(--dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .news-title a:hover {
        color: var(--primary);
    }

    .news-excerpt {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 12px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .view-news-btn {
        width: 100%;
        padding: 12px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 5px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: background-color 0.3s ease;
    }

    .view-news-btn:hover {
        background-color: #2d4a2d;
    }

    /* Toast notification */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: var(--primary);
        color: var(--white);
        padding: 15px 25px;
        border-radius: 5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 1000;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        display: none;
        animation: slideIn 0.3s ease;
    }

    .toast.show {
        display: block;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .no-results {
        text-align: center;
        padding: 60px 20px;
        color: var(--dark);
    }

    .no-results i {
        font-size: 64px;
        color: var(--secondary);
        margin-bottom: 20px;
    }

    .no-results h3 {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 600;
        font-size: 24px;
        margin-bottom: 10px;
        color: var(--primary);
    }

    .no-results p {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 16px;
        color: var(--dark);
    }

    @media (max-width: <?php echo BREAKPOINT_XL; ?>) {
        .products-grid, .categories-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        .news-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .products-grid, .categories-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: <?php echo GRID_GAP_SMALL; ?>;
        }
        .news-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_XS; ?>) {
        .products-grid, .categories-grid, .news-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Main Content -->
<main style="min-height: 60vh; padding: 0; background-color: var(--light);">
    <?php
    $page_title = "Kết Quả Tìm Kiếm";
    include __DIR__ . '/../includes/components/page-header.php';
    ?>
    
    <div class="search-container">
        <?php if (!empty($searchQuery)): ?>
            <!-- Search Header -->
            <div class="search-header">
                Tìm kiếm cho: "<span class="search-query"><?php echo htmlspecialchars($searchQuery); ?></span>" - Tìm thấy <strong><?php echo $totalResults; ?></strong> kết quả
            </div>

            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <a href="?q=<?php echo urlencode($searchQuery); ?>&filter=all" class="filter-btn <?php echo $filterType === 'all' ? 'active' : ''; ?>">
                    Tất cả (<?php echo $totalResults; ?>)
                </a>
                <a href="?q=<?php echo urlencode($searchQuery); ?>&filter=products" class="filter-btn <?php echo $filterType === 'products' ? 'active' : ''; ?>">
                    Sản phẩm (<?php echo $productCount; ?>)
                </a>
                <a href="?q=<?php echo urlencode($searchQuery); ?>&filter=categories" class="filter-btn <?php echo $filterType === 'categories' ? 'active' : ''; ?>">
                    Danh mục (<?php echo $categoryCount; ?>)
                </a>
                <a href="?q=<?php echo urlencode($searchQuery); ?>&filter=news" class="filter-btn <?php echo $filterType === 'news' ? 'active' : ''; ?>">
                    Tin tức (<?php echo $newsCount; ?>)
                </a>
            </div>

            <?php if ($totalResults > 0): ?>
                <!-- Products Section -->
                <?php if (($filterType === 'all' || $filterType === 'products') && $productCount > 0): ?>
                    <div class="results-section">
                        <div class="section-header">
                            <h2 class="section-title">Sản phẩm</h2>
                            <span class="section-count"><?php echo $productCount; ?>/<?php echo $productCount; ?> kết quả</span>
                        </div>
                        <div class="products-grid" id="products-grid">
                            <!-- Products will be generated by JavaScript -->
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Categories Section -->
                <?php if (($filterType === 'all' || $filterType === 'categories') && $categoryCount > 0): ?>
                    <div class="results-section">
                        <div class="section-header">
                            <h2 class="section-title">Danh mục</h2>
                            <span class="section-count"><?php echo $categoryCount; ?>/<?php echo $categoryCount; ?> kết quả</span>
                        </div>
                        <div class="categories-grid">
                            <?php foreach ($categories as $category): ?>
                                <div class="category-card">
                                    <div class="category-image">
                                        <?php if (!empty($category['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['category_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; font-size: 48px; font-weight: bold;">
                                                <?php echo mb_substr($category['category_name'], 0, 1, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="category-info">
                                        <h3 class="category-name">
                                            <a href="products.php?category=<?php echo htmlspecialchars($category['slug']); ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </a>
                                        </h3>
                                        <p class="category-description">
                                            <?php echo htmlspecialchars($category['description'] ?? ''); ?>
                                        </p>
                                        <a href="products.php?category=<?php echo htmlspecialchars($category['slug']); ?>" class="view-category-btn">
                                            XEM DANH MỤC
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- News Section -->
                <?php if (($filterType === 'all' || $filterType === 'news') && $newsCount > 0): ?>
                    <div class="results-section">
                        <div class="section-header">
                            <h2 class="section-title">Tin tức</h2>
                            <span class="section-count"><?php echo $newsCount; ?>/<?php echo $newsCount; ?> kết quả</span>
                        </div>
                        <div class="news-grid">
                            <?php foreach ($news as $item): ?>
                                <div class="news-card">
                                    <div class="news-image">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <div class="news-content">
                                        <div class="news-date">
                                            <i class="far fa-calendar"></i>
                                            <?php echo date('d/m/Y', strtotime($item['publish_date'])); ?>
                                        </div>
                                        <h3 class="news-title">
                                            <a href="../views/news-detail.php?id=<?php echo $item['news_id']; ?>">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </h3>
                                        <p class="news-excerpt">
                                            <?php echo htmlspecialchars($item['excerpt'] ?? $item['description'] ?? ''); ?>
                                        </p>
                                        <a href="../views/news-detail.php?id=<?php echo $item['news_id']; ?>" class="view-news-btn">
                                            XEM TIN TỨC
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- No Results -->
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Không tìm thấy kết quả</h3>
                    <p>Vui lòng thử lại với từ khóa khác</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- No Search Query -->
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Vui lòng nhập từ khóa tìm kiếm</h3>
                <p>Hãy sử dụng thanh tìm kiếm ở trên để tìm kiếm</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    const products = <?php echo json_encode($productData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    // Format price
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + ' đ';
    }

    // Truncate description to 8 words
    function truncateDescription(text) {
        const words = text.split(' ');
        if (words.length <= 8) return text;
        return words.slice(0, 8).join(' ') + '...';
    }

    // Cart management
    function getCart() {
        const cart = localStorage.getItem('cart');
        return cart ? JSON.parse(cart) : [];
    }

    function saveCart(cart) {
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount();
    }

    function addToCart(productId, productName) {
        const cart = getCart();
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: productId,
                name: productName,
                quantity: 1
            });
        }
        
        saveCart(cart);
        showToast('Đã thêm sản phẩm vào giỏ hàng!');
    }

    // Wrapper function for onclick handler
    function handleAddToCart(productId, productName) {
        addToCart(productId, productName);
    }

    function updateCartCount() {
        const cart = getCart();
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const cartCountElement = document.querySelector('.cart-icon span');
        if (cartCountElement) {
            cartCountElement.textContent = totalItems;
            cartCountElement.style.display = 'flex';
        }
    }

    function showToast(message) {
        let toast = document.getElementById('toast-notification');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast-notification';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Render product card
    function renderProductCard(product) {
        return `
            <div class="product-card">
                <div class="product-image">
                    <div class="product-image-placeholder">Hình ảnh sản phẩm</div>
                </div>
                <div class="product-info">
                    <h3 class="product-name">
                        <a href="../views/products-detail.php?id=${product.id}">${product.name}</a>
                    </h3>
                    <div class="product-price">${formatPrice(product.price)}</div>
                    <p class="product-description">${truncateDescription(product.description)}</p>
                    <button class="add-to-cart-btn" onclick="handleAddToCart(${product.id}, '${product.name.replace(/'/g, "\\'").replace(/"/g, '&quot;')}')">
                        THÊM VÀO GIỎ HÀNG
                    </button>
                </div>
            </div>
        `;
    }

    // Render products grid
    function renderProducts() {
        const grid = document.getElementById('products-grid');
        if (!grid || products.length === 0) return;

        grid.innerHTML = products.map(product => renderProductCard(product)).join('');
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (products.length > 0) {
            renderProducts();
        }
        updateCartCount();
    });
</script>

<?php include '../includes/footer.php'; ?>
