<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


$pdo = getPDO();


try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS news_tag_master (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tag VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(150) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
} catch (Exception $e) {}


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = null;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT news_id, title FROM news WHERE news_id = :id');
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch();
    } catch (Exception $e) { $article = null; }
}


function make_slug($text) {
    $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
    return trim($base, '-');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' && $id > 0) {
        $tag = trim($_POST['tag'] ?? '');
        if ($tag !== '') {
            try {
                $stmt = $pdo->prepare('INSERT INTO news_tags (news_id, tag) VALUES (:id, :tag) ON DUPLICATE KEY UPDATE tag = VALUES(tag)');
                $stmt->execute([':id' => $id, ':tag' => $tag]);
            } catch (Exception $e) {}
            try {
                $slug = make_slug($tag);
                $ck = $pdo->prepare('SELECT COUNT(*) FROM news_tag_master WHERE tag = :tag');
                $ck->execute([':tag' => $tag]);
                if ((int)$ck->fetchColumn() === 0) {
                    $dup = $pdo->prepare('SELECT COUNT(*) FROM news_tag_master WHERE slug = :slug');
                    $dup->execute([':slug' => $slug]);
                    if ((int)$dup->fetchColumn() > 0) { $slug .= '-' . substr(sha1(uniqid('', true)), 0, 6); }
                    $pdo->prepare('INSERT INTO news_tag_master (tag, slug) VALUES (:tag, :slug)')->execute([':tag' => $tag, ':slug' => $slug]);
                }
            } catch (Exception $e) {}
        }
    } elseif ($action === 'delete' && $id > 0) {
        $tag = trim($_POST['tag'] ?? '');
        if ($tag !== '') {
            try {
                $stmt = $pdo->prepare('DELETE FROM news_tags WHERE news_id = :id AND tag = :tag');
                $stmt->execute([':id' => $id, ':tag' => $tag]);
            } catch (Exception $e) {}
        }
    } elseif ($action === 'delete_global') {
        $tag = trim($_POST['tag'] ?? '');
        if ($tag !== '') {
            try {
                $stmt = $pdo->prepare('DELETE FROM news_tags WHERE tag = :tag');
                $stmt->execute([':tag' => $tag]);
            } catch (Exception $e) {}
            try { $pdo->prepare('DELETE FROM news_tag_master WHERE tag = :tag')->execute([':tag' => $tag]); } catch (Exception $e) {}
        }
    } elseif ($action === 'add_master') {
        $tag = trim($_POST['tag'] ?? '');
        if ($tag !== '') {
            $slug = make_slug($tag);
            try {
                $check = $pdo->prepare('SELECT COUNT(*) FROM news_tag_master WHERE slug = :slug OR tag = :tag');
                $check->execute([':slug' => $slug, ':tag' => $tag]);
                $exists = (int)$check->fetchColumn();
                if ($exists > 0) { $slug .= '-' . substr(sha1(uniqid('', true)), 0, 6); }
                $pdo->prepare('INSERT INTO news_tag_master (tag, slug) VALUES (:tag, :slug)')->execute([':tag' => $tag, ':slug' => $slug]);
            } catch (Exception $e) {}
        }
    } elseif ($action === 'update_master') {
        $old_tag = trim($_POST['old_tag'] ?? '');
        $new_tag = trim($_POST['new_tag'] ?? '');
        $new_slug = trim($_POST['new_slug'] ?? '');
        if ($old_tag !== '' && $new_tag !== '') {
            if ($new_slug === '') { $new_slug = make_slug($new_tag); }
            try {
                $check = $pdo->prepare('SELECT COUNT(*) FROM news_tag_master WHERE slug = :slug AND tag <> :tag');
                $check->execute([':slug' => $new_slug, ':tag' => $old_tag]);
                $exists = (int)$check->fetchColumn();
                if ($exists > 0) { $new_slug .= '-' . substr(sha1(uniqid('', true)), 0, 6); }
                $pdo->prepare('UPDATE news_tag_master SET tag = :new_tag, slug = :new_slug WHERE tag = :old_tag')->execute([
                    ':new_tag' => $new_tag, ':new_slug' => $new_slug, ':old_tag' => $old_tag
                ]);
                $pdo->prepare('UPDATE news_tags SET tag = :new_tag WHERE tag = :old_tag')->execute([':new_tag' => $new_tag, ':old_tag' => $old_tag]);
            } catch (Exception $e) {}
        }
    }
    $redirect = $id > 0 ? (BASE_URL . '/admin/news/tags.php?id=' . $id) : (BASE_URL . '/admin/news/tags.php');
    header('Location: ' . $redirect);
    exit;
}


