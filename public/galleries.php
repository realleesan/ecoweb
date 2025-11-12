<?php 
$pageTitle = 'Thư viện ảnh - GROWHOPE';
include '../includes/header.php'; 

// Pagination settings
$itemsPerPage = 16; // 4 rows x 4 images = 16 images per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Đảm bảo page không nhỏ hơn 1

// Sample image data - in a real application, this would come from a database
$allImages = [];
for ($i = 1; $i <= 20; $i++) {
    $allImages[] = [
        'src' => 'https://source.unsplash.com/random/400x' . rand(500, 800) . '?nature,plant,tree&' . $i,
        'alt' => 'Hình ảnh ' . $i,
        'category' => ['Cây trồng', 'Vườn ươm', 'Thiên nhiên'][array_rand([0, 1, 2])]
    ];
}

// Calculate total pages
$totalItems = count($allImages);
$totalPages = ceil($totalItems / $itemsPerPage);

// Get current page items
$start = ($page - 1) * $itemsPerPage;
$images = array_slice($allImages, $start, $itemsPerPage);
?>

<!-- Gallery Section -->
<main class="gallery-container" style="padding: 40px 5%;">
    <h1 style="text-align: center; margin-bottom: 30px; color: var(--dark);">Thư viện ảnh</h1>
    
    <div class="gallery-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
        <?php

        foreach ($images as $image) {
            echo '<div class="gallery-item" style="break-inside: avoid; margin-bottom: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">';
            echo '    <img src="' . $image['src'] . '" alt="' . $image['alt'] . '" style="width: 100%; display: block;" class="gallery-image">';
            echo '    <div style="padding: 12px; background: white;">';
            echo '        <p style="margin: 0; color: var(--dark); font-weight: 500;">' . $image['alt'] . '</p>';
            echo '        <span style="font-size: 12px; color: var(--secondary);">' . $image['category'] . '</span>';
            echo '    </div>';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Pagination -->
    <div class="pagination" style="margin-top: 40px; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo ($page - 1); ?>" class="page-link" 
               style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #f0f0f0; color: var(--dark); text-decoration: none; border-radius: 4px; font-weight: 500;"
               onmouseover="this.style.background='var(--secondary)'; this.style.color='white'" 
               onmouseout="this.style.background='#f0f0f0'; this.style.color='var(--dark)'">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php
        // Show page numbers
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++):
            $isActive = ($i == $page);
        ?>
            <a href="?page=<?php echo $i; ?>" 
               style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: <?php echo $isActive ? 'var(--primary)' : '#f0f0f0'; ?>; color: <?php echo $isActive ? 'white' : 'var(--dark)'; ?>; text-decoration: none; border-radius: 4px; font-weight: 500;"
               onmouseover="if(!<?php echo $isActive ? 'true' : 'false'; ?>) {this.style.background='var(--secondary)'; this.style.color='white'}" 
               onmouseout="if(!<?php echo $isActive ? 'true' : 'false'; ?>) {this.style.background='#f0f0f0'; this.style.color='var(--dark)'}">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo ($page + 1); ?>" class="page-link" 
               style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #f0f0f0; color: var(--dark); text-decoration: none; border-radius: 4px; font-weight: 500;"
               onmouseover="this.style.background='var(--secondary)'; this.style.color='white'" 
               onmouseout="this.style.background='#f0f0f0'; this.style.color='var(--dark)'">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
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