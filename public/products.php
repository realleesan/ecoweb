<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

$categories = [];
$products = [];

if ($pdo) {
    try {
        $categoryStmt = $pdo->query('SELECT category_id, category_name, slug FROM categories ORDER BY category_name ASC');
        $categories = $categoryStmt->fetchAll();

        $productStmt = $pdo->query('SELECT p.product_id, p.code, p.name, p.price, p.short_description, p.category_id, c.slug AS category_slug, c.category_name
                                    FROM products p
                                    INNER JOIN categories c ON p.category_id = c.category_id
                                    ORDER BY p.created_at DESC');
        $products = $productStmt->fetchAll();
    } catch (PDOException $e) {
        $categories = [];
        $products = [];
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

$categoryMap = [];
foreach ($categories as $category) {
    $categoryMap[$category['slug']] = $category['category_name'];
}

include '../includes/header.php';
?>

<style>
    /* Products Page Styles */
    .products-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 0 auto;
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
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

    .filter-group select {
        padding: 8px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        color: var(--dark);
        background-color: var(--white);
        cursor: pointer;
    }

    .filter-group select:focus {
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
        gap: <?php echo GRID_GAP_SMALL; ?>;
        margin-bottom: 40px;
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
        font-family: 'Poppins', sans-serif;
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
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 20px;
        color: var(--secondary);
        margin-bottom: 8px;
    }

    .product-description {
        font-family: 'Poppins', sans-serif;
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
        font-family: 'Poppins', sans-serif;
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
        font-family: 'Poppins', sans-serif;
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

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 40px;
    }

    .pagination a,
    .pagination span {
        padding: 10px 15px;
        border-radius: 5px;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s ease;
        min-width: 40px;
        text-align: center;
    }

    .pagination a {
        background-color: #e0e0e0;
        color: var(--dark);
    }

    .pagination a:hover {
        background-color: #d0d0d0;
    }

    .pagination .active {
        background-color: var(--secondary);
        color: var(--white);
    }

    @media (max-width: <?php echo BREAKPOINT_XL; ?>) {
        .products-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .products-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: <?php echo GRID_GAP_SMALL; ?>;
        }

        .filters-section {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-group {
            width: 100%;
            justify-content: space-between;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_XS; ?>) {
        .products-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Main Content -->
<main style="min-height: 60vh; padding: 60px 0; background-color: var(--light);">
    <div class="products-container">
        <h1 class="page-title">Sản Phẩm</h1>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filter-group">
                <label for="category-filter">Danh mục:</label>
                <select id="category-filter" onchange="applyFilters()">
                    <option value="">Tất cả</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['slug']); ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="price-sort">Sắp xếp theo giá:</label>
                <select id="price-sort" onchange="applyFilters()">
                    <option value="">Mặc định</option>
                    <option value="low-to-high">Từ thấp đến cao</option>
                    <option value="high-to-low">Từ cao đến thấp</option>
                </select>
            </div>
        </div>

        <!-- Products Info -->
        <div class="products-info" id="products-info">
            Hiển thị <span id="display-count">8</span> trên tổng số <span id="total-count">8</span> sản phẩm
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="products-grid">
            <!-- Products will be generated by JavaScript -->
        </div>
        <div id="no-products" style="display: none; text-align: center; padding: 40px; color: var(--dark);">
            <i class="fas fa-box-open" style="font-size: 48px; color: var(--secondary); margin-bottom: 20px;"></i>
            <h3>Không có sản phẩm nào</h3>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Pagination will be generated by JavaScript -->
        </div>
    </div>
</main>

<script>
    const products = <?php echo json_encode($productData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const categoryMap = <?php echo json_encode($categoryMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    // Configuration
    const PRODUCTS_PER_PAGE = <?php echo PAGINATION_PRODUCTS_PER_PAGE; ?>;
    let currentPage = 1;
    let filteredProducts = [...products];

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

    // Get category name
    function getCategoryName(category) {
        if (categoryMap.hasOwnProperty(category)) {
            return categoryMap[category];
        }
        return category;
    }

    // Add to cart function
    function handleAddToCart(productId, productName) {
        // Check if user is logged in
        fetch('<?php echo BASE_URL; ?>/api/get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                // If not logged in, redirect to login
                if (!data.success && data.message) {
                    if (confirm('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng. Bạn có muốn đăng nhập không?')) {
                        window.location.href = '<?php echo BASE_URL; ?>/auth/login.php';
                    }
                    return;
                }
                
                // Add to cart
                return fetch('<?php echo BASE_URL; ?>/api/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });
            })
            .then(response => response ? response.json() : null)
            .then(data => {
                if (data && data.success) {
                    showToast(data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
                    // Update cart count
                    updateCartCount();
                } else if (data && !data.success) {
                    if (data.message && data.message.includes('đăng nhập')) {
                        if (confirm('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng. Bạn có muốn đăng nhập không?')) {
                            window.location.href = '<?php echo BASE_URL; ?>/auth/login.php';
                        }
                    } else {
                        alert(data.message || 'Có lỗi xảy ra');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng');
            });
    }

    function updateCartCount() {
        fetch('<?php echo BASE_URL; ?>/api/get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.count;
                        if (data.count > 0) {
                            cartCountElement.style.display = 'flex';
                        } else {
                            cartCountElement.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching cart count:', error);
            });
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
        const emptyState = document.getElementById('no-products');
        const startIndex = (currentPage - 1) * PRODUCTS_PER_PAGE;
        const endIndex = startIndex + PRODUCTS_PER_PAGE;
        const pageProducts = filteredProducts.slice(startIndex, endIndex);

        grid.innerHTML = pageProducts.map(product => renderProductCard(product)).join('');

        if (pageProducts.length === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }

        // Update products info
        document.getElementById('display-count').textContent = pageProducts.length;
        document.getElementById('total-count').textContent = filteredProducts.length;

        // Render pagination
        renderPagination();
    }

    // Render pagination
    function renderPagination() {
        const totalPages = Math.ceil(filteredProducts.length / PRODUCTS_PER_PAGE);
        const pagination = document.getElementById('pagination');

        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let paginationHTML = '';

        // Previous button
        if (currentPage > 1) {
            paginationHTML += `<a href="#" onclick="changePage(${currentPage - 1}); return false;">«</a>`;
        }

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                paginationHTML += `<span class="active">${i}</span>`;
            } else {
                paginationHTML += `<a href="#" onclick="changePage(${i}); return false;">${i}</a>`;
            }
        }

        // Next button
        if (currentPage < totalPages) {
            paginationHTML += `<a href="#" onclick="changePage(${currentPage + 1}); return false;">»</a>`;
        }

        pagination.innerHTML = paginationHTML;
    }

    // Change page
    function changePage(page) {
        currentPage = page;
        renderProducts();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Apply filters
    function applyFilters() {
        const categoryFilter = document.getElementById('category-filter').value;
        const priceSort = document.getElementById('price-sort').value;

        // Filter by category
        filteredProducts = products.filter(product => {
            if (!categoryFilter) return true;
            return product.category === categoryFilter;
        });

        // Sort by price
        if (priceSort === 'low-to-high') {
            filteredProducts.sort((a, b) => a.price - b.price);
        } else if (priceSort === 'high-to-low') {
            filteredProducts.sort((a, b) => b.price - a.price);
        }

        // Reset to first page
        currentPage = 1;

        // Render products
        renderProducts();
    }

    // Search functionality (integrate with header search bar)
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-bar input');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                if (searchTerm === '') {
                    filteredProducts = [...products];
                } else {
                    filteredProducts = products.filter(product => {
                        return product.name.toLowerCase().includes(searchTerm) ||
                               product.code.toLowerCase().includes(searchTerm);
                    });
                }

                currentPage = 1;
                renderProducts();
            });
        }

        // Initial render
        renderProducts();
        
        // Update cart count on page load (if logged in)
        <?php 
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): 
        ?>
        updateCartCount();
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>

