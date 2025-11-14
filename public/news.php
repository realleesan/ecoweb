<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

$items_per_page = PAGINATION_NEWS_PER_PAGE;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

$news = [];
$total_items = 0;
$total_pages = 0;

if ($pdo) {
    try {
        $count_stmt = $pdo->query('SELECT COUNT(*) FROM news');
        $total_items = (int) $count_stmt->fetchColumn();
        $total_pages = $total_items > 0 ? (int) ceil($total_items / $items_per_page) : 0;
        if ($total_pages > 0 && $current_page > $total_pages) {
            $current_page = $total_pages;
            $offset = ($current_page - 1) * $items_per_page;
        }

        if ($total_items > 0) {
            $stmt = $pdo->prepare('SELECT news_id, title, slug, publish_date, excerpt
                                   FROM news
                                   ORDER BY publish_date DESC, news_id DESC
                                   LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $news = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $news = [];
        $total_items = 0;
        $total_pages = 0;
    }
}

include '../includes/header.php';
?>

<style>
        body {
            background-color: var(--light);
        }

        /* News Page Styles */
        .news-container {
            max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
            margin: 0 auto;
            padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
            min-height: 80vh;
        }

        .news-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .news-header h1 {
            font-size: 36px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .news-header p {
            color: var(--dark);
            font-size: 16px;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: <?php echo GRID_GAP_SMALL; ?>;
            margin-bottom: 50px;
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .news-card a {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .news-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 40px;
        }

        .news-content {
            padding: 16px;
        }

        .news-date {
            color: var(--secondary);
            font-size: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .news-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            line-height: 1.4;
            min-height: 50px;
        }

        .news-excerpt {
            color: var(--dark);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .news-full-content {
            color: var(--dark);
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .news-read-more {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }

        .news-read-more:hover {
            color: var(--primary);
        }

        /* Pagination */
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
            background-color: var(--white);
            color: var(--dark);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .pagination a:hover {
            background-color: var(--secondary);
            color: var(--white);
            border-color: var(--secondary);
        }

        .pagination .current {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: <?php echo BREAKPOINT_XL; ?>) {
            .news-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
            .news-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: <?php echo GRID_GAP_SMALL; ?>;
            }
        }

        @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
            .news-grid {
                grid-template-columns: 1fr;
            }

            .news-header h1 {
                font-size: 28px;
            }
        }
    </style>

    <!-- News Content -->
    <div class="news-container">
        <div class="news-header">
            <h1>Tin tức</h1>
            <p>Cập nhật những thông tin mới nhất về môi trường và trồng cây gây rừng</p>
        </div>

        <div class="news-grid">
            <?php if (empty($news)): ?>
                <div class="noResults" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <i class="fas fa-newspaper" style="font-size: 48px; color: var(--secondary); margin-bottom: 15px;"></i>
                    <p>Hiện tại chưa có bài viết nào.</p>
                </div>
            <?php else: ?>
            <?php foreach ($news as $item): ?>
            <article class="news-card">
                <a href="../views/news-detail.php?id=<?php echo $item['news_id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                    <div class="news-image">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="news-content">
                        <div class="news-date">
                            <i class="far fa-calendar"></i>
                            <?php echo date('d/m/Y', strtotime($item['publish_date'])); ?>
                        </div>
                        <h2 class="news-title"><?php echo htmlspecialchars($item['title']); ?></h2>
                        <div class="news-excerpt">
                            <?php echo htmlspecialchars($item['excerpt']); ?>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="disabled">
                    <i class="fas fa-chevron-left"></i>
                </span>
            <?php endif; ?>

            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1): ?>
                <a href="?page=1">1</a>
                <?php if ($start_page > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $current_page): ?>
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

            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled">
                    <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

<?php include '../includes/footer.php'; ?>

