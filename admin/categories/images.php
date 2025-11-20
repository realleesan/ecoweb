<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$mode = isset($_GET['mode']) ? strtolower(trim((string) $_GET['mode'])) : 'manage';
$mode = in_array($mode, ['manage', 'picker'], true) ? $mode : 'manage';

$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$returnTo = isset($_GET['return_to']) ? trim((string) $_GET['return_to']) : '';

$uploadDir = BASE_PATH . '/uploads/categories';
$uploadUrl = BASE_URL . '/uploads/categories';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

$errors = [];
$successMessage = '';
$newImageUrl = '';

if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        $errors[] = 'Không thể tạo thư mục lưu ảnh. Vui lòng kiểm tra phân quyền.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

    if ($action === 'upload') {
        $file = $_FILES['image_file'] ?? null;

        if (!$file || !isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Không tìm thấy file tải lên.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Có lỗi xảy ra khi tải file. Mã lỗi: ' . (int) $file['error'];
        } elseif ($file['size'] > $maxFileSize) {
            $errors[] = 'Dung lượng file vượt quá giới hạn 5MB.';
        } else {
            $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'Định dạng file không được hỗ trợ. Vui lòng chọn JPG, PNG, GIF hoặc WEBP.';
            } else {
                $basename = pathinfo((string) ($file['name'] ?? ''), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('~[^\pL\d]+~u', '-', $basename ?? 'category-image');
                $sanitizedName = trim((string) $sanitizedName, '-');
                $sanitizedName = $sanitizedName !== '' ? strtolower($sanitizedName) : 'category-image';
                $uniqueSuffix = date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
                $fileName = $sanitizedName . '-' . $uniqueSuffix . '.' . $extension;
                $targetPath = $uploadDir . '/' . $fileName;

                if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $errors[] = 'Không thể lưu file lên máy chủ. Vui lòng thử lại.';
                } else {
                    $successMessage = 'Tải ảnh thành công.';
                    $newImageUrl = $uploadUrl . '/' . rawurlencode($fileName);
                }
            }
        }
    } elseif ($action === 'delete') {
        $fileName = isset($_POST['file']) ? basename((string) $_POST['file']) : '';
        if ($fileName === '') {
            $errors[] = 'Không xác định được file cần xóa.';
        } else {
            $filePath = $uploadDir . '/' . $fileName;
            if (!is_file($filePath)) {
                $errors[] = 'File không tồn tại hoặc đã được xóa.';
            } elseif (!@unlink($filePath)) {
                $errors[] = 'Không thể xóa file. Vui lòng thử lại.';
            } else {
                $successMessage = 'Đã xóa ảnh thành công.';
            }
        }
    }
}

$images = [];
if (is_dir($uploadDir)) {
    $files = @scandir($uploadDir) ?: [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            continue;
        }
        $fullPath = $uploadDir . '/' . $file;
        if (!is_file($fullPath)) {
            continue;
        }
        $images[] = [
            'name' => $file,
            'url' => $uploadUrl . '/' . rawurlencode($file),
            'size' => filesize($fullPath),
            'modified' => filemtime($fullPath),
        ];
    }
}

usort($images, static function (array $a, array $b): int {
    return ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0);
});

$pageTitle = $mode === 'picker' ? 'Chọn Ảnh Danh Mục' : 'Quản Lý Hình Ảnh Danh Mục';

