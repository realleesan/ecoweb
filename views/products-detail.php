<?php include '../includes/header.php'; ?>

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
// Sample product data (same as in products.php)
$products = [
    [
        'id' => 1,
        'code' => 'A01',
        'name' => 'Cây Kèn Hồng',
        'price' => 100000,
        'description' => 'Cây Kèn Hồng có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
        'full_description' => 'Cây Kèn Hồng là loại cây cảnh đẹp, có hoa màu hồng rực rỡ. Cây có khả năng tạo bóng mát tốt, giúp thanh lọc không khí và tạo không gian xanh mát cho ngôi nhà của bạn. Cây dễ trồng, phù hợp với nhiều loại đất và khí hậu khác nhau. Ngoài ra, cây còn có tác dụng tốt cho sức khỏe, giúp giảm căng thẳng và tạo không gian thư giãn.',
        'category' => 'cay-trong',
        'category_name' => 'Cây trồng',
        'stock' => 50,
        'rating' => 4.5,
        'reviews_count' => 12,
        'tags' => ['Tuổi đời dài', 'Cây cảnh', 'Tạo bóng mát']
    ],
    [
        'id' => 2,
        'code' => 'A02',
        'name' => 'Cây Hoàng Nam',
        'price' => 200000,
        'description' => 'Cây Hoàng Nam có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
        'full_description' => 'Cây Hoàng Nam là loại cây cảnh quý, có hình dáng đẹp và tán lá xanh mướt. Cây có khả năng tạo bóng mát rất tốt, phù hợp trồng trong sân vườn hoặc công viên. Cây có tuổi thọ cao, dễ chăm sóc và phát triển nhanh.',
        'category' => 'cay-trong',
        'category_name' => 'Cây trồng',
        'stock' => 30,
        'rating' => 4.8,
        'reviews_count' => 8,
        'tags' => ['Tuổi đời dài', 'Cây cảnh', 'Tạo bóng mát']
    ],
    [
        'id' => 3,
        'code' => 'A03',
        'name' => 'Cây Táo',
        'price' => 300000,
        'description' => 'Cây Táo có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
        'full_description' => 'Cây Táo là loại cây ăn quả phổ biến, cho trái ngon và bổ dưỡng. Cây có khả năng tạo bóng mát tốt, phù hợp trồng trong vườn nhà. Trái táo chứa nhiều vitamin và chất xơ, rất tốt cho sức khỏe. Cây dễ trồng, chịu được nhiều loại đất và khí hậu.',
        'category' => 'cay-trong',
        'category_name' => 'Cây trồng',
        'stock' => 25,
        'rating' => 4.7,
        'reviews_count' => 15,
        'tags' => ['Cây ăn quả', 'Tạo bóng mát', 'Dễ trồng']
    ],
    [
        'id' => 4,
        'code' => 'A04',
        'name' => 'Cây Bưởi',
        'price' => 400000,
        'description' => 'Cây Bưởi có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
        'full_description' => 'Cây Bưởi là loại cây ăn quả có giá trị kinh tế cao. Cây cho trái to, ngon và nhiều nước. Bưởi chứa nhiều vitamin C, rất tốt cho sức khỏe. Cây có tán rộng, tạo bóng mát tốt cho sân vườn.',
        'category' => 'cay-trong',
        'category_name' => 'Cây trồng',
        'stock' => 20,
        'rating' => 4.6,
        'reviews_count' => 10,
        'tags' => ['Cây ăn quả', 'Giá trị cao', 'Tạo bóng mát']
    ],
    [
        'id' => 5,
        'code' => 'A05',
        'name' => 'Cây Chanh Leo',
        'price' => 500000,
        'description' => 'Cây Chanh Dây có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
        'full_description' => 'Cây Chanh Leo là loại cây leo, cho trái chanh leo thơm ngon và bổ dưỡng. Trái chanh leo chứa nhiều vitamin và chất chống oxy hóa. Cây có thể leo giàn, tạo bóng mát và cho trái quanh năm.',
        'category' => 'cay-trong',
        'category_name' => 'Cây trồng',
        'stock' => 15,
        'rating' => 4.9,
        'reviews_count' => 20,
        'tags' => ['Cây leo', 'Cây ăn quả', 'Dễ trồng']
    ],
    [
        'id' => 6,
        'code' => 'A06',
        'name' => 'Cây Xoài',
        'price' => 600000,
        'description' => 'Cây Xoài có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
        'full_description' => 'Cây Xoài là loại cây ăn quả nhiệt đới, cho trái xoài thơm ngon. Cây có tán rộng, tạo bóng mát tốt. Xoài chứa nhiều vitamin A và C, rất tốt cho sức khỏe. Cây phù hợp trồng trong vườn nhà.',
        'category' => 'cay-trong',
        'category_name' => 'Cây trồng',
        'stock' => 18,
        'rating' => 4.5,
        'reviews_count' => 14,
        'tags' => ['Cây ăn quả', 'Nhiệt đới', 'Tạo bóng mát']
    ],
    [
        'id' => 7,
        'code' => 'A07',
        'name' => 'Tổ Ong',
        'price' => 700000,
        'description' => 'Tổ ong có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
        'full_description' => 'Tổ Ong là sản phẩm tự nhiên từ ong mật, chứa nhiều dưỡng chất quý giá. Mật ong từ tổ ong có vị ngọt tự nhiên, chứa nhiều vitamin và khoáng chất. Tổ ong có thể sử dụng để làm thuốc và thực phẩm bổ dưỡng.',
        'category' => 'to-ong',
        'category_name' => 'Tổ ong',
        'stock' => 12,
        'rating' => 5.0,
        'reviews_count' => 25,
        'tags' => ['Tổ ong', 'Tự nhiên', 'Bổ dưỡng']
    ],
    [
        'id' => 8,
        'code' => 'A08',
        'name' => 'Cây Sung',
        'price' => 800000,
        'description' => 'Cây Sung có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt',
        'full_description' => 'Cây Sung là loại cây cảnh đẹp, có lá xanh mướt và tạo bóng mát tốt. Cây có tuổi thọ cao, dễ chăm sóc. Sung còn có thể cho trái, trái sung có vị ngọt và bổ dưỡng. Cây phù hợp trồng trong sân vườn hoặc công viên.',
        'category' => 'cay-trong',
        'category_name' => 'Cây trồng',
        'stock' => 22,
        'rating' => 4.4,
        'reviews_count' => 11,
        'tags' => ['Tuổi đời dài', 'Cây cảnh', 'Cây ăn quả']
    ]
];

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Find product by ID
$product = null;
foreach ($products as $p) {
    if ($p['id'] == $product_id) {
        $product = $p;
        break;
    }
}

