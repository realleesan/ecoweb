<?php 
require_once '../includes/config.php';
require_once '../includes/database.php';

$pageTitle = 'Thư viện ảnh - GROWHOPE';

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

// Pagination settings
$itemsPerPage = PAGINATION_GALLERY_PER_PAGE;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page); // Đảm bảo page không nhỏ hơn 1

$totalItems = 0;
$totalPages = 0;
$images = [];

if ($pdo) {
    try {
        $countStmt = $pdo->query('SELECT COUNT(*) FROM gallery_images');
        $totalItems = (int) $countStmt->fetchColumn();
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $itemsPerPage) : 0;

        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        if ($totalItems > 0) {
            $offset = ($page - 1) * $itemsPerPage;
            $stmt = $pdo->prepare('SELECT image_url, alt_text, category
                                   FROM gallery_images
                                   ORDER BY created_at DESC, image_id DESC
                                   LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $images = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $totalItems = 0;
        $totalPages = 0;
        $images = [];
    }
}

include '../includes/header.php';
?>

<!-- Gallery Section -->
<?php
$page_title = "Thư Viện Ảnh";
include __DIR__ . '/../includes/components/page-header.php';
?>

<main class="gallery-container" style="padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; padding-top: 20px; max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 0 auto;">
    <div class="gallery-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: <?php echo GRID_GAP_SMALL; ?>;">
        <?php if (empty($images)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <i class="fas fa-image" style="font-size: 48px; color: var(--secondary); margin-bottom: 20px;"></i>
                <p>Thư viện đang được cập nhật.</p>
            </div>
        <?php else: ?>
            <?php foreach ($images as $image): ?>
                <div class="gallery-item" style="break-inside: avoid; margin-bottom: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['alt_text']); ?>" style="width: 100%; display: block;" class="gallery-image">
                    <div style="padding: 12px; background: white;">
                        <p style="margin: 0; color: var(--dark); font-weight: 500;"><?php echo htmlspecialchars($image['alt_text']); ?></p>
                        <?php if (!empty($image['category'])): ?>
                            <span style="font-size: 12px; color: var(--secondary);"><?php echo htmlspecialchars($image['category']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1 && !empty($images)): ?>
        <?php
        $current_page = $page;
        $total_pages = $totalPages;
        $base_url = 'galleries.php?';
        include __DIR__ . '/../includes/components/pagination.php';
        ?>
    <?php endif; ?>
</main>

<!-- Add some responsive styles -->
<style>
    body {
        background-color: var(--light);
    }

    @media (max-width: <?php echo BREAKPOINT_XL; ?>) {
        .gallery-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
    }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .gallery-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .gallery-grid {
            grid-template-columns: 1fr !important;
        }
    }
    .gallery-item:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
        transition: box-shadow 0.3s ease;
    }
    .gallery-item img {
        transition: transform 0.3s ease;
    }
    .gallery-item:hover img {
        transform: scale(1.03);
    }
</style>

<?php
$cta_heading = 'Tìm hiểu thêm về các dự án và hoạt động của chúng tôi';
$cta_description = 'Sau khi xem hình ảnh, hãy tìm hiểu thêm về các dự án trồng rừng và cách bạn có thể tham gia.';
$cta_button_text = 'Tìm hiểu thêm';
$cta_button_link = BASE_URL . '/public/about.php';
include '../includes/components/cta-section.php';
?>
<?php include '../includes/footer.php'; ?>