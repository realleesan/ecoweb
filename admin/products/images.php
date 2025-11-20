<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


$uploadDir = BASE_PATH . '/uploads/products';
$uploadUrl = BASE_URL . '/uploads/products';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB


if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}


$errors = [];
$successMessages = [];


$productId = isset($_GET['id']) ? max(0, (int)$_GET['id']) : 0;
if ($productId <= 0) {
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}


try {
    $pdo = getPDO();
} catch (Exception $e) {
    $pdo = null;
    $errors[] = 'Không thể kết nối cơ sở dữ liệu.';
}


$product = null;
$images = [];


if ($pdo) {
    try {
        $pdo->exec('CREATE TABLE IF NOT EXISTS product_images (
            image_id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) DEFAULT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product (product_id),
            CONSTRAINT fk_product_images_product FOREIGN KEY (product_id)
                REFERENCES products(product_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (Exception $e) {
        $errors[] = 'Không thể khởi tạo bảng hình ảnh sản phẩm.';
    }


    try {
        $stmt = $pdo->prepare('SELECT product_id, code, name FROM products WHERE product_id = :id');
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            header('Location: ' . BASE_URL . '/admin/products/index.php');
            exit;
        }
    } catch (Exception $e) {
        $errors[] = 'Không thể tải thông tin sản phẩm.';
    }
}


$formData = [
    'image_url' => '',
    'alt_text' => '',
];


if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';


    if ($action === 'add_image') {
        $formData['image_url'] = trim($_POST['image_url'] ?? '');
        $formData['alt_text'] = trim($_POST['alt_text'] ?? '');


        $uploadedFileName = null;
        if (!empty($_FILES['image_file']['name'])) {
            $file = $_FILES['image_file'];
            if (!isset($file['error']) || is_array($file['error'])) {
                $errors[] = 'Không thể tải file hình ảnh. Vui lòng thử lại.';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Có lỗi xảy ra khi tải file (mã lỗi ' . (int) $file['error'] . ').';
            } elseif ($file['size'] > $maxFileSize) {
                $errors[] = 'Dung lượng file vượt quá giới hạn 5MB.';
            } else {
                $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = 'Định dạng file không được hỗ trợ. Vui lòng chọn JPG, PNG, GIF hoặc WEBP.';
                } else {
                    $basename = pathinfo((string) ($file['name'] ?? ''), PATHINFO_FILENAME);
                    $sanitizedName = preg_replace('~[^\pL\d]+~u', '-', $basename ?? 'product-image');
                    $sanitizedName = trim((string) $sanitizedName, '-');
                    $sanitizedName = $sanitizedName !== '' ? strtolower($sanitizedName) : 'product-image';
                    $uniqueSuffix = date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
                    $fileName = $sanitizedName . '-' . $uniqueSuffix . '.' . $extension;
                    $targetPath = $uploadDir . '/' . $fileName;


                    if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $errors[] = 'Không thể lưu file hình ảnh trên máy chủ. Vui lòng thử lại.';
                    } else {
                        $uploadedFileName = $fileName;
                        $formData['image_url'] = $uploadUrl . '/' . rawurlencode($fileName);
                    }
                }
            }
        }


        if ($formData['image_url'] === '') {
            $errors[] = 'Vui lòng tải lên file hình ảnh.';
        } elseif (strlen($formData['image_url']) > 255) {
            $errors[] = 'Đường dẫn hình ảnh quá dài (tối đa 255 ký tự).';
        }
        if ($formData['alt_text'] !== '' && strlen($formData['alt_text']) > 255) {
            $errors[] = 'Văn bản ALT quá dài (tối đa 255 ký tự).';
        }


        if (empty($errors)) {
            try {
                $insert = $pdo->prepare('INSERT INTO product_images (product_id, image_url, alt_text, is_primary)
                    VALUES (:pid, :url, :alt, 0)');
                $insert->execute([
                    ':pid' => $productId,
                    ':url' => $formData['image_url'],
                    ':alt' => $formData['alt_text'] !== '' ? $formData['alt_text'] : null,
                ]);

                $successMessages[] = 'Đã thêm hình ảnh mới.';
                $formData = [
                    'image_url' => '',
                    'alt_text' => '',
                ];
            } catch (Exception $e) {
                if ($uploadedFileName) {
                    @unlink($uploadDir . '/' . $uploadedFileName);
                }
                $errors[] = 'Không thể thêm hình ảnh. Vui lòng thử lại.';
            }
        }
        if (!empty($errors) && isset($uploadedFileName)) {
            @unlink($uploadDir . '/' . $uploadedFileName);
        }
    } elseif ($action === 'delete_image') {
        $imageId = isset($_POST['image_id']) ? (int) $_POST['image_id'] : 0;

        if ($imageId <= 0) {
            $errors[] = 'Hình ảnh không hợp lệ.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT product_id FROM product_images WHERE image_id = :id');
                $stmt->execute([':id' => $imageId]);
                $imageRow = $stmt->fetch();

                if (!$imageRow) {
                    $errors[] = 'Hình ảnh không tồn tại hoặc đã bị xóa.';
                } else {
                    $delete = $pdo->prepare('DELETE FROM product_images WHERE image_id = :id');
                    $delete->execute([':id' => $imageId]);

                    if ($delete->rowCount() > 0) {
                        $successMessages[] = 'Đã xóa hình ảnh.';
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'Không thể xóa hình ảnh. Vui lòng thử lại.';
            }
        }
    }
}


if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT image_id, image_url, alt_text, created_at
                FROM product_images
                WHERE product_id = :pid
                ORDER BY created_at DESC');
        $stmt->execute([':pid' => $productId]);
        $images = $stmt->fetchAll();
    } catch (Exception $e) {
        $images = [];
        $errors[] = 'Không thể tải danh sách hình ảnh.';
    }
}


