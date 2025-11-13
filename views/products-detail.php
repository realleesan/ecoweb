<?php
require_once '../includes/database.php';

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0 || !$pdo) {
    header('Location: ../public/products.php');
    exit;
}

$productStmt = $pdo->prepare('SELECT p.*, c.category_name, c.slug AS category_slug
                              FROM products p
                              INNER JOIN categories c ON p.category_id = c.category_id
                              WHERE p.product_id = :id');
$productStmt->bindValue(':id', $product_id, PDO::PARAM_INT);
$productStmt->execute();
$product = $productStmt->fetch();

if (!$product) {
    header('Location: ../public/products.php');
    exit;
}

$tagStmt = $pdo->prepare('SELECT tag FROM product_tags WHERE product_id = :id ORDER BY tag ASC');
$tagStmt->bindValue(':id', $product_id, PDO::PARAM_INT);
$tagStmt->execute();
$product_tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<style>
    /* Product Detail Page Styles */
    .product-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 5%;
        background-color: var(--white);
    }

    /* Page Title */
    .page-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 48px;
        color: var(--primary);
        text-align: center;
        margin-bottom: 15px;
    }

    /* Breadcrumb */
    .breadcrumb {
        text-align: center;
        margin-bottom: 40px;
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 16px;
        color: var(--dark);
    }

    .breadcrumb a {
        color: var(--primary);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .breadcrumb a:hover {
        color: var(--secondary);
    }

    .breadcrumb span {
        margin: 0 10px;
        color: #999;
    }

    /* Product Detail Grid */
    .product-detail-grid {
        display: grid;
        grid-template-columns: 40% 60%;
        gap: 40px;
        margin-bottom: 60px;
    }

    /* Image Column */
    .product-images {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .main-image {
        width: 100%;
        height: 500px;
        background-color: #e0e0e0;
        border: 2px solid #ddd;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
    }

    .main-image-placeholder {
        color: #999;
        font-size: 16px;
        text-align: center;
    }

    .thumbnail-images {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .thumbnail {
        width: 100%;
        height: 100px;
        background-color: #e0e0e0;
        border: 2px solid #ddd;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #999;
    }

    .thumbnail:hover {
        border-color: var(--primary);
        transform: scale(1.05);
    }

    .thumbnail.active {
        border-color: var(--secondary);
        border-width: 3px;
    }

    /* Info Column */
    .product-info-section {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .bestseller-tag {
        display: inline-block;
        background-color: #FFD700;
        color: #74493D;
        padding: 6px 15px;
        border-radius: 20px;
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .product-name-h2 {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 32px;
        color: var(--primary);
        margin: 0;
    }

    .product-rating {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .stars {
        display: flex;
        gap: 3px;
    }

    .star {
        color: #ddd;
        font-size: 20px;
    }

    .star.filled {
        color: #D26426;
    }

    .rating-count {
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: #666;
    }

    .product-price-large {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 36px;
        color: #D26426;
        margin: 10px 0;
    }

    .product-short-description {
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 16px;
        color: var(--dark);
        line-height: 1.6;
        margin: 15px 0;
    }

    .product-short-description strong {
        font-weight: 600;
        color: var(--primary);
    }

    /* Quantity Selector */
    .quantity-section {
        display: flex;
        align-items: center;
        gap: 20px;
        margin: 20px 0;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        border: 2px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
    }

    .quantity-btn {
        background-color: #f5f5f5;
        border: none;
        width: 40px;
        height: 45px;
        cursor: pointer;
        font-size: 20px;
        color: var(--dark);
        transition: background-color 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quantity-btn:hover {
        background-color: #e0e0e0;
    }

    .quantity-input {
        width: 60px;
        height: 45px;
        border: none;
        text-align: center;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 16px;
        outline: none;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin: 20px 0;
    }

    .add-to-cart-btn {
        flex: 1;
        padding: 15px 30px;
        background-color: #BC935A;
        color: var(--white);
        border: none;
        border-radius: 5px;
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: background-color 0.3s ease;
    }

    .add-to-cart-btn:hover {
        background-color: #a67d4a;
    }

    .favorite-btn {
        width: 50px;
        height: 50px;
        border: 2px solid #ddd;
        background-color: var(--white);
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #999;
        transition: all 0.3s ease;
    }

    .favorite-btn:hover {
        border-color: #D26426;
        color: #D26426;
    }

    .favorite-btn.active {
        border-color: #D26426;
        color: #D26426;
        background-color: #fff5f0;
    }

    /* Technical Info */
    .technical-info {
        background-color: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        margin-top: 30px;
    }

    .technical-info h3 {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 20px;
        color: var(--primary);
        margin-bottom: 20px;
    }

    .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 14px;
    }

    .info-label {
        font-weight: 600;
        color: var(--dark);
        min-width: 120px;
    }

    .info-value {
        color: #666;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .category-tag {
        display: inline-block;
        background-color: var(--primary);
        color: var(--white);
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
    }

    .authentic-tag {
        display: inline-block;
        background-color: #3C603C;
        color: var(--white);
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
        margin-left: 10px;
    }

    .status-in-stock {
        color: #3C603C;
        font-weight: 600;
    }

    .product-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .tag {
        display: inline-block;
        background-color: #e0e0e0;
        color: var(--dark);
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    /* Tabs Section */
    .tabs-section {
        margin-top: 60px;
        border-top: 2px solid #e0e0e0;
        padding-top: 30px;
    }

    .tabs-header {
        display: flex;
        gap: 30px;
        border-bottom: 2px solid #e0e0e0;
        margin-bottom: 30px;
    }

    .tab-button {
        background: none;
        border: none;
        padding: 15px 0;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 16px;
        color: #999;
        cursor: pointer;
        position: relative;
        transition: color 0.3s ease;
    }

    .tab-button:hover {
        color: var(--primary);
    }

    .tab-button.active {
        color: var(--primary);
        font-weight: 700;
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background-color: #D26426;
    }

    .tab-content {
        display: none;
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 16px;
        color: var(--dark);
        line-height: 1.8;
        padding: 20px 0;
    }

    .tab-content.active {
        display: block;
    }

    .tab-content p {
        margin-bottom: 15px;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .product-detail-grid {
            grid-template-columns: 1fr;
        }

        .main-image {
            height: 400px;
        }

        .page-title {
            font-size: 36px;
        }

        .product-name-h2 {
            font-size: 28px;
        }
    }

    @media (max-width: 768px) {
        .thumbnail-images {
            grid-template-columns: repeat(3, 1fr);
        }

        .action-buttons {
            flex-direction: column;
        }

        .favorite-btn {
            width: 100%;
        }
    }
</style>

<?php
function formatPrice(float $price): string
{
    return number_format($price, 0, ',', '.') . ' đ';
}

function renderStars(float $rating): string
{
    $fullStars = (int) floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

    $html = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<span class="star filled">★</span>';
    }
    if ($hasHalfStar) {
        $html .= '<span class="star filled">★</span>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<span class="star">★</span>';
    }

    return $html;
}
?>

<!-- Main Content -->
<main style="min-height: 60vh; padding: 60px 0; background-color: var(--white);">
    <div class="product-detail-container">
        <!-- Page Title -->
        <h1 class="page-title"><?php echo strtoupper(htmlspecialchars($product['name'])); ?></h1>
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../index.php">Trang Chủ</a>
            <span>/</span>
            <span><?php echo strtoupper(htmlspecialchars($product['name'])); ?></span>
        </div>

        <!-- Product Detail Grid -->
        <div class="product-detail-grid">
            <!-- Column 1: Images -->
            <div class="product-images">
                <div class="main-image" id="main-image">
                    <div class="main-image-placeholder">Hình ảnh sản phẩm chính</div>
                </div>
                <div class="thumbnail-images">
                    <div class="thumbnail active" onclick="changeMainImage(0)">
                        <span>Hình 1</span>
                    </div>
                    <div class="thumbnail" onclick="changeMainImage(1)">
                        <span>Hình 2</span>
                    </div>
                    <div class="thumbnail" onclick="changeMainImage(2)">
                        <span>Hình 3</span>
                    </div>
                    <div class="thumbnail" onclick="changeMainImage(3)">
                        <span>Hình 4</span>
                    </div>
                </div>
            </div>

            <!-- Column 2: Info & Actions -->
            <div class="product-info-section">
                <?php if (!empty($product['is_bestseller'])): ?>
                <div>
                    <span class="bestseller-tag">Bán Chạy</span>
                </div>
                <?php endif; ?>

                <!-- Product Name -->
                <h2 class="product-name-h2"><?php echo htmlspecialchars($product['name']); ?></h2>

                <!-- Rating -->
                <div class="product-rating">
                    <div class="stars">
                        <?php echo renderStars((float) $product['rating']); ?>
                    </div>
                    <span class="rating-count"><?php echo (int) $product['reviews_count']; ?> đánh giá</span>
                </div>

                <!-- Price -->
                <div class="product-price-large"><?php echo formatPrice((float) $product['price']); ?></div>

                <!-- Short Description -->
                <div class="product-short-description">
                    <strong>Mô tả ngắn:</strong><br>
                    <?php echo nl2br(htmlspecialchars($product['short_description'] ?? '')); ?>
                </div>

                <!-- Quantity Selector & Action Buttons -->
                <div class="quantity-section">
                    <div class="quantity-selector">
                        <button class="quantity-btn" onclick="decreaseQuantity()">-</button>
                        <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo max(1, (int) $product['stock']); ?>">
                        <button class="quantity-btn" onclick="increaseQuantity()">+</button>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="add-to-cart-btn" onclick="addToCart()">
                        <i class="fas fa-shopping-cart"></i>
                        THÊM VÀO GIỎ HÀNG
                    </button>
                    <button class="favorite-btn" id="favorite-btn" onclick="toggleFavorite()">
                        <i class="far fa-heart"></i>
                    </button>
                </div>

                <!-- Technical Info -->
                <div class="technical-info">
                    <h3>Thông tin kỹ thuật</h3>
                    <div class="info-item">
                        <span class="info-label">Danh mục:</span>
                        <span class="info-value">
                            <span class="category-tag"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <span class="authentic-tag">Chính hãng</span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mã Sản Phẩm:</span>
                        <span class="info-value"><?php echo htmlspecialchars($product['code']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tình Trạng:</span>
                        <span class="info-value status-in-stock">Còn hàng (<?php echo (int) $product['stock']; ?> sản phẩm)</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tags:</span>
                        <div class="info-value">
                            <div class="product-tags">
                                <?php if (!empty($product_tags)): ?>
                                    <?php foreach ($product_tags as $tag): ?>
                                        <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="tag">Đang cập nhật</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Section -->
        <div class="tabs-section">
            <div class="tabs-header">
                <button class="tab-button active" onclick="switchTab('description')">Mô tả sản phẩm</button>
                <button class="tab-button" onclick="switchTab('specifications')">Thông số kỹ thuật</button>
                <button class="tab-button" onclick="switchTab('reviews')">Đánh giá (<?php echo (int) $product['reviews_count']; ?>)</button>
            </div>

            <div id="tab-description" class="tab-content active">
                <p><?php echo nl2br(htmlspecialchars($product['full_description'] ?? '')); ?></p>
            </div>

            <div id="tab-specifications" class="tab-content">
                <p><strong>Kích thước:</strong> Đang cập nhật</p>
                <p><strong>Màu sắc:</strong> Đang cập nhật</p>
                <p><strong>Chất liệu:</strong> Đang cập nhật</p>
                <p><strong>Xuất xứ:</strong> Việt Nam</p>
                <p><strong>Bảo hành:</strong> 12 tháng</p>
                <p><strong>Hướng dẫn sử dụng:</strong> Trồng ở nơi có ánh sáng mặt trời, tưới nước đều đặn.</p>
            </div>

            <div id="tab-reviews" class="tab-content">
                <p>Hiện tại có <?php echo (int) $product['reviews_count']; ?> đánh giá cho sản phẩm này.</p>
                <p>Đánh giá trung bình: <?php echo number_format((float) $product['rating'], 1); ?>/5.0</p>
                <p style="margin-top: 20px; color: #999; font-style: italic;">Chức năng đánh giá chi tiết sẽ được cập nhật sau.</p>
            </div>
        </div>
    </div>
</main>

<script>
    // Change main image
    function changeMainImage(index) {
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach((thumb, i) => {
            if (i === index) {
                thumb.classList.add('active');
            } else {
                thumb.classList.remove('active');
            }
        });
        // In a real application, you would change the main image source here
    }

    // Quantity controls
    function increaseQuantity() {
        const input = document.getElementById('quantity');
        const max = parseInt(input.getAttribute('max'));
        const current = parseInt(input.value);
        if (current < max) {
            input.value = current + 1;
        }
    }

    function decreaseQuantity() {
        const input = document.getElementById('quantity');
        const current = parseInt(input.value);
        if (current > 1) {
            input.value = current - 1;
        }
    }

    // Add to cart
    function addToCart() {
        const quantity = document.getElementById('quantity').value;
        const productId = <?php echo (int) $product['product_id']; ?>;
        // In a real application, you would send this to the server
        alert('Đã thêm ' + quantity + ' sản phẩm vào giỏ hàng!');
    }

    // Toggle favorite
    function toggleFavorite() {
        const btn = document.getElementById('favorite-btn');
        const icon = btn.querySelector('i');
        if (btn.classList.contains('active')) {
            btn.classList.remove('active');
            icon.classList.remove('fas');
            icon.classList.add('far');
        } else {
            btn.classList.add('active');
            icon.classList.remove('far');
            icon.classList.add('fas');
        }
    }

    // Switch tabs
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });

        // Show selected tab content
        document.getElementById('tab-' + tabName).classList.add('active');

        // Add active class to clicked button
        event.target.classList.add('active');
    }
</script>

<?php include '../includes/footer.php'; ?>

