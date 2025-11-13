<?php 
require_once '../includes/database.php';

$pageTitle = 'Thư viện ảnh - GROWHOPE';

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

// Pagination settings
$itemsPerPage = 16; // 4 rows x 4 images = 16 images per page
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
<main class="gallery-container" style="padding: 40px 5%;">
    <h1 style="text-align: center; margin-bottom: 30px; color: var(--dark);">Thư viện ảnh</h1>
    
    <div class="gallery-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
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
    <div class="pagination" style="margin-top: 40px; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="page-link" 
               style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #f0f0f0; color: var(--dark); text-decoration: none; border-radius: 4px; font-weight: 500;"
               onmouseover="this.style.background='var(--secondary)'; this.style.color='white'" 
               onmouseout="this.style.background='#f0f0f0'; this.style.color='var(--dark)'">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);

        if ($startPage > 1) {
            echo '<a href="?page=1" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #f0f0f0; color: var(--dark); text-decoration: none; border-radius: 4px; font-weight: 500;">1</a>';
            if ($startPage > 2) {
                echo '<span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px;">...</span>';
            }
        }

        for ($i = $startPage; $i <= $endPage; $i++) {
            $isActive = ($i === $page);
            echo '<a href="?page=' . $i . '" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: ' . ($isActive ? 'var(--primary)' : '#f0f0f0') . '; color: ' . ($isActive ? 'white' : 'var(--dark)') . '; text-decoration: none; border-radius: 4px; font-weight: 500;">' . $i . '</a>';
        }

        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                echo '<span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px;">...</span>';
            }
            echo '<a href="?page=' . $totalPages . '" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #f0f0f0; color: var(--dark); text-decoration: none; border-radius: 4px; font-weight: 500;">' . $totalPages . '</a>';
        }

        if ($page < $totalPages) {
            echo '<a href="?page=' . ($page + 1) . '" class="page-link" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #f0f0f0; color: var(--dark); text-decoration: none; border-radius: 4px; font-weight: 500;"><i class="fas fa-chevron-right"></i></a>';
        }
        ?>
    </div>
    <?php endif; ?>
</main>

<!-- Add some responsive styles -->
<style>
    @media (max-width: 1200px) {
        .gallery-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
    }
    @media (max-width: 900px) {
        .gallery-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    @media (max-width: 600px) {
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

<?php include '../includes/footer.php'; ?>