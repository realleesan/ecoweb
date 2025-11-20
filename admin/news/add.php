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
    
    $pdo->exec('CREATE TABLE IF NOT EXISTS news_tag_master (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tag VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(150) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    
    $pdo->exec('CREATE TABLE IF NOT EXISTS news_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        news_id INT NOT NULL,
        tag VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_news_tag (news_id, tag),
        CONSTRAINT fk_news_tags_news FOREIGN KEY (news_id) REFERENCES news(news_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}


ensureNewsTables($pdo);


$error = '';
$success = '';
// Load all unique tags from both news_tag_master and news_tags
$all_tags = [];
try { 
    $stmt = $pdo->query('
        SELECT DISTINCT tag FROM (
            SELECT tag FROM news_tag_master
            UNION
            SELECT DISTINCT tag FROM news_tags
        ) AS all_tags
        ORDER BY tag ASC
    ');
    $all_tags = $stmt->fetchAll(PDO::FETCH_COLUMN); 
} catch (Exception $e) { 
    $all_tags = []; 
}
// Load news categories for dropdown (distinct from existing news)
$categories = [];
try { $categories = $pdo->query("SELECT DISTINCT category FROM news WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN); } catch (Exception $e) { $categories = []; }


function make_slug($text) {
    $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
    return trim($base, '-');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publish_date = trim($_POST['publish_date'] ?? '');
    $publish_now = isset($_POST['publish_now']) ? true : false;
    $excerpt = trim($_POST['excerpt'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tags_selected = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
    $new_tag = trim($_POST['new_tag'] ?? '');


    if ($title === '') {
        $error = 'Vui lòng nhập tiêu đề';
    } else {
        if ($slug === '') { $slug = make_slug($title); }
        try {
            $check = $pdo->prepare('SELECT COUNT(*) FROM news WHERE slug = :slug');
            $check->execute([':slug' => $slug]);
            $exists = (int)$check->fetchColumn();
            if ($exists > 0) {
                $slug .= '-' . substr(sha1(uniqid('', true)), 0, 6);
            }
            $stmt = $pdo->prepare('INSERT INTO news (title, slug, publish_date, author, category, excerpt, description, content) VALUES (:title, :slug, :publish_date, :author, :category, :excerpt, :description, :content)');
            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':publish_date' => ($publish_now ? date('Y-m-d') : ($publish_date !== '' ? $publish_date : null)),
                ':author' => $author !== '' ? $author : null,
                ':category' => $category !== '' ? $category : null,
                ':excerpt' => $excerpt !== '' ? $excerpt : null,
                ':description' => $description !== '' ? $description : null,
                ':content' => $content !== '' ? $content : null,
            ]);
            $newsId = (int)$pdo->lastInsertId();


            // Normalize and add new tags from input
            $to_add = [];
            foreach ($tags_selected as $t) {
                $t = trim($t);
                if ($t !== '') { $to_add[] = $t; }
            }
            if ($new_tag !== '') {
                $parts = array_map('trim', explode(',', $new_tag));
                foreach ($parts as $p) { if ($p !== '') { $to_add[] = $p; } }
            }
            if (!empty($to_add)) {
                foreach ($to_add as $tg) {
                    try {
                        // ensure exists in master
                        $slugTag = make_slug($tg);
                        $chk = $pdo->prepare('SELECT COUNT(*) FROM news_tag_master WHERE tag = :tag');
                        $chk->execute([':tag' => $tg]);
                        if ((int)$chk->fetchColumn() === 0) {
                            $dup = $pdo->prepare('SELECT COUNT(*) FROM news_tag_master WHERE slug = :slug');
                            $dup->execute([':slug' => $slugTag]);
                            if ((int)$dup->fetchColumn() > 0) { $slugTag .= '-' . substr(sha1(uniqid('', true)), 0, 6); }
                            $pdo->prepare('INSERT INTO news_tag_master (tag, slug) VALUES (:tag, :slug)')->execute([':tag' => $tg, ':slug' => $slugTag]);
                        }
                        // link to news
                        $pdo->prepare('INSERT IGNORE INTO news_tags (news_id, tag) VALUES (:id, :tag)')->execute([':id' => $newsId, ':tag' => $tg]);
                    } catch (Exception $e) {}
                }
            }


            $success = 'Đã tạo bài viết';
            header('Location: ' . BASE_URL . '/admin/news/edit.php?id=' . $newsId);
            exit;
        } catch (Exception $e) {
            $error = 'Không thể tạo bài viết';
        }
    }
}


include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 30px; }
    .news-add__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; }
    .news-add__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 14px; flex-wrap: wrap; margin-bottom: 6px; }
    .breadcrumb a { color: var(--secondary); text-decoration: none; font-weight: 600; }
    .breadcrumb span { color: rgba(0,0,0,0.55); }
    .edit-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
    .card { background: var(--white); border-radius: 14px; border: 1px solid #f0ebe3; overflow: hidden; }
    .card-header { padding: 18px 20px; border-bottom: 1px solid #f0ebe3; font-weight: 700; font-size: 16px; color: var(--dark); background: rgba(255,247,237,0.3); }
    .card-body { padding: 20px; }
    .form-row { display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; }
    .form-row label { font-weight: 600; font-size: 14px; color: var(--dark); }
    .form-row input, .form-row textarea, .form-row select { width: 100%; padding: 11px 14px; border: 1px solid #e5e5e5; border-radius: 10px; font-size: 14px; transition: border 0.2s ease, box-shadow 0.2s ease; font-family: inherit; }
    .form-row input:focus, .form-row textarea:focus, .form-row select:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(210,100,38,0.15); outline: none; }
    .form-row textarea { resize: vertical; }
    .actions { display: flex; gap: 12px; margin-top: 20px; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 10px; cursor: pointer; border: none; font-weight: 600; font-size: 14px; transition: transform 0.2s ease, box-shadow 0.2s ease; text-decoration: none; }
    .btn-primary { background: var(--secondary); color: var(--white); }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(210,100,38,0.28); }
    .btn-outline { border: 1px solid #e5e5e5; color: var(--dark); background: var(--white); }
    .btn-outline:hover { transform: translateY(-1px); box-shadow: 0 8px 14px rgba(0,0,0,0.12); }
    .checkbox-list { display: flex; flex-direction: column; gap: 10px; max-height: 280px; overflow-y: auto; padding: 12px; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px solid rgba(0,0,0,0.08); }
    .checkbox-list::-webkit-scrollbar { width: 8px; }
    .checkbox-list::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 4px; }
    .checkbox-list::-webkit-scrollbar-thumb { background: rgba(210,100,38,0.4); border-radius: 4px; }
    .checkbox-list::-webkit-scrollbar-thumb:hover { background: rgba(210,100,38,0.6); }
    .checkbox-list label { display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 14px; cursor: pointer; }
    .checkbox-list input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
    .notice { display: flex; align-items: flex-start; gap: 12px; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
    .notice--error { background: rgba(210,64,38,0.12); color: #a52f1c; border: 1px solid rgba(210,64,38,0.35); }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .edit-grid { grid-template-columns: 1fr; } }
</style>


<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-content news-add">
        <div class="news-add__header">
            <div class="news-add__title-group">
                <h1>Thêm Tin Tức Mới</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <a href="<?php echo BASE_URL; ?>/admin/news/index.php">Quản Lý Tin Tức</a>
                    <span>/</span>
                    <span>Thêm Mới</span>
                </nav>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="notice notice--error" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="edit-grid">
                <div class="card">
                    <div class="card-header">Thông Tin Cơ Bản</div>
                    <div class="card-body">
                        <div class="form-row">
                            <label>Tiêu đề <span style="color:#d64226;">*</span></label>
                            <input type="text" name="title" required placeholder="Nhập tiêu đề bài viết">
                        </div>
                        <div class="form-row">
                            <label>Slug (URL)</label>
                            <input type="text" name="slug" placeholder="Tự tạo từ tiêu đề nếu để trống">
                        </div>
                        <div class="form-row">
                            <label>Danh mục</label>
                            <select name="category">
                                <option value="">Chọn danh mục...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label>Mô tả ngắn (Excerpt)</label>
                            <textarea name="excerpt" rows="3" placeholder="Mô tả ngắn giúp hiển thị ở trang danh sách"></textarea>
                        </div>
                        <div class="form-row">
                            <label>Ảnh đại diện</label>
                            <div style="color:rgba(0,0,0,0.6); font-size:13px; padding:10px; background:rgba(255,247,237,0.5); border-radius:8px;">
                                <i class="fas fa-info-circle"></i> Quản lý ảnh sau khi lưu tại trang sửa
                            </div>
                        </div>
                        <div class="form-row">
                            <label>Nội dung <span style="color:#d64226;">*</span></label>
                            <textarea name="content" rows="12" placeholder="Nội dung đầy đủ của bài viết"></textarea>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="card" style="margin-bottom:20px;">
                        <div class="card-header">Xuất Bản</div>
                        <div class="card-body">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; padding:10px; background:rgba(255,247,237,0.5); border-radius:8px;">
                                <input type="checkbox" id="publish_now" name="publish_now" value="1" style="width:18px; height:18px; cursor:pointer;">
                                <label for="publish_now" style="margin:0; cursor:pointer; font-weight:600;">Xuất bản ngay</label>
                            </div>
                            <div class="form-row">
                                <label>Ngày xuất bản</label>
                                <input type="date" name="publish_date">
                            </div>
                            <div class="form-row">
                                <label>Tác giả</label>
                                <input type="text" name="author" placeholder="Administrator">
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">Tags</div>
                        <div class="card-body">
                            <div class="checkbox-list">
                                <?php foreach ($all_tags as $tg): ?>
                                    <label><input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($tg); ?>"><span><?php echo htmlspecialchars($tg); ?></span></label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-row" style="margin-top:16px;">
                                <label>Thêm Tag Mới</label>
                                <input type="text" name="new_tag" placeholder="tag1, tag2...">
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Tin Tức</button>
                        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/news/index.php"><i class="fas fa-times"></i> Hủy</a>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>

