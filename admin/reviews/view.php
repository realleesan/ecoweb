<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$reviewId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($reviewId <= 0) {
    header('Location: ' . BASE_URL . '/admin/reviews/index.php');
    exit;
}

$errors = [];
$successMessage = '';
$review = null;

$allowedStatuses = [
    'Pending' => 'Chờ duyệt',
    'Approved' => 'Đã duyệt',
    'Rejected' => 'Từ chối',
];

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare(
        'SELECT r.review_id, r.product_id, r.user_id, r.user_name, r.user_email, r.rating, r.title, r.content, r.status, r.created_at,
                p.name AS product_name, p.code AS product_code
         FROM product_reviews r
         LEFT JOIN products p ON p.product_id = r.product_id
         WHERE r.review_id = :id'
    );
    $stmt->execute([':id' => $reviewId]);
    $review = $stmt->fetch();

    if (!$review) {
        $errors[] = 'Không tìm thấy đánh giá.';
    }

    if ($review && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

        if ($action === 'update_status') {
            $newStatus = isset($_POST['status']) ? (string) $_POST['status'] : '';

            if (!array_key_exists($newStatus, $allowedStatuses)) {
                $errors[] = 'Trạng thái không hợp lệ.';
            } elseif ($newStatus === (string) $review['status']) {
                $successMessage = 'Trạng thái đánh giá không thay đổi.';
            } else {
                $updateStmt = $pdo->prepare('UPDATE product_reviews SET status = :status WHERE review_id = :id');
                $updateStmt->execute([
                    ':status' => $newStatus,
                    ':id' => $reviewId,
                ]);
                $successMessage = 'Đã cập nhật trạng thái đánh giá.';

                $stmt->execute([':id' => $reviewId]);
                $review = $stmt->fetch();
            }
        }
    }
} catch (Throwable $exception) {
    $errors[] = 'Không thể tải thông tin đánh giá. Vui lòng thử lại sau.';
    $review = null;
}

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

    .admin-content {
        background-color: var(--white);
        border-radius: 20px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.06);
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        display: flex;
        flex-direction: column;
        gap: 28px;
    }

    .review-view__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
    }

    .review-view__title h1 {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 6px;
    }

    .breadcrumb {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 14px;
        flex-wrap: wrap;
        color: rgba(0, 0, 0, 0.6);
    }

    .breadcrumb a {
        color: var(--secondary);
        text-decoration: none;
        font-weight: 600;
    }

    .back-link {
        text-decoration: none;
        font-weight: 600;
        color: var(--secondary);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .notice {
        border-radius: 12px;
        padding: 16px 20px;
        font-size: 14px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .notice--error {
        background: rgba(210, 64, 38, 0.12);
        color: #a52f1c;
        border: 1px solid rgba(210, 64, 38, 0.35);
    }

    .notice--success {
        background: rgba(63, 142, 63, 0.12);
        color: #2a6a2a;
        border: 1px solid rgba(63, 142, 63, 0.32);
    }

    .review-meta-card {
        background: rgba(255, 247, 237, 0.95);
        border-radius: 18px;
        padding: 22px;
        border: 1px solid rgba(210, 100, 38, 0.18);
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
    }

    .review-meta-card__item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .review-meta-card__label {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.55);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .review-meta-card__value {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark);
    }

    .review-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        border-radius: 999px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .review-status-badge--pending {
        background: rgba(250, 177, 60, 0.18);
        color: #a96807;
    }

    .review-status-badge--approved {
        background: rgba(63, 142, 63, 0.18);
        color: #1f6a1f;
    }

    .review-status-badge--rejected {
        background: rgba(210, 64, 38, 0.18);
        color: #9c2a18;
    }

    .review-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .review-actions form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .review-actions select {
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        padding: 10px 14px;
        font-size: 14px;
        min-width: 180px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--secondary), #f7c76a);
        color: var(--white);
        border: none;
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(210, 100, 38, 0.28);
    }

    .review-content {
        background: rgba(255, 255, 255, 0.92);
        border-radius: 18px;
        border: 1px solid #f0ebe3;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        line-height: 1.7;
        color: rgba(0, 0, 0, 0.75);
    }

    .review-content__title {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
    }

    .review-rating-display {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: var(--secondary);
    }

    .review-rating-display i {
        color: var(--secondary);
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .admin-content {
            padding: <?php echo CONTAINER_PADDING; ?>;
        }

        .review-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .review-actions form {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }

        .review-actions select,
        .btn-primary {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content review-view">
        <header class="review-view__header">
            <div class="review-view__title">
                <h1>Chi Tiết Đánh Giá</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <a href="<?php echo BASE_URL; ?>/admin/reviews/index.php">Quản Lý Đánh Giá</a>
                    <span>/</span>
                    <span>Chi tiết</span>
                </nav>
                <?php if ($review):
                    $status = (string) ($review['status'] ?? 'Pending');
                    $statusClass = 'review-status-badge--pending';
                    if ($status === 'Approved') {
                        $statusClass = 'review-status-badge--approved';
                    } elseif ($status === 'Rejected') {
                        $statusClass = 'review-status-badge--rejected';
                    }
                ?>
                    <span class="review-status-badge <?php echo $statusClass; ?>">
                        <i class="fas fa-star"></i> <?php echo $allowedStatuses[$status] ?? $status; ?>
                    </span>
                <?php endif; ?>
            </div>
            <a class="back-link" href="<?php echo BASE_URL; ?>/admin/reviews/index.php"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
        </header>

        <?php if ($errors): ?>
            <div class="notice notice--error" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Không thể hiển thị đánh giá:</strong>
                    <ul style="margin:8px 0 0;padding-left:20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php elseif ($successMessage): ?>
            <div class="notice notice--success" role="status">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($review): ?>
            <section class="review-meta-card" role="presentation">
                <div class="review-meta-card__item">
                    <span class="review-meta-card__label">Khách hàng</span>
                    <span class="review-meta-card__value"><?php echo htmlspecialchars($review['user_name'] ?? 'Ẩn danh'); ?></span>
                    <?php if (!empty($review['user_email'])): ?>
                        <span style="font-size:13px;color:rgba(0,0,0,0.6);">Email: <?php echo htmlspecialchars($review['user_email']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="review-meta-card__item">
                    <span class="review-meta-card__label">Sản phẩm</span>
                    <span class="review-meta-card__value"><?php echo htmlspecialchars($review['product_name'] ?? 'Sản phẩm đã xóa'); ?></span>
                    <a href="<?php echo BASE_URL; ?>/views/products-detail.php?id=<?php echo (int) ($review['product_id'] ?? 0); ?>" target="_blank" rel="noopener" style="font-size:13px;color:var(--secondary);text-decoration:none;">Xem sản phẩm</a>
                </div>
                <div class="review-meta-card__item">
                    <span class="review-meta-card__label">Mã sản phẩm</span>
                    <span class="review-meta-card__value"><?php echo htmlspecialchars($review['product_code'] ?? 'N/A'); ?></span>
                </div>
                <div class="review-meta-card__item">
                    <span class="review-meta-card__label">Ngày gửi</span>
                    <span class="review-meta-card__value"><?php echo $review['created_at'] ? date('d/m/Y H:i', strtotime((string) $review['created_at'])) : 'Không rõ'; ?></span>
                </div>
                <div class="review-meta-card__item">
                    <span class="review-meta-card__label">Điểm số</span>
                    <span class="review-meta-card__value review-rating-display">
                        <?php
                        $rating = (int) ($review['rating'] ?? 0);
                        for ($star = 1; $star <= 5; $star++):
                            $filled = $star <= $rating;
                        ?>
                            <i class="fas fa-star" style="color: <?php echo $filled ? 'var(--secondary)' : '#ccc'; ?>;"></i>
                        <?php endfor; ?>
                        <span><?php echo $rating; ?>/5</span>
                    </span>
                </div>
            </section>

            <section class="review-actions" role="group" aria-label="Hành động">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    <input type="hidden" name="action" value="update_status">
                    <label class="sr-only" for="status">Cập nhật trạng thái</label>
                    <select id="status" name="status">
                        <?php foreach ($allowedStatuses as $statusKey => $statusLabel): ?>
                            <option value="<?php echo htmlspecialchars($statusKey); ?>" <?php echo ((string) $review['status'] === $statusKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Lưu trạng thái</button>
                </form>
            </section>

            <article class="review-content" aria-labelledby="review-title">
                <header>
                    <h2 class="review-content__title" id="review-title"><?php echo htmlspecialchars($review['title'] ?? '(Không có tiêu đề)'); ?></h2>
                </header>
                <div>
                    <?php echo nl2br(htmlspecialchars((string) ($review['content'] ?? ''))); ?>
                </div>
            </article>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
