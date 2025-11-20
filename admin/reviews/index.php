<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$keyword = isset($_GET['keyword']) ? trim((string) $_GET['keyword']) : '';
$productFilter = isset($_GET['product']) ? (int) $_GET['product'] : 0;
$ratingFilter = isset($_GET['rating']) ? trim((string) $_GET['rating']) : '';
$statusFilter = isset($_GET['status']) ? trim((string) $_GET['status']) : '';

$errors = [];
$reviews = [];
$products = [];
$totalReviews = 0;
$pendingReviewsCount = 0;
$latestPendingAt = '';

try {
    $pdo = getPDO();

    $productStmt = $pdo->query('SELECT product_id, name AS product_name FROM products ORDER BY name ASC');
    $products = $productStmt->fetchAll();

    try {
        $pendingStatsStmt = $pdo->query('SELECT COUNT(*) AS cnt, MAX(created_at) AS latest_created FROM product_reviews WHERE status = "Pending"');
        if ($pendingStatsStmt) {
            $pendingStats = $pendingStatsStmt->fetch();
            $pendingReviewsCount = (int) ($pendingStats['cnt'] ?? 0);
            $latestPendingAt = $pendingStats['latest_created'] ?? '';
        }
    } catch (Throwable $statsException) {
        $pendingReviewsCount = 0;
        $latestPendingAt = '';
    }

    $sql = 'SELECT r.review_id, r.user_name, r.user_email, r.title, r.content, r.rating, r.status, r.created_at, p.product_id, p.name AS product_name, p.code
            FROM product_reviews r
            LEFT JOIN products p ON p.product_id = r.product_id';
    $conditions = [];
    $params = [];

    if ($keyword !== '') {
        $conditions[] = '(r.title LIKE :keyword OR r.content LIKE :keyword OR r.user_name LIKE :keyword OR r.user_email LIKE :keyword)';
        $params[':keyword'] = '%' . $keyword . '%';
    }

    if ($productFilter > 0) {
        $conditions[] = 'r.product_id = :product_id';
        $params[':product_id'] = $productFilter;
    }

    if ($ratingFilter !== '' && in_array($ratingFilter, ['1', '2', '3', '4', '5'], true)) {
        $conditions[] = 'r.rating = :rating';
        $params[':rating'] = (int) $ratingFilter;
    }

    if ($statusFilter !== '' && in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true)) {
        $conditions[] = 'r.status = :status';
        $params[':status'] = $statusFilter;
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY r.created_at DESC';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $reviews = $stmt->fetchAll();
    $totalReviews = count($reviews);
} catch (Throwable $exception) {
    $errors[] = 'Không thể tải danh sách đánh giá. Vui lòng thử lại sau.';
    $reviews = [];
    $products = [];
    $totalReviews = 0;
    $pendingReviewsCount = 0;
    $latestPendingAt = '';
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
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .notice {
        border-radius: 12px;
        padding: 16px 20px;
        font-size: 14px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .notice--info {
        background: rgba(63, 142, 63, 0.12);
        border: 1px solid rgba(63, 142, 63, 0.28);
        color: #2a6a2a;
    }

    .notice__body {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .notice__actions {
        display: inline-flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .notice__link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--secondary), #f7c76a);
        color: var(--white);
        font-weight: 600;
        text-decoration: none;
        font-size: 13px;
    }

    .notice__link:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(210, 100, 38, 0.25);
    }

    .reviews-page__header {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .reviews-page__title {
        font-size: 30px;
        font-weight: 700;
        color: var(--primary);
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

    .reviews-page__meta {
        color: rgba(0, 0, 0, 0.55);
        font-size: 14px;
    }

    .reviews-filter {
        background: rgba(255, 247, 237, 0.9);
        border: 1px solid rgba(210, 100, 38, 0.15);
        border-radius: 16px;
        padding: 14px 16px;
        display: grid;
        grid-template-columns: minmax(0, 2.8fr) minmax(0, 1fr) minmax(0, 0.9fr) minmax(0, 0.9fr) minmax(0, 0.6fr);
        column-gap: 12px;
        row-gap: 10px;
        align-items: end;
        overflow: hidden;
    }

    .reviews-filter__field label {
        display: block;
        margin-bottom: 4px;
        font-weight: 600;
        font-size: 12px;
        color: var(--dark);
    }

    .reviews-filter__field input,
    .reviews-filter__field select {
        width: 100%;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        padding: 8px 12px;
        font-size: 13px;
        transition: border 0.2s ease, box-shadow 0.2s ease;
        background: var(--white);
    }

    .reviews-filter__field input:focus,
    .reviews-filter__field select:focus {
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.16);
        outline: none;
    }

    .reviews-filter__actions {
        display: flex;
        justify-content: stretch;
    }

    .btn-filter-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 12px;
        border: none;
        padding: 10px 14px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        text-decoration: none;
        width: auto;
        min-width: 44px;
        min-height: 44px;
    }

    .btn-filter-submit {
        background: linear-gradient(135deg, var(--secondary), #f7c76a);
        color: var(--white);
        box-shadow: 0 10px 22px rgba(210, 100, 38, 0.25);
    }

    .btn-filter-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 15px 26px rgba(210, 100, 38, 0.32);
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .reviews-filter {
            grid-template-columns: 1fr;
        }

        .reviews-filter__actions {
            justify-content: stretch;
        }

        .btn-filter-submit {
            width: 100%;
        }
    }

    .reviews-table-wrapper {
        border-radius: 20px;
        border: 1px solid #f0ebe3;
        overflow: hidden;
        background: var(--white);
        display: flex;
        flex-direction: column;
    }

    .reviews-table-scroll {
        overflow-x: auto;
    }

    .reviews-table {
        display: flex;
        flex-direction: column;
        min-width: 100%;
    }

    .reviews-row {
        display: grid;
        grid-template-columns: 48px minmax(0, 1.8fr) minmax(0, 1.4fr) minmax(0, 0.8fr) minmax(0, 0.9fr) 64px;
        padding: 14px 20px;
        gap: 14px;
        align-items: center;
        border-bottom: 1px solid #f5ede5;
    }

    .reviews-row--head {
        background: rgba(210, 100, 38, 0.04);
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(0, 0, 0, 0.55);
    }

    .reviews-row:last-child {
        border-bottom: none;
    }

    .reviews-row--head > div {
        display: flex;
        align-items: center;
    }

    .review-index {
        font-weight: 600;
        font-size: 14px;
        color: var(--secondary);
    }

    .review-main__title {
        font-size: 15px;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 6px;
    }

    .review-main__meta {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.55);
        margin-bottom: 8px;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .review-product__name {
        font-weight: 600;
        color: var(--secondary);
        display: inline-flex;
        margin-bottom: 6px;
    }

    .review-rating span {
        font-weight: 600;
        margin-left: 6px;
    }

    .review-status {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .review-status--pending {
        background: rgba(250, 177, 60, 0.18);
        color: #a96807;
    }

    .review-status--approved {
        background: rgba(63, 142, 63, 0.18);
        color: #1f6a1f;
    }

    .review-status--rejected {
        background: rgba(210, 64, 38, 0.18);
        color: #9c2a18;
    }

    .review-actions {
        display: flex;
        justify-content: flex-end;
    }

    .review-action {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(210, 100, 38, 0.12);
        color: var(--secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: transform 0.2s ease, background 0.2s ease;
    }

    .review-action:hover {
        transform: translateY(-2px);
        background: rgba(210, 100, 38, 0.22);
    }

    .reviews-empty {
        padding: 36px 20px;
        text-align: center;
        color: rgba(0, 0, 0, 0.55);
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: center;
        justify-content: center;
    }

    .reviews-empty i {
        font-size: 44px;
        color: rgba(210, 100, 38, 0.3);
    }

    @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
        .reviews-row {
            grid-template-columns: 40px minmax(0, 1.6fr) minmax(0, 1.3fr) minmax(0, 0.8fr) minmax(0, 0.9fr) 56px;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .reviews-row {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .reviews-row--head {
            display: none;
        }

        .review-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .reviews-filter {
            grid-template-columns: 1fr;
        }

        .reviews-filter__actions {
            justify-content: stretch;
        }

        .btn-filter-submit,
        .btn-filter-reset {
            width: 100%;
            justify-content: center;
        }

        .reviews-row {
            grid-template-columns: 1fr;
            grid-template-areas:
                'main'
                'product'
                'rating'
                'status'
                'actions';
        }

        .reviews-row .review-main { grid-area: main; }
        .reviews-row .review-product { grid-area: product; }
        .reviews-row .review-rating { grid-area: rating; }
        .reviews-row .review-status { grid-area: status; }
        .reviews-row .review-actions { grid-area: actions; justify-content: flex-start; }

        .reviews-row--head {
            display: none;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .admin-content {
            padding: <?php echo CONTAINER_PADDING; ?>;
        }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content reviews-page">
        <header class="reviews-page__header">
            <div>
                <h1 class="reviews-page__title">Quản Lý Đánh Giá</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <span>Quản Lý Đánh Giá</span>
                </nav>
            </div>
            <p class="reviews-page__meta">Tổng cộng: <?php echo $totalReviews; ?> đánh giá</p>
        </header>

        <?php if ($errors): ?>
            <div class="notice notice--error" role="alert" style="display:flex;align-items:flex-start;gap:12px;border-radius:12px;padding:16px 20px;background:rgba(210,64,38,0.12);color:#a52f1c;border:1px solid rgba(210,64,38,0.35);">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Không thể thực hiện thao tác:</strong>
                    <ul style="margin:8px 0 0;padding-left:20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form class="reviews-filter" method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="reviews-filter__field">
                <label for="keyword">Tìm Kiếm</label>
                <input type="text" id="keyword" name="keyword" placeholder="Tiêu đề, nội dung, sản phẩm..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="reviews-filter__field">
                <label for="product">Sản Phẩm</label>
                <select id="product" name="product">
                    <option value="0" <?php echo $productFilter === 0 ? 'selected' : ''; ?>>Tất cả sản phẩm</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo (int) $product['product_id']; ?>" <?php echo $productFilter === (int) $product['product_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($product['product_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="reviews-filter__field">
                <label for="rating">Đánh Giá</label>
                <select id="rating" name="rating">
                    <option value="" <?php echo $ratingFilter === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo $ratingFilter === (string) $i ? 'selected' : ''; ?>><?php echo $i; ?> sao</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="reviews-filter__field">
                <label for="status">Trạng Thái</label>
                <select id="status" name="status">
                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                    <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                    <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Từ chối</option>
                </select>
            </div>
            <div class="reviews-filter__actions">
                <button type="submit" class="btn-filter-submit" aria-label="Lọc"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <div class="reviews-table-wrapper">
            <div class="reviews-table-scroll">
            <?php if ($totalReviews > 0): ?>
                <div class="reviews-table" role="table" aria-label="Danh sách đánh giá">
                    <div class="reviews-row reviews-row--head" role="row">
                        <div role="columnheader">#</div>
                        <div role="columnheader">Đánh giá</div>
                        <div role="columnheader">Sản phẩm</div>
                        <div role="columnheader">Điểm</div>
                        <div role="columnheader">Trạng thái</div>
                        <div role="columnheader">Thao tác</div>
                    </div>
                    <?php foreach ($reviews as $index => $review):
                        $rating = (int) ($review['rating'] ?? 0);
                        $status = (string) ($review['status'] ?? '');
                        $statusClass = 'review-status--pending';
                        $statusLabel = 'Chờ duyệt';

                        if ($status === 'Approved') {
                            $statusClass = 'review-status--approved';
                            $statusLabel = 'Đã duyệt';
                        } elseif ($status === 'Rejected') {
                            $statusClass = 'review-status--rejected';
                            $statusLabel = 'Từ chối';
                        }

                        $productName = trim((string) ($review['product_name'] ?? 'Sản phẩm đã xóa'));
                        $rowNumber = $index + 1;
                    ?>
                        <div class="reviews-row" role="row">
                            <div class="review-index" role="cell"><?php echo $rowNumber; ?></div>
                            <div class="review-main" role="cell">
                                <div class="review-main__title"><?php echo htmlspecialchars($review['title'] ?? '(Không có tiêu đề)'); ?></div>
                                <div class="review-main__meta">
                                    <?php echo htmlspecialchars($review['user_name'] ?? 'Ẩn danh'); ?> · <?php echo htmlspecialchars($review['user_email'] ?? ''); ?>
                                </div>
                            </div>
                            <div class="review-product" role="cell">
                                <span class="review-product__name"><?php echo htmlspecialchars($productName); ?></span>
                            </div>
                            <div class="review-rating" role="cell" aria-label="<?php echo $rating; ?> trên 5 sao">
                                <?php for ($star = 1; $star <= 5; $star++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $star <= $rating ? 'var(--secondary)' : '#ccc'; ?>;"></i>
                                <?php endfor; ?>
                                <span><?php echo $rating; ?>/5</span>
                            </div>
                            <div role="cell">
                                <span class="review-status <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                            </div>
                            <div class="review-actions" role="cell">
                                <a class="review-action" href="<?php echo BASE_URL; ?>/admin/reviews/view.php?id=<?php echo (int) $review['review_id']; ?>" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="reviews-empty" role="status">
                    <i class="fas fa-star"></i>
                    <p><strong>Không tìm thấy đánh giá nào</strong></p>
                    <p>Hãy thử thay đổi bộ lọc.</p>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
