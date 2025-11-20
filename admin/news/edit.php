<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


$pdo = getPDO();


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ' . BASE_URL . '/admin/news/index.php'); exit; }


$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publish_date = trim($_POST['publish_date'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $selectedTags = isset($_POST['tags']) && is_array($_POST['tags']) ? array_values(array_filter(array_map('trim', $_POST['tags']))) : [];
    $newTag = trim($_POST['new_tag'] ?? '');


    if ($title === '') {
        $error = 'Vui lòng nhập tiêu đề';
    } else {
        // Giữ nguyên slug do người dùng nhập; nếu để trống thì cho phép lưu trống
        try {
            if ($slug !== '') {
                $check = $pdo->prepare('SELECT COUNT(*) FROM news WHERE slug = :slug AND news_id <> :id');
                $check->execute([':slug' => $slug, ':id' => $id]);
                $exists = (int)$check->fetchColumn();
                if ($exists > 0) { $slug .= '-' . substr(sha1(uniqid('', true)), 0, 6); }
            }
            $stmt = $pdo->prepare('UPDATE news SET title = :title, slug = :slug, publish_date = :publish_date, author = :author, category = :category, excerpt = :excerpt, description = :description, content = :content WHERE news_id = :id');
            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':publish_date' => $publish_date !== '' ? $publish_date : null,
                ':author' => $author !== '' ? $author : null,
                ':category' => $category !== '' ? $category : null,
                ':excerpt' => $excerpt !== '' ? $excerpt : null,
                ':description' => $description !== '' ? $description : null,
                ':content' => $content !== '' ? $content : null,
                ':id' => $id,
            ]);
            if ($newTag !== '') { $selectedTags[] = $newTag; }
            $selectedTags = array_values(array_unique($selectedTags));
            $currentTags = [];
            try {
                $st = $pdo->prepare('SELECT tag FROM news_tags WHERE news_id = :id');
                $st->execute([':id' => $id]);
                $currentTags = $st->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) { $currentTags = []; }
            $toAdd = array_diff($selectedTags, $currentTags);
            $toDelete = array_diff($currentTags, $selectedTags);
            foreach ($toAdd as $tg) {
                if ($tg !== '') {
                    try { $ins = $pdo->prepare('INSERT INTO news_tags (news_id, tag) VALUES (:id, :tag)'); $ins->execute([':id' => $id, ':tag' => $tg]); } catch (Exception $e) {}
                }
            }
            foreach ($toDelete as $tg) {
                try { $del = $pdo->prepare('DELETE FROM news_tags WHERE news_id = :id AND tag = :tag'); $del->execute([':id' => $id, ':tag' => $tg]); } catch (Exception $e) {}
            }
            $success = 'Đã cập nhật bài viết';
            header('Location: ' . BASE_URL . '/admin/news/edit.php?id=' . $id);
            exit;
        } catch (Exception $e) {
            $error = 'Không thể cập nhật';
        }
    }
}