$tags = [];
if ($id > 0) {
    try {
        $tags = $pdo->prepare('SELECT tag FROM news_tags WHERE news_id = :id ORDER BY tag ASC');
        $tags->execute([':id' => $id]);
        $tags = $tags->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) { $tags = []; }
} else {
    // Phân trang danh sách tags lấy từ master, đếm số bài sử dụng từ news_tags
    $itemsPerPage = 6;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $itemsPerPage;


    $total = 0;
    try {
        $countStmt = $pdo->query('SELECT COUNT(*) FROM (
            SELECT tag FROM news_tag_master
            UNION SELECT DISTINCT tag FROM news_tags
        ) u');
        $total = (int)$countStmt->fetchColumn();
    } catch (Exception $e) { $total = 0; }
    $total_pages = $total > 0 ? (int)ceil($total / $itemsPerPage) : 0;


    try {
        $stmt = $pdo->prepare('SELECT u.tag, COALESCE(m.slug, "") AS slug,
                                   (SELECT COUNT(*) FROM news_tags t WHERE t.tag = u.tag) AS total
                               FROM (
                                   SELECT tag FROM news_tag_master
                                   UNION SELECT DISTINCT tag FROM news_tags
                               ) u
                               LEFT JOIN news_tag_master m ON m.tag = u.tag
                               ORDER BY u.tag ASC
                               LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tags = $stmt->fetchAll();
    } catch (Exception $e) { $tags = []; }
}


$edit_tag = isset($_GET['edit']) ? trim($_GET['edit']) : '';
$edit_row = null;
if ($edit_tag !== '') {
    try {
        $st = $pdo->prepare('SELECT tag, slug FROM news_tag_master WHERE tag = :tag');
        $st->execute([':tag' => $edit_tag]);
        $edit_row = $st->fetch();
    } catch (Exception $e) { $edit_row = null; }
}


include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; }
    .page-title { font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-size: 26px; color: var(--primary); font-weight: 700; margin-bottom: 15px; }
    .grid { display: grid; grid-template-columns: 1fr 2fr; gap: <?php echo GRID_GAP_SMALL; ?>; }
    .card { background-color: var(--white); border: 1px solid #eee; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
    .card-header { padding: 14px 18px; border-bottom: 1px solid #eee; font-weight: 600; color: var(--dark); }
    .card-body { padding: 18px; }
    .form-row { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
    .form-row input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; cursor: pointer; border: none; text-decoration: none; }
    .btn-primary { background-color: var(--secondary); color: var(--white); }
    .btn-outline { border: 1px solid var(--primary); color: var(--primary); background: transparent; }
    .btn-sm { padding: 6px 10px; font-size: 13px; border-radius: 6px; }
    .table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .table th, .table td { border-bottom: 1px solid #eee; padding: 8px 10px; text-align: left; font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-size: 14px; vertical-align: middle; }
    .table th { background-color: var(--light); font-weight: 600; color: var(--dark); }
    .badge { display: inline-block; background-color: rgba(63,160,79,0.13); color: #3fa04f; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 11px; text-decoration: none; color: var(--dark); background: rgba(0,0,0,0.06); transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .icon-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 14px rgba(0,0,0,0.12); }
    .inline-actions { display: flex; flex-direction: column; align-items: center; gap: 10px; width: 100%; }
    .inline-edit-form { display: grid; grid-template-columns: minmax(160px, 1fr) minmax(160px, 1fr) auto; column-gap: 8px; align-items: center; width: 100%; }
    .inline-edit-form input[type="text"] { width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; height: 34px; }
    .row-editing { background-color: var(--light); }
    .pagination-wrap { margin-top: 16px; }
    .pagination-wrap .pagination-component { flex-wrap: nowrap; overflow-x: auto; }
</style>


<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-content">
        <?php
            if ($id > 0) {
                $page_title = 'Quản Lý Tags - ' . htmlspecialchars($article['title']);
                $breadcrumbs = [
                    ['text' => 'Dashboard', 'url' => BASE_URL . '/admin/index.php'],
                    ['text' => 'Quản Lý Tin Tức', 'url' => BASE_URL . '/admin/news/index.php'],
                    ['text' => 'Sửa Tin Tức', 'url' => BASE_URL . '/admin/news/edit.php?id=' . (int)$id],
                    ['text' => 'Tags', 'url' => '']
                ];
            } else {
                $page_title = 'Quản Lý Tags Tin Tức';
                $breadcrumbs = [
                    ['text' => 'Dashboard', 'url' => BASE_URL . '/admin/index.php'],
                    ['text' => 'Quản Lý Tin Tức', 'url' => BASE_URL . '/admin/news/index.php'],
                    ['text' => 'Tags', 'url' => '']
                ];
            }
            include __DIR__ . '/../../includes/components/page-header.php';
        ?>


        <?php if ($id > 0): ?>
            <div class="page-title">Tags: <?php echo htmlspecialchars($article['title']); ?></div>
            <div class="card">
                <div class="card-body">
                    <form method="post" style="display:flex; gap:10px; align-items:center;">
                        <input type="hidden" name="action" value="add">
                        <input type="text" name="tag" placeholder="Nhập tag">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Tag</button>
                        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/news/edit.php?id=<?php echo (int)$id; ?>">Quay lại bài viết</a>
                    </form>
                </div>
            </div>
            <div style="margin-top:14px;" class="card">
                <div class="card-header">Tags hiện tại</div>
                <div class="card-body">
                    <?php if (empty($tags)): ?>
                        <div>Chưa có tag</div>
                    <?php else: ?>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php foreach ($tags as $t): ?>
                            <span class="badge"><?php echo htmlspecialchars($t); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="grid">
                <div class="card">
                    <div class="card-header"><?php echo $edit_row ? 'Sửa Tag' : 'Thêm Tag Mới'; ?></div>
                    <div class="card-body">
                        <?php if ($edit_row): ?>
                            <div style="margin-bottom:10px;"><a class="btn btn-outline btn-sm" href="<?php echo BASE_URL; ?>/admin/news/tags.php"><i class="fas fa-arrow-left"></i> Hủy sửa</a></div>
                            <form method="post">
                                <input type="hidden" name="action" value="update_master">
                                <input type="hidden" name="old_tag" value="<?php echo htmlspecialchars($edit_row['tag']); ?>">
                                <div class="form-row">
                                    <label>Tên Tag *</label>
                                    <input type="text" name="new_tag" value="<?php echo htmlspecialchars($edit_row['tag']); ?>" required>
                                </div>
                                <div style="color:#777; font-size:13px;">Slug sẽ tự động tạo từ tên tag</div>
                                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Cập Nhật</button>
                            </form>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="action" value="add_master">
                                <div class="form-row">
                                    <label>Tên Tag *</label>
                                    <input type="text" name="tag" placeholder="VD: Yến sào, Sức khỏe..." required>
                                </div>
                                <div style="color:#777; font-size:13px;">Slug sẽ tự động tạo từ tên tag</div>
                                <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Thêm Tag</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">Danh Sách Tags</div>
                    <div class="card-body">
                        <table class="table">
                            <colgroup>
                                <col style="width:28%"><col style="width:22%"><col style="width:15%"><col style="width:35%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Tên Tag</th>
                                    <th>Slug</th>
                                    <th>Sử dụng</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tags)): ?>
                                    <tr><td colspan="4">Chưa có tag</td></tr>
                                <?php else: foreach ($tags as $row): ?>
                                    <tr class="<?php echo ($edit_tag === $row['tag']) ? 'row-editing' : ''; ?>">
                                        <td><?php echo htmlspecialchars($row['tag']); ?></td>
                                        <td><span style="color: var(--primary);"><?php echo htmlspecialchars(!empty($row['slug']) ? $row['slug'] : make_slug($row['tag'])); ?></span></td>
                                        <td><span class="badge"><?php echo (int)$row['total']; ?> bài viết</span></td>
                                        <td>
                                            <div class="inline-actions">
                                                <a class="icon-btn edit" href="<?php echo BASE_URL; ?>/admin/news/tags.php?edit=<?php echo urlencode($row['tag']); ?>&page=<?php echo isset($page)? (int)$page : 1; ?>" title="Sửa"><i class="fas fa-pen"></i></a>
                                                <form method="post" onsubmit="return confirm('Xóa tag này?');">
                                                    <input type="hidden" name="action" value="delete_global">
                                                    <input type="hidden" name="tag" value="<?php echo htmlspecialchars($row['tag']); ?>">
                                                    <button class="icon-btn delete" title="Xóa"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                        <?php if ($id === 0): ?>
                        <div class="pagination-wrap">
                            <?php
                                $base_url = BASE_URL . '/admin/news/tags.php?';
                                $current_page = isset($page) ? $page : 1;
                                include __DIR__ . '/../../includes/components/pagination.php';
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>

