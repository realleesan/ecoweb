<?php include '../includes/header.php'; ?>

<style>
    /* Products Page Styles */
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
        height: 192px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .product-image-placeholder {
        color: #999;
        font-size: 14px;
    }


    .product-info {
        padding: 20px;
    }

    .product-name {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 18px;
        color: var(--dark);
        margin-bottom: 10px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 54px;
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
        color: #D26426;
        margin-bottom: 10px;
    }

    .product-description {
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
        line-height: 1.5;
    }

    .add-to-cart-btn {
        width: 100%;
        padding: 12px;
        background-color: #3C603C;
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
        background-color: #3C603C;
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
        background-color: #D26426;
        color: var(--white);
    }

    @media (max-width: 1200px) {
        .products-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
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

    @media (max-width: 480px) {
        .products-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Main Content -->
<main style="min-height: 60vh; padding: 60px 0; background-color: var(--white);">
    <div class="products-container">
        <h1 class="page-title">Sản Phẩm</h1>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filter-group">
                <label for="category-filter">Danh mục:</label>
                <select id="category-filter" onchange="applyFilters()">
                    <option value="">Tất cả</option>
                    <option value="cay-trong">Cây trồng</option>
                    <option value="to-ong">Tổ ong</option>
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

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Pagination will be generated by JavaScript -->
        </div>
    </div>
</main>

<script>
    // Sample product data
    const products = [
        {
            id: 1,
            code: 'A01',
            name: 'Cây Kèn Hồng',
            price: 100000,
            description: 'Cây Kèn Hồng có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
            category: 'cay-trong'
        },
        {
            id: 2,
            code: 'A02',
            name: 'Cây Hoàng Nam',
            price: 200000,
            description: 'Cây Hoàng Nam có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
            category: 'cay-trong'
        },
        {
            id: 3,
            code: 'A03',
            name: 'Cây Táo',
            price: 300000,
            description: 'Cây Táo có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
            category: 'cay-trong'
        },
        {
            id: 4,
            code: 'A04',
            name: 'Cây Bưởi',
            price: 400000,
            description: 'Cây Bưởi có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
            category: 'cay-trong'
        },
        {
            id: 5,
            code: 'A05',
            name: 'Cây Chanh Leo',
            price: 500000,
            description: 'Cây Chanh Dây có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
            category: 'cay-trong'
        },
        {
            id: 6,
            code: 'A06',
            name: 'Cây Xoài',
            price: 600000,
            description: 'Cây Xoài có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
            category: 'cay-trong'
        },
        {
            id: 7,
            code: 'A07',
            name: 'Tổ Ong',
            price: 700000,
            description: 'Tổ ong có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
            category: 'to-ong'
        },
        {
            id: 8,
            code: 'A08',
            name: 'Cây Sung',
            price: 800000,
            description: 'Cây Sung có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
            category: 'cay-trong'
        }
    ];

    // Configuration
    const PRODUCTS_PER_PAGE = 16;
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
        const categories = {
            'cay-trong': 'Cây trồng',
            'to-ong': 'Tổ ong'
        };
        return categories[category] || category;
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
            // Always show the count badge
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
        const startIndex = (currentPage - 1) * PRODUCTS_PER_PAGE;
        const endIndex = startIndex + PRODUCTS_PER_PAGE;
        const pageProducts = filteredProducts.slice(startIndex, endIndex);

        grid.innerHTML = pageProducts.map(product => renderProductCard(product)).join('');

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
        
        // Update cart count on page load
        updateCartCount();
    });
</script>

<?php include '../includes/footer.php'; ?>

