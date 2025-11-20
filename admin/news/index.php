<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


$pdo = getPDO();


function ensureNewsTables(PDO $pdo) {
    $pdo->exec('CREATE TABLE IF NOT EXISTS news (
        news_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        publish_date DATE DEFAULT NULL,
        author VARCHAR(255) DEFAULT NULL,
        category VARCHAR(255) DEFAULT NULL,
        excerpt TEXT DEFAULT NULL,
        description TEXT DEFAULT NULL,
        content LONGTEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');


    $pdo->exec('CREATE TABLE IF NOT EXISTS news_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        news_id INT NOT NULL,
        tag VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_news_tag (news_id, tag),
        CONSTRAINT fk_news_tags_news FOREIGN KEY (news_id) REFERENCES news(news_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');


    $pdo->exec('CREATE TABLE IF NOT EXISTS news_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        news_id INT NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        caption VARCHAR(255) DEFAULT NULL,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_news_images_news FOREIGN KEY (news_id) REFERENCES news(news_id) ON DELETE CASCADE,
        INDEX idx_news_images_order (news_id, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}


ensureNewsTables($pdo);


$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM news WHERE news_id = :id');
                $stmt->execute([':id' => $id]);
                $message = 'Đã xóa bài viết';
            } catch (Exception $e) {
                $message = 'Không thể xóa bài viết';
            }
        }
    }
}


$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$filter_author = isset($_GET['author']) ? trim($_GET['author']) : '';


$where = [];
$params = [];
if ($keyword !== '') {
    $where[] = '(title LIKE :kw OR excerpt LIKE :kw OR description LIKE :kw)';
    $params[':kw'] = '%' . $keyword . '%';
}
if ($filter_category !== '') {
    $where[] = 'category = :category';
    $params[':category'] = $filter_category;
}
if ($filter_author !== '') {
    $where[] = 'author = :author';
    $params[':author'] = $filter_author;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';


$itemsPerPage = PAGINATION_NEWS_PER_PAGE;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;


$total = 0;
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM news $whereSql");
    foreach ($params as $k => $v) { $countStmt->bindValue($k, $v); }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();
} catch (Exception $e) { $total = 0; }


$rows = [];
try {
    $sql = "SELECT n.news_id, n.title, n.slug, n.category, n.author, n.publish_date, n.created_at, n.excerpt,
            (SELECT i.image_url FROM news_images i WHERE i.news_id = n.news_id ORDER BY i.display_order ASC, i.id ASC LIMIT 1) AS thumb_url
            FROM news n $whereSql ORDER BY n.publish_date DESC, n.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (Exception $e) { $rows = []; }


$categories = [];
$authors = [];
try {
    $categories = $pdo->query("SELECT DISTINCT category FROM news WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
    $authors = $pdo->query("SELECT DISTINCT author FROM news WHERE author IS NOT NULL AND author != '' ORDER BY author ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}


include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 30px; }
    .news-page__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; }
    .news-page__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .news-page__title-group p { color: rgba(0,0,0,0.55); font-size: 14px; }
    .breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 14px; flex-wrap: wrap; margin-bottom: 6px; }
    .breadcrumb a { color: var(--secondary); text-decoration: none; font-weight: 600; }
    .breadcrumb span { color: rgba(0,0,0,0.55); }
    .btn-add-news { display: inline-flex; align-items: center; gap: 10px; padding: 12px 18px; border-radius: 999px; background: linear-gradient(135deg, var(--secondary), #f7c76a); color: var(--white); font-weight: 600; text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 12px 25px rgba(210,100,38,0.25); }
    .btn-add-news:hover { transform: translateY(-2px); box-shadow: 0 18px 30px rgba(210,100,38,0.35); }
    .btn-secondary { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; background: rgba(0,0,0,0.06); color: var(--dark); font-weight: 600; text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .btn-secondary:hover { transform: translateY(-1px); box-shadow: 0 8px 14px rgba(0,0,0,0.12); }
    .news-filter { background-color: rgba(255,247,237,0.9); border-radius: 14px; padding: 18px 20px; border: 1px solid rgba(210,100,38,0.15); display: grid; grid-template-columns: minmax(220px,2fr) minmax(160px,1fr) minmax(160px,1fr) auto; gap: 16px; align-items: end; }
    .news-filter__field label { font-weight: 600; font-size: 13px; color: var(--dark); margin-bottom: 6px; display: block; }
    .news-filter__field input, .news-filter__field select { width: 100%; border-radius: 10px; border: 1px solid #e5e5e5; padding: 10px 14px; font-size: 14px; transition: border 0.2s ease, box-shadow 0.2s ease; background-color: var(--white); }
    .news-filter__field input:focus, .news-filter__field select:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(210,100,38,0.15); outline: none; }
    .news-filter__actions { display: flex; align-items: center; justify-content: flex-end; }
    .btn-filter-submit { padding: 10px 18px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; background: var(--secondary); color: var(--white); }
    .btn-filter-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(210,100,38,0.28); }
    .news-table-wrapper { border-radius: 14px; border: 1px solid #f0ebe3; overflow: hidden; background: var(--white); }
    .news-table { width: 100%; display: block; }
    .news-row { width: 100%; display: grid; grid-template-columns: 70px minmax(180px,2fr) minmax(110px,0.9fr) minmax(90px,0.7fr) minmax(100px,0.8fr) minmax(85px,0.7fr) 140px; gap: 10px; align-items: center; padding: 18px 20px; background-color: var(--white); border-bottom: 1px solid #f3f1ed; box-sizing: border-box; }
    .news-row:last-child { border-bottom: none; }
    .news-row--head { background-color: rgba(255,247,237,0.75); font-weight: 600; color: rgba(0,0,0,0.6); text-transform: uppercase; font-size: 12px; letter-spacing: 0.75px; position: relative; }
    .news-row--head::before { content: ''; position: absolute; inset: 0; background: rgba(255,247,237,0.75); z-index: 0; }
    .news-row--head .news-col { position: relative; z-index: 1; }
    .news-col { display: flex; align-items: center; gap: 10px; }
    .news-thumb { width: 56px; height: 56px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(210,100,38,0.1); color: var(--secondary); font-size: 14px; overflow: hidden; }
    .news-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .news-title { display: flex; flex-direction: column; gap: 4px; }
    .news-title strong { font-weight: 700; color: var(--dark); font-size: 14px; }
    .news-title span { font-size: 13px; color: rgba(0,0,0,0.55); }
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .status-badge.published { background-color: rgba(63,160,79,0.13); color: #3fa04f; }
    .status-badge.draft { background-color: rgba(0,0,0,0.08); color: rgba(0,0,0,0.6); }
    .news-actions { display: grid; grid-template-columns: repeat(2,1fr); gap: 6px; width: 100%; max-width: 80px; }
    .news-actions a, .news-actions button { width: 100%; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: var(--dark); background: rgba(0,0,0,0.06); text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; border: none; cursor: pointer; font-size: 13px; }
    .news-actions a:hover, .news-actions button:hover { transform: translateY(-2px); box-shadow: 0 8px 14px rgba(0,0,0,0.12); }
    @media (max-width: <?php echo BREAKPOINT_LG; ?>) { .news-row { grid-template-columns: 60px 1.5fr 0.9fr 0.7fr 0.8fr 0.7fr 130px; padding: 14px; } }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .news-row { grid-template-columns: repeat(2,minmax(0,1fr)); grid-template-areas: 'thumb title' 'category author' 'date status' 'actions actions'; gap: 12px 18px; } .news-row--head { display: none; } }
</style>


<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-content news-page">
        <div class="news-page__header">
            <div class="news-page__title-group">
                <h1>Quản Lý Tin Tức</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <span>Quản Lý Tin Tức</span>
                </nav>
                <p>Tổng cộng: <?php echo $total; ?> bài viết</p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <a class="btn-secondary" href="<?php echo BASE_URL; ?>/admin/news/tags.php"><i class="fas fa-tags"></i> Quản Lý Tags</a>
                <a class="btn-add-news" href="<?php echo BASE_URL; ?>/admin/news/add.php"><i class="fas fa-plus"></i> Thêm Tin Tức Mới</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="notice notice--success" role="status" style="display:flex;align-items:flex-start;gap:12px;border-radius:12px;padding:14px 18px;background:rgba(63,142,63,0.12);color:#2a6a2a;border:1px solid rgba(63,142,63,0.35);">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
        <?php endif; ?>

        <form class="news-filter" method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="news-filter__field">
                <label for="q">Tìm Kiếm</label>
                <input type="text" id="q" name="q" placeholder="Tiêu đề, nội dung..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="news-filter__field">
                <label for="category">Danh Mục</label>
                <select id="category" name="category">
                    <option value="">Tất cả</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="news-filter__field">
                <label for="author">Tác Giả</label>
                <select id="author" name="author">
                    <option value="">Tất cả</option>
                    <?php foreach ($authors as $auth): ?>
                        <option value="<?php echo htmlspecialchars($auth); ?>" <?php echo $filter_author === $auth ? 'selected' : ''; ?>><?php echo htmlspecialchars($auth); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="news-filter__actions">
                <button type="submit" class="btn-filter-submit"><i class="fas fa-filter"></i> Lọc</button>
            </div>
        </form>

        <div class="news-table-wrapper">
            <div class="news-table" role="table" aria-label="Danh sách tin tức">
                <div class="news-row news-row--head" role="row">
                    <div class="news-col" role="columnheader">Ảnh</div>
                    <div class="news-col" role="columnheader">Tiêu đề</div>
                    <div class="news-col" role="columnheader">Danh mục</div>
                    <div class="news-col" role="columnheader">Tác giả</div>
                    <div class="news-col" role="columnheader">Ngày xuất bản</div>
                    <div class="news-col" role="columnheader">Trạng thái</div>
                    <div class="news-col" role="columnheader">Thao tác</div>
                </div>

                <?php if (empty($rows)): ?>
                    <div class="news-row" role="row" style="justify-content:center;">
                        <div style="grid-column:1/-1;text-align:center;color:rgba(0,0,0,0.5);">Chưa có bài viết</div>
                    </div>
                <?php else: foreach ($rows as $r):
                    $is_published = !empty($r['publish_date']) && strtotime($r['publish_date'].' 00:00:00') <= time();
                ?>
                    <div class="news-row" role="row">
                        <div class="news-col" role="cell">
                            <span class="news-thumb">
                                <?php if (!empty($r['thumb_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($r['thumb_url']); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-newspaper"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="news-col" role="cell">
                            <div class="news-title">
                                <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                            </div>
                        </div>
                        <div class="news-col" role="cell">
                            <span><?php echo htmlspecialchars($r['category'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="news-col" role="cell">
                            <span><?php echo htmlspecialchars($r['author'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="news-col" role="cell">
                            <span><?php echo $r['publish_date'] ? date('d/m/Y', strtotime($r['publish_date'])) : 'N/A'; ?></span>
                        </div>
                        <div class="news-col" role="cell">
                            <span class="status-badge <?php echo $is_published ? 'published' : 'draft'; ?>">
                                <?php echo $is_published ? 'Đã xuất bản' : 'Bản nháp'; ?>
                            </span>
                        </div>
                        <div class="news-col" role="cell">
                            <div class="news-actions">
                                <a href="<?php echo BASE_URL; ?>/admin/news/edit.php?id=<?php echo (int)$r['news_id']; ?>" title="Sửa"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo BASE_URL; ?>/views/news-detail.php?slug=<?php echo htmlspecialchars($r['slug']); ?>" target="_blank" title="Xem"><i class="fas fa-eye"></i></a>
                                <form method="post" style="display:contents;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$r['news_id']; ?>">
                                    <button type="submit" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>


        <div class="pagination-wrap">
            <?php
            $base_params = [];
            if ($keyword !== '') $base_params['q'] = $keyword;
            if ($filter_category !== '') $base_params['category'] = $filter_category;
            if ($filter_author !== '') $base_params['author'] = $filter_author;
            $base_url = BASE_URL . '/admin/news/index.php?' . (!empty($base_params) ? http_build_query($base_params) . '&' : '');
            $current_page = $page;
            $total_pages = $total > 0 ? (int)ceil($total / $itemsPerPage) : 0;
            include __DIR__ . '/../../includes/components/pagination.php';
            ?>
        </div>
    </main>
 </div>


<?php include __DIR__ . '/../includes/footer.php'; ?>

