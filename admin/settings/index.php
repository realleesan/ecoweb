<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$pdo = getPDO();

$errors = [];
$successMessage = '';

// Lấy cài đặt hiện tại từ database hoặc file config
$settings = [
    'site_name' => defined('SITE_NAME') ? SITE_NAME : 'EcoWeb',
    'site_address_1' => '123 Đường Số 1, Phường 2',
    'site_address_2' => 'Quận 3, TP. Hồ Chí Minh',
    'site_phone' => '0909 123 456',
    'site_email' => 'info@ecoweb.com',
    'business_hours_weekday' => '8:00 - 17:00',
    'business_hours_weekend' => '8:00 - 12:00',
    'facebook_url' => 'https://facebook.com/ecoweb',
    'instagram_url' => 'https://instagram.com/ecoweb',
    'youtube_url' => 'https://youtube.com/@ecoweb',
    'tiktok_url' => 'https://tiktok.com/@ecoweb',
    'zalo_url' => 'https://zalo.me/0909123456',
    'seo_keywords' => 'cây xanh, trồng cây, phủ xanh đồi trọc, môi trường',
    'seo_description' => 'EcoWeb - Nền tảng kết nối khách hàng mua cây trồng và thuê đất để phủ xanh đồi trọc, bảo vệ môi trường.',
    'google_analytics_id' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['site_name'] = isset($_POST['site_name']) ? trim($_POST['site_name']) : '';
    $settings['site_address_1'] = isset($_POST['site_address_1']) ? trim($_POST['site_address_1']) : '';
    $settings['site_address_2'] = isset($_POST['site_address_2']) ? trim($_POST['site_address_2']) : '';
    $settings['site_phone'] = isset($_POST['site_phone']) ? trim($_POST['site_phone']) : '';
    $settings['site_email'] = isset($_POST['site_email']) ? trim($_POST['site_email']) : '';
    $settings['business_hours_weekday'] = isset($_POST['business_hours_weekday']) ? trim($_POST['business_hours_weekday']) : '';
    $settings['business_hours_weekend'] = isset($_POST['business_hours_weekend']) ? trim($_POST['business_hours_weekend']) : '';
    $settings['facebook_url'] = isset($_POST['facebook_url']) ? trim($_POST['facebook_url']) : '';
    $settings['instagram_url'] = isset($_POST['instagram_url']) ? trim($_POST['instagram_url']) : '';
    $settings['youtube_url'] = isset($_POST['youtube_url']) ? trim($_POST['youtube_url']) : '';
    $settings['tiktok_url'] = isset($_POST['tiktok_url']) ? trim($_POST['tiktok_url']) : '';
    $settings['zalo_url'] = isset($_POST['zalo_url']) ? trim($_POST['zalo_url']) : '';
    $settings['seo_keywords'] = isset($_POST['seo_keywords']) ? trim($_POST['seo_keywords']) : '';
    $settings['seo_description'] = isset($_POST['seo_description']) ? trim($_POST['seo_description']) : '';
    $settings['google_analytics_id'] = isset($_POST['google_analytics_id']) ? trim($_POST['google_analytics_id']) : '';

    if (empty($settings['site_name'])) {
        $errors[] = 'Tên website không được để trống.';
    }
    if (!empty($settings['site_email']) && !filter_var($settings['site_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (empty($errors)) {
        try {
            // Lưu cài đặt vào database hoặc file
            // Ví dụ: lưu vào bảng settings
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare('
                    INSERT INTO settings (setting_key, setting_value) 
                    VALUES (:key, :value) 
                    ON DUPLICATE KEY UPDATE setting_value = :value
                ');
                $stmt->execute([':key' => $key, ':value' => $value]);
            }
            $successMessage = 'Cập nhật cài đặt thành công.';
        } catch (Throwable $e) {
            $errors[] = 'Không thể lưu cài đặt. Vui lòng thử lại.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 30px; }
    .settings-page__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; }
    .settings-page__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 14px; flex-wrap: wrap; margin-bottom: 6px; }
    .breadcrumb a { color: var(--secondary); text-decoration: none; font-weight: 600; }
    .breadcrumb span { color: rgba(0,0,0,0.55); }
    .settings-section { background: var(--white); border-radius: 14px; border: 1px solid #f0ebe3; padding: 28px; margin-bottom: 24px; }
    .settings-section__title { font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
    .settings-section__title i { color: var(--secondary); }
    .settings-section__subtitle { font-size: 14px; color: rgba(0,0,0,0.6); margin-bottom: 24px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; font-size: 14px; color: var(--dark); margin-bottom: 8px; }
    .form-group label .required { color: #d64226; margin-left: 2px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; border-radius: 10px; border: 1px solid #e5e5e5; padding: 11px 14px; font-size: 14px; transition: border 0.2s ease, box-shadow 0.2s ease; background-color: var(--white); font-family: inherit; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.15); outline: none; }
    .form-group textarea { resize: vertical; min-height: 100px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: <?php echo BREAKPOINT_SM; ?>) { .form-row { grid-template-columns: 1fr; } }
    .form-actions { display: flex; justify-content: flex-start; gap: 12px; margin-top: 24px; }
    .btn-submit { padding: 12px 24px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; background: var(--secondary); color: var(--white); display: flex; align-items: center; gap: 8px; }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(210, 100, 38, 0.28); }
    .notice { display: flex; align-items: flex-start; gap: 12px; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
    .notice--success { background: rgba(63,142,63,0.12); color: #2a6a2a; border: 1px solid rgba(63,142,63,0.35); }
    .notice--error { background: rgba(210,64,38,0.12); color: #a52f1c; border: 1px solid rgba(210,64,38,0.35); }
    .notice i { margin-top: 2px; }
    .notice ul { margin: 8px 0 0; padding-left: 20px; }
    .social-input-group { display: flex; align-items: center; gap: 10px; }
    .social-input-group i { width: 20px; color: var(--secondary); }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content settings-page">
        <div class="settings-page__header">
            <div class="settings-page__title-group">
                <h1>Cài Đặt</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <span>Cài Đặt</span>
                </nav>
            </div>
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
                    <strong>Không thể cập nhật:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <!-- Thông Tin Liên Hệ -->
            <div class="settings-section">
                <h2 class="settings-section__title">
                    <i class="fas fa-info-circle"></i>
                    Thông Tin Liên Hệ
                </h2>
                <p class="settings-section__subtitle">Quản lý các thông tin cơ bản của website</p>

                <div class="form-row">
                    <div class="form-group">
                        <label for="site_address_1">Địa Chỉ 1</label>
                        <input type="text" id="site_address_1" name="site_address_1" value="<?php echo htmlspecialchars($settings['site_address_1']); ?>" placeholder="123 Đường Số 1, Phường 2">
                    </div>

                    <div class="form-group">
                        <label for="site_address_2">Địa Chỉ 2</label>
                        <input type="text" id="site_address_2" name="site_address_2" value="<?php echo htmlspecialchars($settings['site_address_2']); ?>" placeholder="Quận 3, TP. Hồ Chí Minh">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="site_phone">Số Điện Thoại</label>
                        <input type="text" id="site_phone" name="site_phone" value="<?php echo htmlspecialchars($settings['site_phone']); ?>" placeholder="0909 123 456">
                    </div>

                    <div class="form-group">
                        <label for="site_email">Email</label>
                        <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>" placeholder="info@ecoweb.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="business_hours_weekday">Giờ Làm Việc (Ngày Thường)</label>
                        <input type="text" id="business_hours_weekday" name="business_hours_weekday" value="<?php echo htmlspecialchars($settings['business_hours_weekday']); ?>" placeholder="8:00 - 17:00">
                    </div>

                    <div class="form-group">
                        <label for="business_hours_weekend">Giờ Làm Việc (Cuối Tuần)</label>
                        <input type="text" id="business_hours_weekend" name="business_hours_weekend" value="<?php echo htmlspecialchars($settings['business_hours_weekend']); ?>" placeholder="8:00 - 12:00">
                    </div>
                </div>
            </div>

            <!-- Mạng Xã Hội -->
            <div class="settings-section">
                <h2 class="settings-section__title">
                    <i class="fas fa-share-alt"></i>
                    Mạng Xã Hội
                </h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="facebook_url">
                            <i class="fab fa-facebook"></i> Facebook
                        </label>
                        <input type="url" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($settings['facebook_url']); ?>" placeholder="https://facebook.com/ecoweb">
                    </div>

                    <div class="form-group">
                        <label for="instagram_url">
                            <i class="fab fa-instagram"></i> Instagram
                        </label>
                        <input type="url" id="instagram_url" name="instagram_url" value="<?php echo htmlspecialchars($settings['instagram_url']); ?>" placeholder="https://instagram.com/ecoweb">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="youtube_url">
                            <i class="fab fa-youtube"></i> YouTube
                        </label>
                        <input type="url" id="youtube_url" name="youtube_url" value="<?php echo htmlspecialchars($settings['youtube_url']); ?>" placeholder="https://youtube.com/@ecoweb">
                    </div>

                    <div class="form-group">
                        <label for="tiktok_url">
                            <i class="fab fa-tiktok"></i> TikTok
                        </label>
                        <input type="url" id="tiktok_url" name="tiktok_url" value="<?php echo htmlspecialchars($settings['tiktok_url']); ?>" placeholder="https://tiktok.com/@ecoweb">
                    </div>
                </div>

                <div class="form-group">
                    <label for="zalo_url">Zalo</label>
                    <input type="url" id="zalo_url" name="zalo_url" value="<?php echo htmlspecialchars($settings['zalo_url']); ?>" placeholder="https://zalo.me/0909123456">
                </div>
            </div>

            <!-- Cài Đặt SEO -->
            <div class="settings-section">
                <h2 class="settings-section__title">
                    <i class="fas fa-search"></i>
                    Cài Đặt SEO
                </h2>

                <div class="form-group">
                    <label for="seo_keywords">Từ Khóa (Keywords)</label>
                    <input type="text" id="seo_keywords" name="seo_keywords" value="<?php echo htmlspecialchars($settings['seo_keywords']); ?>" placeholder="VD: cây xanh, trồng cây, phủ xanh đồi trọc">
                </div>

                <div class="form-group">
                    <label for="seo_description">Phần Giới Thiệu Ngắn</label>
                    <input type="text" id="seo_description" name="seo_description" value="<?php echo htmlspecialchars($settings['seo_description']); ?>" placeholder="Phần mô tả ngắn về website">
                </div>

                <div class="form-group">
                    <label for="seo_description_long">Mô Tả (Description)</label>
                    <textarea id="seo_description_long" name="seo_description_long" placeholder="Mô tả ngắn về website..."><?php echo htmlspecialchars($settings['seo_description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="google_analytics_id">Tên Tác Giả</label>
                    <input type="text" id="author_name" name="author_name" placeholder="Tên tác giả">
                </div>

                <div class="form-group">
                    <label for="google_analytics_id">Google Analytics ID</label>
                    <input type="text" id="google_analytics_id" name="google_analytics_id" value="<?php echo htmlspecialchars($settings['google_analytics_id']); ?>" placeholder="VD: UA-XXXXX-Y hoặc G-XXXXXXXXXX">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i>
                    Lưu Cài Đặt
                </button>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