include __DIR__ . '/../includes/header.php';
?>


<style>
    .images-page {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 20px auto;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
    }

    .images-header {
        background: linear-gradient(135deg, rgba(60, 96, 60, 0.08), rgba(210, 100, 38, 0.05));
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .images-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 8px 0;
    }

    .images-header .breadcrumb {
        color: #666;
        font-size: 13px;
    }

    .images-header .breadcrumb a {
        color: var(--secondary);
        font-weight: 600;
        text-decoration: none;
    }

    .images-header .breadcrumb a:hover {
        text-decoration: underline;
    }

    .alert {
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert-error {
        border: 1px solid #f0c7c7;
        background: #fff2f0;
        color: #c74343;
    }

    .alert-success {
        border: 1px solid #b7eb8f;
        background: #f6ffed;
        color: #256029;
    }

    .upload-section {
        background: #fff;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        padding: 24px;
        margin-bottom: 24px;
    }

    .upload-section h2 {
        font-size: 16px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 18px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .upload-section h2 i {
        color: var(--secondary);
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
    }

    .form-field label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #666;
        font-weight: 600;
    }

    .form-field input[type="text"],
    .form-field input[type="file"] {
        padding: 11px 14px;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        background: #fff;
        outline: none;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-field input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(60, 96, 60, 0.15);
    }

    .form-field small {
        color: #999;
        font-size: 12px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--secondary), var(--primary));
        color: #fff;
        box-shadow: 0 8px 18px rgba(60, 96, 60, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
    }

    .images-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }

    .image-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .image-card-img {
        width: 100%;
        height: 200px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .image-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-card-body {
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
    }

    .image-card-name {
        font-size: 12px;
        color: #999;
        word-break: break-all;
        line-height: 1.4;
    }

    .image-card-alt {
        font-size: 13px;
        color: #555;
        font-weight: 500;
    }

    .image-card-date {
        font-size: 11px;
        color: #bbb;
    }

    .image-card-actions {
        display: flex;
        gap: 8px;
        padding-top: 10px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .icon-btn {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 8px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        background: #fff;
        color: var(--primary);
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .icon-btn:hover {
        background: rgba(60, 96, 60, 0.12);
        color: var(--secondary);
    }

    .icon-btn-danger {
        color: #c74343;
    }

    .icon-btn-danger:hover {
        background: rgba(199, 67, 67, 0.12);
        color: #a03030;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    .empty-state i {
        font-size: 48px;
        color: #ddd;
        margin-bottom: 12px;
    }

    .empty-state p {
        font-size: 14px;
        margin: 0;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .images-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }
</style>


<div class="images-page">
    <div class="images-header">
        <h1>Quản Lý Hình Ảnh Sản Phẩm</h1>
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a> /
            <a href="<?php echo BASE_URL; ?>/admin/products/index.php">Quản lý sản phẩm</a> /
            <a href="<?php echo BASE_URL; ?>/admin/products/edit.php?id=<?php echo $productId; ?>">Sửa sản phẩm</a> /
            <span>Hình ảnh</span>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMessages)): ?>
        <div class="alert alert-success">
            <?php echo implode('<br>', array_map('htmlspecialchars', $successMessages)); ?>
        </div>
    <?php endif; ?>

    <div class="upload-section">
        <h2><i class="fas fa-cloud-upload-alt"></i> Tải Lên Hình Ảnh</h2>

        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_image">

            <div class="form-field">
                <label>Chọn tệp *</label>
                <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.gif,.webp" required>
                <small>Định dạng: JPG, PNG, GIF, WEBP. Kích thước tối đa: 5MB.</small>
            </div>

            <div class="form-field">
                <label>Văn bản ALT (Không bắt buộc)</label>
                <input type="text" name="alt_text" placeholder="Mô tả ngắn gọn cho hình ảnh" value="<?php echo htmlspecialchars($formData['alt_text']); ?>">
                <small>Giúp cải thiện SEO và khả năng truy cập.</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Tải Lên</button>
            </div>
        </form>
    </div>

    <div>
        <h2 style="font-size: 16px; font-weight: 700; color: var(--primary); margin: 0 0 18px 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-images" style="color: var(--secondary);"></i> Ảnh Hiện Tại (<?php echo count($images); ?>)
        </h2>

        <?php if (empty($images)): ?>
            <div class="empty-state">
                <i class="fas fa-image"></i>
                <p>Chưa có hình ảnh nào. Hãy tải lên hình ảnh đầu tiên cho sản phẩm này.</p>
            </div>
        <?php else: ?>
            <div class="images-grid">
                <?php foreach ($images as $image): ?>
                    <div class="image-card">
                        <div class="image-card-img">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?? ''); ?>">
                        </div>
                        <div class="image-card-body">
                            <div class="image-card-name"><?php echo htmlspecialchars(basename($image['image_url'])); ?></div>
                            <?php if ($image['alt_text']): ?>
                                <div class="image-card-alt"><?php echo htmlspecialchars($image['alt_text']); ?></div>
                            <?php endif; ?>
                            <div class="image-card-date"><?php echo date('d/m/Y H:i', strtotime($image['created_at'])); ?></div>
                            <div class="image-card-actions">
                                <form method="post" action="" style="flex: 1;">
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="image_id" value="<?php echo (int) $image['image_id']; ?>">
                                    <button type="submit" class="icon-btn icon-btn-danger" onclick="return confirm('Bạn chắc chắn muốn xóa hình ảnh này?');">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>
