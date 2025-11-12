<?php 
// Kết nối database
$host = 'localhost';
$dbname = 'growhope_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Nếu không kết nối được, sử dụng dữ liệu mẫu
    $pdo = null;
}

// Phân trang
$items_per_page = 12; // 4 hàng x 3 cột = 12 items
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Lấy dữ liệu categories
$categories = [];
$total_categories = 0;

if ($pdo) {
    try {
        // Đếm tổng số categories
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM categories");
        $total_categories = $count_stmt->fetchColumn();
        
        // Lấy categories với phân trang
        $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY category_id ASC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $pdo = null;
    }
}

// Nếu không có dữ liệu từ database, sử dụng dữ liệu mẫu
if (empty($categories)) {
    $sample_categories = [
        ['category_id' => 1, 'category_name' => 'Cây Ăn Quả', 'slug' => 'cay-an-qua', 'description' => 'Các loại cây ăn quả phù hợp với khí hậu Việt Nam, mang lại giá trị kinh tế cao và góp phần bảo vệ môi trường.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Ăn+Quả', 'product_count' => 25],
        ['category_id' => 2, 'category_name' => 'Cây Lấy Gỗ', 'slug' => 'cay-lay-go', 'description' => 'Những loại cây lấy gỗ có giá trị kinh tế, sinh trưởng nhanh và thân thiện với môi trường.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Lấy+Gỗ', 'product_count' => 18],
        ['category_id' => 3, 'category_name' => 'Cây Cảnh Quan', 'slug' => 'cay-canh-quan', 'description' => 'Các loại cây cảnh quan đẹp mắt, tạo không gian xanh mát và trong lành cho môi trường sống.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Cảnh+Quan', 'product_count' => 32],
        ['category_id' => 4, 'category_name' => 'Cây Thuốc Nam', 'slug' => 'cay-thuoc-nam', 'description' => 'Những loại cây thuốc nam quý giá, có tác dụng chữa bệnh và bồi bổ sức khỏe.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Thuốc+Nam', 'product_count' => 15],
        ['category_id' => 5, 'category_name' => 'Cây Công Nghiệp', 'slug' => 'cay-cong-nghiep', 'description' => 'Các loại cây công nghiệp phục vụ sản xuất, mang lại hiệu quả kinh tế cao.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Công+Nghiệp', 'product_count' => 22],
        ['category_id' => 6, 'category_name' => 'Cây Phong Thủy', 'slug' => 'cay-phong-thuy', 'description' => 'Những loại cây phong thủy mang lại may mắn, tài lộc và năng lượng tích cực cho không gian.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Phong+Thủy', 'product_count' => 28],
        ['category_id' => 7, 'category_name' => 'Cây Bóng Mát', 'slug' => 'cay-bong-mat', 'description' => 'Các loại cây bóng mát lớn, tạo bóng râm và làm mát không gian sống.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Bóng+Mát', 'product_count' => 20],
        ['category_id' => 8, 'category_name' => 'Cây Rừng', 'slug' => 'cay-rung', 'description' => 'Những loại cây rừng bản địa, góp phần phục hồi và bảo tồn hệ sinh thái rừng.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Rừng', 'product_count' => 35],
        ['category_id' => 9, 'category_name' => 'Cây Hoa', 'slug' => 'cay-hoa', 'description' => 'Các loại cây hoa đẹp, tô điểm cho không gian và thu hút ong bướm.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Hoa', 'product_count' => 30],
        ['category_id' => 10, 'category_name' => 'Cây Thảo Mộc', 'slug' => 'cay-thao-moc', 'description' => 'Những loại cây thảo mộc có hương thơm, dùng trong ẩm thực và làm đẹp.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Thảo+Mộc', 'product_count' => 24],
        ['category_id' => 11, 'category_name' => 'Cây Ven Biển', 'slug' => 'cay-ven-bien', 'description' => 'Các loại cây chịu mặn, phù hợp trồng ven biển và bảo vệ đất khỏi xói mòn.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Ven+Biển', 'product_count' => 16],
        ['category_id' => 12, 'category_name' => 'Cây Nội Thất', 'slug' => 'cay-noi-that', 'description' => 'Những loại cây nội thất thanh lọc không khí, tạo không gian xanh trong nhà.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Nội+Thất', 'product_count' => 27],
        ['category_id' => 13, 'category_name' => 'Cây Nhiệt Đới', 'slug' => 'cay-nhiet-doi', 'description' => 'Các loại cây nhiệt đới đặc trưng, thích hợp với khí hậu nóng ẩm của Việt Nam.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Nhiệt+Đới', 'product_count' => 19],
        ['category_id' => 14, 'category_name' => 'Cây Cổ Thụ', 'slug' => 'cay-co-thu', 'description' => 'Những loại cây cổ thụ quý hiếm, có giá trị lịch sử và văn hóa cao.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Cổ+Thụ', 'product_count' => 12],
        ['category_id' => 15, 'category_name' => 'Cây Chống Lũ', 'slug' => 'cay-chong-lu', 'description' => 'Các loại cây có khả năng chống lũ, giữ đất và bảo vệ môi trường.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Chống+Lũ', 'product_count' => 14],
        ['category_id' => 16, 'category_name' => 'Cây Hữu Cơ', 'slug' => 'cay-huu-co', 'description' => 'Những loại cây được trồng theo phương pháp hữu cơ, an toàn và thân thiện môi trường.', 'image' => 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=Cây+Hữu+Cơ', 'product_count' => 21],
    ];
    
    $total_categories = count($sample_categories);
    $categories = array_slice($sample_categories, $offset, $items_per_page);
}

$total_pages = ceil($total_categories / $items_per_page);

include '../includes/header.php'; 
?>

<style>
    :root {
        --primary: #3C603C;
        --secondary: #D26426;
        --dark: #74493D;
        --light: #FFF7ED;
        --white: #FFFFFF;
        --bg-main: #9FBD48;
    }

    .categories-page {
        min-height: 60vh;
        padding: 60px 5%;
        background: var(--light);
    }

    .page-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .page-header h1 {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 42px;
        color: var(--dark);
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .page-header p {
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 18px;
        color: var(--dark);
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .categories-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin-bottom: 50px;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }

    .category-card {
        background: var(--white);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(116, 73, 61, 0.15);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .category-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 25px rgba(116, 73, 61, 0.25);
    }

    .category-image {
        width: 100%;
        height: 220px;
        object-fit: cover;
        background: linear-gradient(135deg, var(--primary), var(--bg-main));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 32px;
        font-weight: 700;
        position: relative;
        overflow: hidden;
    }

    .category-image::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(116, 73, 61, 0.1);
        transition: all 0.3s ease;
    }

    .category-card:hover .category-image {
        background: linear-gradient(135deg, var(--secondary), var(--primary));
    }

    .category-card:hover .category-image::before {
        background: rgba(116, 73, 61, 0.15);
    }

    .category-content {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .category-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 22px;
        color: var(--dark);
        margin-bottom: 12px;
        line-height: 1.3;
    }

    .category-description {
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
        opacity: 0.7;
        line-height: 1.6;
        margin-bottom: 15px;
        flex-grow: 1;
    }

    .category-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid rgba(116, 73, 61, 0.1);
    }

    .product-count {
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 14px;
        color: var(--secondary);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .product-count i {
        font-size: 16px;
    }

    .view-btn {
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 14px;
        color: var(--white);
        background: var(--primary);
        padding: 8px 20px;
        border-radius: 20px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .view-btn:hover {
        background: var(--secondary);
        transform: scale(1.05);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 50px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 16px;
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
        min-width: 44px;
        text-align: center;
    }

    .pagination a {
        color: var(--dark);
        background: var(--white);
        border: 2px solid var(--primary);
    }

    .pagination a:hover {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .pagination .current {
        background: var(--secondary);
        color: var(--white);
        border: 2px solid var(--secondary);
        font-weight: 600;
    }

    .pagination .disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
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

        .categories-page {
            padding: 40px 3%;
        }
    }
</style>

<!-- Main Content -->
<main class="categories-page">
    <div class="page-header">
        <h1>Danh Mục Sản Phẩm</h1>
        <p>Khám phá đa dạng các loại cây xanh, từ cây ăn quả đến cây cảnh quan, tất cả đều được chọn lọc kỹ lưỡng để mang lại giá trị tốt nhất cho bạn và môi trường.</p>
    </div>

    <div class="categories-grid">
        <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <div class="category-image" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
                    <?php 
                    $category_name = $category['category_name'];
                    $first_letter = mb_substr($category_name, 0, 1, 'UTF-8');
                    echo $first_letter;
                    ?>
                </div>
                <div class="category-content">
                    <h3 class="category-title"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                    <p class="category-description">
                        <?php 
                        echo isset($category['description']) 
                            ? htmlspecialchars($category['description']) 
                            : 'Danh mục chứa các sản phẩm chất lượng cao, được chọn lọc kỹ lưỡng để đảm bảo chất lượng tốt nhất.';
                        ?>
                    </p>
                    <div class="category-meta">
                        <span class="product-count">
                            <i class="fas fa-leaf"></i>
                            <?php echo isset($category['product_count']) ? $category['product_count'] : rand(10, 50); ?> sản phẩm
                        </span>
                        <a href="products.php?category=<?php echo htmlspecialchars($category['slug']); ?>" class="view-btn">
                            Xem thêm
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="prev">
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
                <a href="?page=1">1</a>
                <?php if ($start_page > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="next">
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

