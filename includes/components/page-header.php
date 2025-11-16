<?php
/**
 * Page Header Component với Breadcrumb
 * 
 * Sử dụng:
 * $page_title = "Sản Phẩm";
 * $breadcrumbs = [
 *     ['text' => 'Trang Chủ', 'url' => BASE_URL . '/index.php'],
 *     ['text' => 'Sản Phẩm', 'url' => '']
 * ];
 * include __DIR__ . '/components/page-header.php';
 * 
 * Hoặc để tự động tạo breadcrumb từ URL:
 * $page_title = "Sản Phẩm";
 * include __DIR__ . '/components/page-header.php';
 */

// Đảm bảo config đã được load
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

// Lấy tiêu đề trang (mặc định nếu không được truyền)
if (!isset($page_title)) {
    $page_title = 'Trang';
}

// Tự động tạo breadcrumb nếu không được truyền
if (!isset($breadcrumbs)) {
    $breadcrumbs = [];
    
    // Xác định đường dẫn hiện tại
    $script_path = $_SERVER['PHP_SELF'];
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Xác định base path
    $is_public = (strpos($script_path, '/public/') !== false);
    $is_auth = (strpos($script_path, '/auth/') !== false);
    $is_views = (strpos($script_path, '/views/') !== false);
    $is_admin = (strpos($script_path, '/admin/') !== false);
    
    if ($is_public) {
        $base_path = '';
        $index_link = BASE_URL . '/index.php';
    } elseif ($is_auth || $is_views) {
        $base_path = BASE_URL . '/public/';
        $index_link = BASE_URL . '/index.php';
    } else {
        $base_path = BASE_URL . '/public/';
        $index_link = BASE_URL . '/index.php';
    }
    
    // Thêm Trang Chủ vào breadcrumb
    $breadcrumbs[] = ['text' => 'Trang Chủ', 'url' => $index_link];
    
    // Thêm các mục breadcrumb dựa trên trang hiện tại
    $page_map = [
        'products.php' => ['text' => 'Sản Phẩm', 'url' => $base_path . 'products.php'],
        'products-detail.php' => ['text' => 'Chi Tiết Sản Phẩm', 'url' => ''],
        'categories.php' => ['text' => 'Danh Mục', 'url' => $base_path . 'categories.php'],
        'news.php' => ['text' => 'Tin Tức', 'url' => $base_path . 'news.php'],
        'news-detail.php' => ['text' => 'Chi Tiết Tin Tức', 'url' => ''],
        'about.php' => ['text' => 'Giới Thiệu', 'url' => $base_path . 'about.php'],
        'contact.php' => ['text' => 'Liên Hệ', 'url' => $base_path . 'contact.php'],
        'search.php' => ['text' => 'Tìm Kiếm', 'url' => $base_path . 'search.php'],
        'galleries.php' => ['text' => 'Thư Viện', 'url' => $base_path . 'galleries.php'],
    ];
    
    if (isset($page_map[$current_page])) {
        $breadcrumbs[] = $page_map[$current_page];
    } else {
        // Nếu không có trong map, sử dụng tiêu đề trang
        $breadcrumbs[] = ['text' => $page_title, 'url' => ''];
    }
}
?>

<style>
    .page-header {
        background-color: var(--light);
        padding: 40px 0 30px;
        text-align: center;
    }

    .page-header-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 0 auto;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
    }

    .page-title {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 42px;
        color: var(--primary);
        margin-bottom: 15px;
        line-height: 1.2;
    }

    .page-breadcrumb {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
    }

    .page-breadcrumb a {
        color: var(--dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .page-breadcrumb a:hover {
        color: var(--primary);
    }

    .page-breadcrumb .separator {
        margin: 0 8px;
        color: #999;
    }

    .page-breadcrumb .current {
        color: #666;
    }

    .page-header-divider {
        width: 120px;
        height: 4px;
        background-color: var(--secondary);
        margin: 0 auto;
        border-radius: 2px;
    }

    /* Responsive */
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .page-title {
            font-size: 32px;
        }

        .page-breadcrumb {
            font-size: 13px;
        }

        .page-header {
            padding: 30px 0 25px;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .page-title {
            font-size: 28px;
        }

        .page-breadcrumb {
            font-size: 12px;
        }

        .page-header-divider {
            width: 100px;
            height: 3px;
        }
    }
</style>

<div class="page-header">
    <div class="page-header-container">
        <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
        
        <nav class="page-breadcrumb" aria-label="Breadcrumb">
            <?php
            $breadcrumb_count = count($breadcrumbs);
            foreach ($breadcrumbs as $index => $crumb) {
                $is_last = ($index === $breadcrumb_count - 1);
                
                if (!empty($crumb['url']) && !$is_last) {
                    echo '<a href="' . htmlspecialchars($crumb['url']) . '">' . htmlspecialchars($crumb['text']) . '</a>';
                } else {
                    echo '<span class="current">' . htmlspecialchars($crumb['text']) . '</span>';
                }
                
                if (!$is_last) {
                    echo '<span class="separator">/</span>';
                }
            }
            ?>
        </nav>
        
        <div class="page-header-divider"></div>
    </div>
</div>