$row = null;
try {
    $stmt = $pdo->prepare('SELECT news_id, title, slug, category, author, publish_date, excerpt, description, content FROM news WHERE news_id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
} catch (Exception $e) { $row = null; }
if (!$row) { header('Location: ' . BASE_URL . '/admin/news/index.php'); exit; }


$thumb_url = null;
try {
    $st = $pdo->prepare('SELECT image_url FROM news_images WHERE news_id = :id ORDER BY display_order ASC, id ASC LIMIT 1');
    $st->execute([':id' => $id]);
    $thumb_url = $st->fetchColumn();
} catch (Exception $e) { $thumb_url = null; }


$all_tags = [];
$current_tags = [];
// Load all unique tags from both news_tag_master and news_tags
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
try { $st = $pdo->prepare('SELECT tag FROM news_tags WHERE news_id = :id'); $st->execute([':id' => $id]); $current_tags = $st->fetchAll(PDO::FETCH_COLUMN); } catch (Exception $e) { $current_tags = []; }

// Danh mục dropdown: lấy từ danh mục tin tức hiện có
$categories = [];
try { $categories = $pdo->query("SELECT DISTINCT category FROM news WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN); } catch (Exception $e) { $categories = []; }


include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 30px; }
    .news-edit__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; }
    .news-edit__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
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
    .thumb { width: 140px; height: 100px; object-fit: cover; border-radius: 12px; background-color: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; color: rgba(0,0,0,0.4); font-size: 14px; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 10px; cursor: pointer; border: none; font-weight: 600; font-size: 14px; transition: transform 0.2s ease, box-shadow 0.2s ease; text-decoration: none; }
    .btn-primary { background: var(--secondary); color: var(--white); }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(210,100,38,0.28); }
    .btn-outline { border: 1px solid #e5e5e5; color: var(--dark); background: var(--white); }
    .btn-outline:hover { transform: translateY(-1px); box-shadow: 0 8px 14px rgba(0,0,0,0.12); }
    .checkbox-list { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-height: 280px; overflow-y: auto; padding: 12px; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px solid rgba(0,0,0,0.08); }
    .checkbox-list::-webkit-scrollbar { width: 8px; }
    .checkbox-list::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 4px; }
    .checkbox-list::-webkit-scrollbar-thumb { background: rgba(210,100,38,0.4); border-radius: 4px; }
    .checkbox-list::-webkit-scrollbar-thumb:hover { background: rgba(210,100,38,0.6); }
    .checkbox-list label { display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 14px; cursor: pointer; }
    .checkbox-list input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
    .actions { display: flex; gap: 12px; margin-top: 20px; }
    .notice { display: flex; align-items: flex-start; gap: 12px; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
    .notice--success { background: rgba(63,142,63,0.12); color: #2a6a2a; border: 1px solid rgba(63,142,63,0.35); }
    .notice--error { background: rgba(210,64,38,0.12); color: #a52f1c; border: 1px solid rgba(210,64,38,0.35); }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .edit-grid { grid-template-columns: 1fr; } .checkbox-list { grid-template-columns: 1fr; } }
</style>


<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-content news-edit">
        <div class="news-edit__header">
            <div class="news-edit__title-group">
                <h1>Sửa Tin Tức</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <a href="<?php echo BASE_URL; ?>/admin/news/index.php">Quản Lý Tin Tức</a>
                    <span>/</span>
                    <span>Sửa</span>
                </nav>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="notice notice--success" role="status">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
        <?php endif; ?>

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
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($row['title']); ?>">
                        </div>
                        <div class="form-row">
                            <label>Slug (URL)</label>
                            <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($row['slug']); ?>">
                        </div>
                        <div class="form-row">
                            <label>Danh mục</label>
                            <select name="category" id="category">
                                <option value="">Chọn danh mục...</option>
                                <?php foreach ($categories as $cat): $sel = ($row['category'] === $cat) ? 'selected' : ''; ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label>Mô tả ngắn (Excerpt)</label>
                            <textarea name="excerpt" rows="3"><?php echo htmlspecialchars($row['excerpt']); ?></textarea>
                        </div>
                        <div class="form-row">
                            <label>Ảnh đại diện</label>
                            <?php if (!empty($thumb_url)): ?>
                                <img src="<?php echo htmlspecialchars($thumb_url); ?>" alt="Thumb" class="thumb" style="width:140px; height:100px; object-fit:cover; border-radius:12px;" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="thumb">Chưa có ảnh</div>
                            <?php endif; ?>
                            <div style="margin-top:12px;">
                                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/news/images.php?id=<?php echo (int)$row['news_id']; ?>"><i class="fas fa-images"></i> Quản lý ảnh</a>
                            </div>
                        </div>
                        <div class="form-row">
                            <label>Nội dung</label>
                            <textarea name="content" rows="12"><?php echo htmlspecialchars($row['content']); ?></textarea>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="card" style="margin-bottom:20px;">
                        <div class="card-header">Xuất Bản</div>
                        <div class="card-body">
                            <div class="form-row">
                                <label>Ngày xuất bản</label>
                                <input type="date" name="publish_date" value="<?php echo htmlspecialchars($row['publish_date']); ?>">
                            </div>
                            <div class="form-row">
                                <label>Tác giả</label>
                                <input type="text" name="author" value="<?php echo htmlspecialchars($row['author']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">Tags</div>
                        <div class="card-body">
                            <div class="checkbox-list">
                                <?php foreach ($all_tags as $tg): $checked = in_array($tg, $current_tags); ?>
                                    <label><input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($tg); ?>" <?php echo $checked ? 'checked' : ''; ?>> <span><?php echo htmlspecialchars($tg); ?></span></label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-row" style="margin-top:16px;">
                                <label>Thêm Tag Mới</label>
                                <input type="text" name="new_tag" placeholder="tag1, tag2...">
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
                        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/news/index.php"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
    (function(){
        function slugify(str){
            return (str || '').toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
                .replace(/[^a-z0-9]+/g,'-')
                .replace(/^-+|-+$/g,'');
        }
        var titleInput = document.querySelector('input[name="title"]');
        var slugInput = document.getElementById('slug');
        var slugDirty = false;
        if (slugInput) {
            slugInput.addEventListener('input', function(){ slugDirty = true; });
        }
        if (titleInput && slugInput) {
            titleInput.addEventListener('input', function(){
                if (!slugDirty) {
                    slugInput.value = slugify(titleInput.value);
                }
            });
        }
    })();
</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>

