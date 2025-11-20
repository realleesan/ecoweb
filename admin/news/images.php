<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


$pdo = getPDO();


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ' . BASE_URL . '/admin/news/index.php'); exit; }


$article = null;
try {
    $stmt = $pdo->prepare('SELECT news_id, title FROM news WHERE news_id = :id');
    $stmt->execute([':id' => $id]);
    $article = $stmt->fetch();
} catch (Exception $e) { $article = null; }
if (!$article) { header('Location: ' . BASE_URL . '/admin/news/index.php'); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'upload_file') {
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            $name = $_FILES['image_file']['name'];
            $size = (int)$_FILES['image_file']['size'];
            $tmp  = $_FILES['image_file']['tmp_name'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $size > 0 && $size <= $maxSize) {
                $uploadDir = PUBLIC_PATH . '/uploads/news';
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
                $safeBase = preg_replace('/[^a-z0-9\-]+/i', '-', $article['title']);
                $filename = $safeBase . '-' . $id . '-' . date('YmdHis') . '-' . substr(sha1(uniqid('', true)), 0, 6) . '.' . $ext;
                $destPath = $uploadDir . '/' . $filename;
                if (@move_uploaded_file($tmp, $destPath)) {
                    $url = BASE_URL . '/public/uploads/news/' . $filename;
                    try {
                        $stmt = $pdo->prepare('INSERT INTO news_images (news_id, image_url, caption, display_order) VALUES (:id, :url, NULL, 0)');
                        $stmt->execute([':id' => $id, ':url' => $url]);
                    } catch (Exception $e) {}
                }
            }
        }
    } elseif ($action === 'delete') {
        $image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
        if ($image_id > 0) {
            try {
                $stmt = $pdo->prepare('SELECT image_url FROM news_images WHERE id = :image_id AND news_id = :id');
                $stmt->execute([':image_id' => $image_id, ':id' => $id]);
                $imgRow = $stmt->fetch();
            } catch (Exception $e) { $imgRow = null; }
            try {
                $stmt = $pdo->prepare('DELETE FROM news_images WHERE id = :image_id AND news_id = :id');
                $stmt->execute([':image_id' => $image_id, ':id' => $id]);
                if ($imgRow && !empty($imgRow['image_url'])) {
                    $local = str_replace(BASE_URL, '', $imgRow['image_url']);
                    $abs = BASE_PATH . $local;
                    if (is_file($abs)) { @unlink($abs); }
                }
            } catch (Exception $e) {}
        }
    } elseif ($action === 'update_order') {
        $image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        if ($image_id > 0) {
            try {
                $stmt = $pdo->prepare('UPDATE news_images SET display_order = :ord WHERE id = :image_id AND news_id = :id');
                $stmt->execute([':ord' => $display_order, ':image_id' => $image_id, ':id' => $id]);
            } catch (Exception $e) {}
        }
    }
    header('Location: ' . BASE_URL . '/admin/news/images.php?id=' . $id);
    exit;
}


$images = [];
try {
    $stmt = $pdo->prepare('SELECT id, image_url, caption, display_order FROM news_images WHERE news_id = :id ORDER BY display_order ASC, id ASC');
    $stmt->execute([':id' => $id]);
    $images = $stmt->fetchAll();
} catch (Exception $e) { $images = []; }
$featured = !empty($images) ? $images[0] : null;


include __DIR__ . '/../includes/header.php';
?>


<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; }
    .page-title { font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-size: 26px; color: var(--primary); font-weight: 700; margin-bottom: 15px; }
    .card { background-color: var(--white); border: 1px solid #eee; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
    .card-header { padding: 14px 18px; border-bottom: 1px solid #eee; font-weight: 600; color: var(--dark); }
    .card-body { padding: 18px; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; cursor: pointer; border: none; text-decoration: none; }
    .btn-primary { background-color: var(--primary); color: var(--white); }
    .btn-outline { border: 1px solid var(--primary); color: var(--primary); background: transparent; }
    .btn-danger { background-color: #dc3545; color: var(--white); }
    .thumb { width: 160px; height: 120px; object-fit: cover; border-radius: 8px; background-color: #f0f0f0; display: block; }
    .image-list { margin-top: 20px; }
    .image-item { display: flex; align-items: center; gap: 15px; padding: 12px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; background-color: #f9f9f9; }
    .image-preview { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; }
    .image-actions { display: flex; gap: 8px; }
    .image-actions .icon-btn { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:11px; color:var(--dark); background:rgba(0,0,0,0.06); text-decoration:none; transition:transform 0.2s ease, box-shadow 0.2s ease; }
    .image-actions .icon-btn:hover { transform: translateY(-2px); box-shadow:0 8px 14px rgba(0,0,0,0.12); }
</style>


<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-content">
        <?php
            $page_title = 'Quản Lý Hình Ảnh - ' . htmlspecialchars($article['title']);
            $breadcrumbs = [
                ['text' => 'Dashboard', 'url' => BASE_URL . '/admin/index.php'],
                ['text' => 'Quản Lý Tin Tức', 'url' => BASE_URL . '/admin/news/index.php'],
                ['text' => 'Sửa Tin Tức', 'url' => BASE_URL . '/admin/news/edit.php?id=' . (int)$id],
                ['text' => 'Quản Lý Hình Ảnh', 'url' => '']
            ];
            include __DIR__ . '/../../includes/components/page-header.php';
        ?>


        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">Upload Ảnh Đại Diện</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_file">
                    <div class="form-group" style="margin-bottom:10px;">
                        <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.gif,.webp" required>
                    </div>
                    <div style="color:#777; font-size:13px; margin-bottom:10px;">Định dạng: JPG, PNG, GIF, WEBP. Kích thước tối đa: 5MB</div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Ảnh</button>
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/news/edit.php?id=<?php echo (int)$id; ?>">Quay lại</a>
                </form>
            </div>
        </div>


        <div class="card">
            <div class="card-header">Ảnh Hiện Tại</div>
            <div class="card-body">
                <?php if (!$featured): ?>
                    <div>Chưa có ảnh</div>
                <?php else: ?>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <img src="<?php echo htmlspecialchars($featured['image_url']); ?>" alt="Featured" class="thumb" onerror="this.style.display='none'">
                        <form method="post" onsubmit="return confirm('Xóa ảnh này?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="image_id" value="<?php echo (int)$featured['id']; ?>">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Xóa Ảnh</button>
                            <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/news/edit.php?id=<?php echo (int)$id; ?>">Quay lại</a>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>


        <div class="image-list" style="margin-top:18px;">
            <?php if (count($images) > 1): ?>
                <?php foreach ($images as $img): ?>
                    <?php if ($featured && $img['id'] == $featured['id']) continue; ?>
                    <div class="image-item">
                        <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="Preview" class="image-preview" onerror="this.style.display='none'">
                        <div style="flex:1; color:#666; word-break: break-all;"><?php echo htmlspecialchars($img['image_url']); ?></div>
                        <div class="image-actions">
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="update_order">
                                <input type="hidden" name="image_id" value="<?php echo (int)$img['id']; ?>">
                                <input type="number" name="display_order" value="<?php echo (int)$img['display_order']; ?>" style="width: 60px;" min="0">
                                <button type="submit" class="btn btn-outline">Cập nhật</button>
                            </form>
                            <form method="post" style="display:inline" onsubmit="return confirm('Xóa hình ảnh này?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="image_id" value="<?php echo (int)$img['id']; ?>">
                                <button type="submit" class="btn btn-danger">Xóa</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>