// If product not found, redirect to products page
if (!$product) {
    header('Location: ../public/products.php');
    exit;
}

// Format price
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' đ';
}

// Render stars
function renderStars($rating) {
    $fullStars = floor($rating);
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
                <!-- Bestseller Tag -->
                <div>
                    <span class="bestseller-tag">Bán Chạy</span>
                </div>

                <!-- Product Name -->
                <h2 class="product-name-h2"><?php echo htmlspecialchars($product['name']); ?></h2>

                <!-- Rating -->
                <div class="product-rating">
                    <div class="stars">
                        <?php echo renderStars($product['rating']); ?>
                    </div>
                    <span class="rating-count">(<?php echo $product['reviews_count']; ?> đánh giá)</span>
                </div>

                <!-- Price -->
                <div class="product-price-large"><?php echo formatPrice($product['price']); ?></div>

                <!-- Short Description -->
                <div class="product-short-description">
                    <strong>Mô tả ngắn:</strong><br>
                    <?php echo htmlspecialchars($product['description']); ?>
                </div>

                <!-- Quantity Selector & Action Buttons -->
                <div class="quantity-section">
                    <div class="quantity-selector">
                        <button class="quantity-btn" onclick="decreaseQuantity()">-</button>
                        <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
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
                        <span class="info-value status-in-stock">Còn hàng (<?php echo $product['stock']; ?> sản phẩm)</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tags:</span>
                        <div class="info-value">
                            <div class="product-tags">
                                <?php foreach ($product['tags'] as $tag): ?>
                                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
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
                <button class="tab-button" onclick="switchTab('reviews')">Đánh giá (<?php echo $product['reviews_count']; ?>)</button>
            </div>

            <div id="tab-description" class="tab-content active">
                <p><?php echo nl2br(htmlspecialchars($product['full_description'])); ?></p>
            </div>

            <div id="tab-specifications" class="tab-content">
                <p><strong>Kích thước:</strong> Tùy theo loại cây</p>
                <p><strong>Màu sắc:</strong> Xanh lá cây</p>
                <p><strong>Chất liệu:</strong> Cây tự nhiên</p>
                <p><strong>Xuất xứ:</strong> Việt Nam</p>
                <p><strong>Bảo hành:</strong> 12 tháng</p>
                <p><strong>Hướng dẫn sử dụng:</strong> Trồng ở nơi có ánh sáng mặt trời, tưới nước đều đặn.</p>
            </div>

            <div id="tab-reviews" class="tab-content">
                <p>Hiện tại có <?php echo $product['reviews_count']; ?> đánh giá cho sản phẩm này.</p>
                <p>Đánh giá trung bình: <?php echo number_format($product['rating'], 1); ?>/5.0</p>
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
        const productId = <?php echo $product['id']; ?>;
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

