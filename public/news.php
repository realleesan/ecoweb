<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

$news = [];
$total_news = 0;
$categories_list = [];
$authors_list = [];

// Lấy filter params
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$filter_author = isset($_GET['author']) ? trim($_GET['author']) : '';

if ($pdo) {
    try {
        // Lấy danh sách categories và authors
        $categoriesStmt = $pdo->query('SELECT DISTINCT category FROM news WHERE category IS NOT NULL AND category != "" ORDER BY category ASC');
        $categories_list = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $authorsStmt = $pdo->query('SELECT DISTINCT author FROM news WHERE author IS NOT NULL AND author != "" ORDER BY author ASC');
        $authors_list = $authorsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Xây dựng query với filter
        $where_conditions = [];
        $query_params = [];
        
        if (!empty($filter_category)) {
            $where_conditions[] = 'category = :category';
            $query_params[':category'] = $filter_category;
        }
        
        if (!empty($filter_author)) {
            $where_conditions[] = 'author = :author';
            $query_params[':author'] = $filter_author;
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Đếm tổng số tin tức
        $count_sql = "SELECT COUNT(*) FROM news $where_sql";
        $countStmt = $pdo->prepare($count_sql);
        foreach ($query_params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total_news = (int) $countStmt->fetchColumn();

        // Phân trang
        $items_per_page = PAGINATION_NEWS_PER_PAGE;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $items_per_page;

        // Lấy tin tức với phân trang và filter
        $news_sql = "SELECT news_id, title, slug, publish_date, author, category, excerpt, description 
                     FROM news 
                     $where_sql
                     ORDER BY publish_date DESC, created_at DESC 
                     LIMIT :limit OFFSET :offset";
        $newsStmt = $pdo->prepare($news_sql);
        foreach ($query_params as $key => $value) {
            $newsStmt->bindValue($key, $value);
        }
        $newsStmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $newsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $newsStmt->execute();
        $news = $newsStmt->fetchAll();
    } catch (PDOException $e) {
        $news = [];
        $total_news = 0;
    }
}

$total_pages = $total_news > 0 ? (int)ceil($total_news / PAGINATION_NEWS_PER_PAGE) : 0;

include '../includes/header.php';
?>

<style>
    /* News Page Styles - Matching Products Page */
    .news-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 0 auto;
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        padding-top: 20px;
    }

    .news-info {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 16px;
        color: var(--dark);
        margin-bottom: 20px;
    }

    .news-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: <?php echo GRID_GAP_SMALL; ?>;
        margin-bottom: 40px;
    }

    .news-card {
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        cursor: pointer;
        display: flex;
        flex-direction: column;
    }

    .news-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .news-image {
        width: 100%;
        height: 160px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .news-image-placeholder {
        color: var(--dark);
        font-size: 14px;
    }

    .news-info-card {
        padding: 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .news-category {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 12px;
        color: var(--secondary);
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .news-title {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 18px;
        color: var(--dark);
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 50px;
        line-height: 1.4;
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
        flex: 1;
    }

    .news-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-size: 12px;
        color: #666;
    }

    .news-date {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .news-author {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .read-more-btn {
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
        margin-top: 12px;
    }

    .read-more-btn:hover {
        background-color: #2d4a2d;
    }


    .no-news {
        text-align: center;
        padding: 50px 20px;
        color: var(--dark);
    }

    .no-news i {
        font-size: 48px;
        color: var(--secondary);
        margin-bottom: 20px;
    }

    .no-news h3 {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 600;
        font-size: 24px;
        margin-bottom: 10px;
    }

    @media (max-width: <?php echo BREAKPOINT_XL; ?>) {
        .news-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .news-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: <?php echo GRID_GAP_SMALL; ?>;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_XS; ?>) {
        .news-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Main Content -->
<main style="min-height: 60vh; padding: 0; background-color: var(--light);">
    <?php
    $page_title = "Tin Tức";
    include __DIR__ . '/../includes/components/page-header.php';
    ?>
    
    <div class="news-container">
        <!-- Filter Section -->
        <?php
        // Chuẩn bị filter fields cho news
        $category_options = [['value' => '', 'label' => 'Tất cả']];
        foreach ($categories_list as $cat) {
            $category_options[] = [
                'value' => $cat,
                'label' => $cat
            ];
        }
        
        $author_options = [['value' => '', 'label' => 'Tất cả']];
        foreach ($authors_list as $author) {
            $author_options[] = [
                'value' => $author,
                'label' => $author
            ];
        }
        
        $filter_fields = [
            [
                'type' => 'select',
                'name' => 'category',
                'label' => 'Danh mục',
                'options' => $category_options,
                'value' => $filter_category
            ],
            [
                'type' => 'select',
                'name' => 'author',
                'label' => 'Tác giả',
                'options' => $author_options,
                'value' => $filter_author
            ]
        ];
        
        $type = 'news';
        $preserve_params = ['page'];
        include __DIR__ . '/../includes/components/filter.php';
        ?>
        
        <!-- News Info -->
        <div class="news-info">
            Hiển thị <span id="display-count"><?php echo count($news); ?></span> trên tổng số <span id="total-count"><?php echo $total_news; ?></span> tin tức
        </div>

        <!-- News Grid -->
        <?php if (empty($news)): ?>
            <div class="no-news">
                <i class="fas fa-newspaper"></i>
                <h3>Chưa có tin tức nào</h3>
                <p>Hiện tại chưa có tin tức nào được đăng tải.</p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($news as $item): ?>
                    <div class="news-card">
                        <div class="news-image">
                            <div class="news-image-placeholder">Hình ảnh tin tức</div>
                        </div>
                        <div class="news-info-card">
                            <div class="news-category"><?php echo htmlspecialchars($item['category']); ?></div>
                            <h3 class="news-title">
                                <a href="../views/news-detail.php?slug=<?php echo htmlspecialchars($item['slug']); ?>">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </h3>
                            <p class="news-excerpt">
                                <?php echo htmlspecialchars($item['excerpt'] ?: $item['description']); ?>
                            </p>
                            <div class="news-meta">
                                <div class="news-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($item['publish_date'])); ?>
                                </div>
                                <div class="news-author">
                                    <i class="far fa-user"></i>
                                    <?php echo htmlspecialchars($item['author']); ?>
                                </div>
                            </div>
                            <a href="../views/news-detail.php?slug=<?php echo htmlspecialchars($item['slug']); ?>" class="read-more-btn">
                                ĐỌC THÊM
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php
        // Giữ lại filter params trong pagination
        $pagination_params = [];
        if (!empty($filter_category)) $pagination_params['category'] = $filter_category;
        if (!empty($filter_author)) $pagination_params['author'] = $filter_author;
        $base_url = 'news.php?' . (!empty($pagination_params) ? http_build_query($pagination_params) . '&' : '');
        
        $current_page = $page;
        $total_pages = $total_pages;
        include __DIR__ . '/../includes/components/pagination.php';
        ?>
    </div>
</main>

<?php
$cta_heading = 'Theo dõi tin tức mới nhất về môi trường và trồng rừng';
$cta_description = 'Cập nhật các hoạt động, dự án và tin tức mới nhất từ cộng đồng phủ xanh Trái Đất.';
$cta_button_text = 'Xem sản phẩm';
$cta_button_link = BASE_URL . '/public/products.php';
include '../includes/components/cta-section.php';
?>
<?php include '../includes/footer.php'; ?>