include __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-layout {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 20px auto 40px;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: <?php echo GRID_GAP; ?>;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .admin-layout {
            grid-template-columns: 1fr;
        }
    }

    .images-manager {
        background-color: var(--white);
        border-radius: 18px;
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .images-manager__header {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .images-manager__title {
        font-size: 30px;
        font-weight: 700;
        color: var(--primary);
        margin: 0;
    }

    .breadcrumb {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 14px;
        flex-wrap: wrap;
    }

    .breadcrumb a {
        color: var(--secondary);
        text-decoration: none;
        font-weight: 600;
    }

    .breadcrumb span {
        color: rgba(0, 0, 0, 0.55);
    }

    .notice {
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .notice--success {
        background: rgba(63, 142, 63, 0.12);
        color: #2a6a2a;
        border: 1px solid rgba(63, 142, 63, 0.35);
    }

    .notice--error {
        background: rgba(210, 64, 38, 0.12);
        color: #a52f1c;
        border: 1px solid rgba(210, 64, 38, 0.35);
    }

    .images-manager__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 18px;
    }

    .image-card {
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: rgba(255, 247, 237, 0.8);
        border-radius: 16px;
        padding: 18px;
        border: 1px solid rgba(210, 100, 38, 0.18);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    }

    .image-card__preview {
        position: relative;
        padding-bottom: 62%;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        border: 1px dashed rgba(210, 100, 38, 0.25);
    }

    .image-card__preview img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-card__meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 13px;
        color: rgba(0, 0, 0, 0.65);
        word-break: break-word;
    }

    .image-card__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 16px;
        border-radius: 999px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--secondary), #f7c76a);
        color: var(--white);
        box-shadow: 0 12px 24px rgba(210, 100, 38, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 28px rgba(210, 100, 38, 0.3);
    }

    .btn-soft {
        background: rgba(210, 100, 38, 0.15);
        color: var(--secondary);
        border: 1px solid rgba(210, 100, 38, 0.2);
    }

    .btn-light {
        background: rgba(0, 0, 0, 0.05);
        color: var(--dark);
    }

    .btn-outline-danger {
        background: transparent;
        border: 1px solid rgba(210, 64, 38, 0.6);
        color: rgba(210, 64, 38, 0.95);
    }

    .upload-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid rgba(210, 100, 38, 0.18);
        padding: 22px 24px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .upload-card form {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-field label {
        font-weight: 600;
        color: var(--dark);
        font-size: 14px;
    }

    .form-field input[type="file"] {
        padding: 10px;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        background-color: #fff;
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 14px;
        padding: 40px 20px;
        border-radius: 16px;
        border: 1px dashed rgba(210, 100, 38, 0.25);
        background: rgba(255, 247, 237, 0.7);
        color: rgba(0, 0, 0, 0.6);
        text-align: center;
    }

    .picker-hint {
        font-size: 14px;
        color: rgba(0, 0, 0, 0.6);
        background: rgba(60, 96, 60, 0.08);
        border-left: 3px solid var(--primary);
        padding: 12px 16px;
        border-radius: 12px;
    }

    .category-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(60, 96, 60, 0.12);
        color: var(--primary);
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="images-manager" aria-labelledby="images-manager-title">
        <div class="images-manager__header">
            <h1 class="images-manager__title" id="images-manager-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                <span>/</span>
                <a href="<?php echo BASE_URL; ?>/admin/categories/index.php">Quản Lý Danh Mục</a>
                <span>/</span>
                <span><?php echo htmlspecialchars($pageTitle); ?></span>
            </nav>
            <?php if ($mode === 'picker'): ?>
                <div class="picker-hint">
                    <i class="fas fa-info-circle"></i>
                    Chọn một ảnh bên dưới để sử dụng cho danh mục của bạn. Cửa sổ này sẽ tự đóng sau khi bạn chọn ảnh.
                </div>
            <?php endif; ?>
        </div>

        <?php if ($successMessage): ?>
            <div class="notice notice--success" role="status">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="notice notice--error" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Không thể thực hiện thao tác:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <section class="upload-card" aria-label="Tải ảnh mới">
            <h2 class="card__title" style="font-size: 20px; font-weight: 600; color: var(--dark); margin: 0;">Upload Ảnh Mới</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="form-field">
                    <label for="image_file">Chọn ảnh</label>
                    <input type="file" id="image_file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <small>Định dạng: JPG, PNG, GIF, WEBP. Kích thước tối đa: 5MB.</small>
                </div>
                <div class="form-field">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Ảnh</button>
                </div>
            </form>
        </section>

        <section aria-label="Danh sách ảnh hiện có">
            <h2 class="card__title" style="font-size: 20px; font-weight: 600; color: var(--dark); margin: 0 0 12px; display: flex; justify-content: space-between; align-items: center;">
                Ảnh Hiện Tại
                <span style="font-size: 14px; color: rgba(0,0,0,0.55); font-weight: 500;">Tổng: <?php echo count($images); ?></span>
            </h2>

            <?php if (!$images): ?>
                <div class="empty-state">
                    <i class="fas fa-image" style="font-size: 40px; color: var(--secondary);"></i>
                    <p>Chưa có ảnh nào. Hãy upload ảnh đầu tiên cho danh mục.</p>
                </div>
            <?php else: ?>
                <div class="images-manager__grid">
                    <?php foreach ($images as $image): ?>
                        <article class="image-card" data-image-url="<?php echo htmlspecialchars($image['url']); ?>" data-file-name="<?php echo htmlspecialchars($image['name']); ?>">
                            <div class="image-card__preview">
                                <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="Ảnh danh mục">
                            </div>
                            <div class="image-card__meta">
                                <strong><?php echo htmlspecialchars($image['name']); ?></strong>
                                <span>Kích thước: <?php echo number_format((int) $image['size'] / 1024, 1); ?> KB</span>
                                <span>Cập nhật: <?php echo date(DATETIME_FORMAT, (int) $image['modified']); ?></span>
                                <?php if ($categoryId): ?>
                                    <span class="category-tag"><i class="fas fa-folder-open"></i> Danh mục #<?php echo (int) $categoryId; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="image-card__actions">
                                <button type="button" class="btn btn-primary" data-action="select-image"><i class="fas fa-check"></i> Chọn ảnh</button>
                                <button type="button" class="btn btn-soft" data-action="copy-url"><i class="fas fa-link"></i> Sao chép URL</button>
                                <form method="post" style="margin: 0;" onsubmit="return confirm('Bạn có chắc muốn xóa ảnh này?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file" value="<?php echo htmlspecialchars($image['name']); ?>">
                                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i> Xóa</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    (function () {
        const mode = <?php echo json_encode($mode); ?>;
        const newImageUrl = <?php echo json_encode($newImageUrl); ?>;

        function formatClipboardText(url) {
            return url;
        }

        async function copyToClipboard(url, button) {
            try {
                await navigator.clipboard.writeText(formatClipboardText(url));
                if (button) {
                    const original = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-check"></i> Đã sao chép!';
                    button.disabled = true;
                    setTimeout(() => {
                        button.innerHTML = original;
                        button.disabled = false;
                    }, 1600);
                }
            } catch (error) {
                alert('Không thể sao chép URL. Vui lòng thử lại.');
            }
        }

        function emitSelection(url) {
            if (mode === 'picker') {
                const returnTo = <?php echo json_encode($returnTo); ?>;
                if (returnTo) {
                    const separator = returnTo.includes('?') ? '&' : '?';
                    window.location.href = returnTo + separator + 'selected_image=' + encodeURIComponent(url);
                    return;
                }
            }
            if (window.opener && typeof window.opener.postMessage === 'function') {
                window.opener.postMessage({
                    type: 'category-image-selected',
                    url: url
                }, '*');
            }
            if (mode === 'picker') {
                setTimeout(() => window.close(), 200);
            }
        }

        document.querySelectorAll('[data-action="copy-url"]').forEach((button) => {
            button.addEventListener('click', (event) => {
                const card = event.currentTarget.closest('.image-card');
                if (!card) {
                    return;
                }
                const url = card.dataset.imageUrl;
                copyToClipboard(url, event.currentTarget);
            });
        });

        document.querySelectorAll('[data-action="select-image"]').forEach((button) => {
            button.addEventListener('click', (event) => {
                const card = event.currentTarget.closest('.image-card');
                if (!card) {
                    return;
                }
                emitSelection(card.dataset.imageUrl);
            });
        });

        if (mode === 'picker' && newImageUrl) {
            emitSelection(newImageUrl);
        }
    })();
</script>
