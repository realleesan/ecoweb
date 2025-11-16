<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

// Hỗ trợ cả slug và id để tương thích
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$news_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/news.php');
    exit;
}

// Tìm tin tức theo slug hoặc id
if (!empty($slug)) {
    $newsStmt = $pdo->prepare('SELECT news_id, title, slug, publish_date, created_at, author, category, excerpt, description, content
                                FROM news
                                WHERE slug = :slug');
    $newsStmt->bindValue(':slug', $slug, PDO::PARAM_STR);
    $newsStmt->execute();
    $article = $newsStmt->fetch();
} elseif ($news_id > 0) {
    $newsStmt = $pdo->prepare('SELECT news_id, title, slug, publish_date, created_at, author, category, excerpt, description, content
                                FROM news
                                WHERE news_id = :id');
    $newsStmt->bindValue(':id', $news_id, PDO::PARAM_INT);
    $newsStmt->execute();
    $article = $newsStmt->fetch();
} else {
    $article = false;
}

if (!$article) {
    header('Location: ' . BASE_URL . '/public/news.php');
    exit;
}

// Lấy news_id từ article để lấy tags
$news_id = (int) $article['news_id'];

$tagStmt = $pdo->prepare('SELECT tag FROM news_tags WHERE news_id = :id ORDER BY tag ASC');
$tagStmt->bindValue(':id', $news_id, PDO::PARAM_INT);
$tagStmt->execute();
$article_tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<style>
    body {
        background-color: var(--light);
    }

    /* News Detail Page Styles */
    .news-detail-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 0 auto;
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        padding-top: 20px;
        min-height: 80vh;
    }

    .news-detail-wrapper {
        max-width: <?php echo CONTAINER_MAX_WIDTH_SMALL; ?>;
        margin: 0 auto;
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .news-detail-header {
        padding: 40px 40px 30px;
        border-bottom: 1px solid #e0e0e0;
    }


    .news-category {
        display: inline-block;
        background-color: var(--primary);
        color: var(--white);
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 15px;
    }

    .news-title {
        font-size: 36px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 20px;
        line-height: 1.3;
    }

    .news-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e0e0e0;
    }

    .news-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--dark);
        font-size: 14px;
    }

    .news-meta-item i {
        color: var(--secondary);
        width: 16px;
    }

    .news-description {
        font-size: 18px;
        color: var(--dark);
        line-height: 1.8;
        font-style: italic;
        padding: 20px;
        background-color: #f8f9fa;
        border-left: 4px solid var(--secondary);
        margin-bottom: 30px;
    }

    .news-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 72px;
        margin-bottom: 30px;
    }

    .news-content {
        padding: 0 40px 40px;
    }

    .news-body {
        font-size: 16px;
        line-height: 1.9;
        color: var(--dark);
        margin-bottom: 30px;
    }

    .news-body p {
        margin-bottom: 20px;
    }

    .news-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e0e0e0;
    }

    .news-tags-label {
        font-weight: 600;
        color: var(--primary);
        margin-right: 10px;
    }

    .news-tag {
        display: inline-block;
        background-color: var(--light);
        color: var(--dark);
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid #e0e0e0;
    }

    .news-tag:hover {
        background-color: var(--secondary);
        color: var(--white);
        border-color: var(--secondary);
    }

    .news-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e0e0e0;
    }

    .news-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background-color: var(--primary);
        color: var(--white);
        text-decoration: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .news-action-btn:hover {
        background-color: var(--secondary);
        transform: translateY(-2px);
    }

    .news-action-btn.secondary {
        background-color: transparent;
        color: var(--primary);
        border: 2px solid var(--primary);
    }

    .news-action-btn.secondary:hover {
        background-color: var(--primary);
        color: var(--white);
    }

    /* Responsive */
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .news-detail-header,
        .news-content {
            padding: 30px 20px;
        }

        .news-title {
            font-size: 28px;
        }

        .news-image {
            height: 250px;
            font-size: 48px;
        }

        .news-meta {
            flex-direction: column;
            gap: 10px;
        }

        .news-actions {
            flex-direction: column;
        }

        .news-action-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>


<?php
$page_title = htmlspecialchars($article['title']);
$breadcrumbs = [
    ['text' => 'Trang Chủ', 'url' => BASE_URL . '/index.php'],
    ['text' => 'Tin Tức', 'url' => BASE_URL . '/public/news.php'],
    ['text' => $article['title'], 'url' => '']
];
include __DIR__ . '/../includes/components/page-header.php';
?>

<div class="news-detail-container">
    <div class="news-detail-wrapper">
        <!-- Header -->
        <div class="news-detail-header">
            <div class="news-category"><?php echo htmlspecialchars($article['category']); ?></div>

            <h1 class="news-title" style="display: none;"><?php echo htmlspecialchars($article['title']); ?></h1>

            <div class="news-meta">
                <div class="news-meta-item">
                    <i class="far fa-calendar"></i>
                    <span><?php echo date(DATE_FORMAT, strtotime($article['publish_date'])); ?></span>
                </div>
                <div class="news-meta-item">
                    <i class="far fa-user"></i>
                    <span><?php echo htmlspecialchars($article['author']); ?></span>
                </div>
                <div class="news-meta-item">
                    <i class="far fa-clock"></i>
                    <span><?php echo date('H:i', strtotime($article['created_at'] ?? $article['publish_date'])); ?></span>
                </div>
            </div>

            <?php if (!empty($article['description'])): ?>
            <div class="news-description">
                <?php echo htmlspecialchars($article['description']); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Image -->
        <div class="news-image">
            <i class="fas fa-leaf"></i>
        </div>

        <!-- Content -->
        <div class="news-content">
            <div class="news-body">
                <?php echo nl2br(htmlspecialchars($article['content'])); ?>
            </div>

            <!-- Tags -->
            <?php if (!empty($article_tags)): ?>
            <div class="news-tags">
                <span class="news-tags-label">Tags:</span>
                <?php foreach ($article_tags as $tag): ?>
                    <a href="<?php echo BASE_URL; ?>/public/news.php?tag=<?php echo urlencode($tag); ?>" class="news-tag">
                        <?php echo htmlspecialchars($tag); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="news-actions">
                <a href="<?php echo BASE_URL; ?>/public/news.php" class="news-action-btn secondary">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại danh sách
                </a>
                <a href="#" class="news-action-btn" onclick="window.print(); return false;">
                    <i class="fas fa-print"></i>
                    In bài viết
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Related News Section -->
<div style="background-color: var(--light); padding: 40px 0;">
    <?php
    $type = 'news';
    $current_id = $news_id;
    $category = $article['category'];
    $tags = $article_tags;
    include __DIR__ . '/../includes/components/related-section.php';
    ?>
</div>

<?php include '../includes/footer.php'; ?>

