<?php
/**
 * Pagination Component
 * 
 * Hiển thị phân trang theo thiết kế chuẩn
 * 
 * @param int $current_page Trang hiện tại (bắt đầu từ 1)
 * @param int $total_pages Tổng số trang
 * @param string $base_url URL cơ sở (có thể chứa query params khác)
 * @param string $page_param Tên tham số trang (mặc định: 'page')
 * @param int $max_visible Số trang tối đa hiển thị xung quanh trang hiện tại (mặc định: 2)
 */

// Đảm bảo các tham số được truyền vào
if (!isset($current_page)) {
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
}

if (!isset($total_pages)) {
    $total_pages = 1;
}

if (!isset($base_url)) {
    // Lấy URL hiện tại và loại bỏ tham số page
    $base_url = strtok($_SERVER["REQUEST_URI"], '?');
    $query_params = $_GET;
    unset($query_params['page']);
    if (!empty($query_params)) {
        $base_url .= '?' . http_build_query($query_params);
        $base_url .= '&';
    } else {
        $base_url .= '?';
    }
}

if (!isset($page_param)) {
    $page_param = 'page';
}

if (!isset($max_visible)) {
    $max_visible = 2;
}

// Không hiển thị phân trang nếu chỉ có 1 trang hoặc không có trang nào
if ($total_pages <= 1) {
    return;
}

// Đảm bảo current_page hợp lệ
$current_page = max(1, min($current_page, $total_pages));

// Tính toán phạm vi trang hiển thị
$start_page = max(1, $current_page - $max_visible);
$end_page = min($total_pages, $current_page + $max_visible);

// Điều chỉnh để luôn hiển thị đủ số trang
if ($end_page - $start_page < ($max_visible * 2)) {
    if ($start_page == 1) {
        $end_page = min($total_pages, $start_page + ($max_visible * 2));
    } else {
        $start_page = max(1, $end_page - ($max_visible * 2));
    }
}
?>

<style>
    .pagination-component {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin: 40px 0;
        flex-wrap: wrap;
    }

    .pagination-component .page-link,
    .pagination-component .page-item {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        border-radius: 4px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
        background: none;
    }

    .pagination-component .page-link {
        background-color: #e8e8e8;
        color: var(--dark);
    }

    .pagination-component .page-link:hover {
        background-color: #d8d8d8;
    }

    .pagination-component .page-item.active .page-link {
        background-color: var(--primary);
        color: var(--white);
    }

    .pagination-component .page-item.disabled .page-link {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    .pagination-component .page-link.next,
    .pagination-component .page-link.prev {
        background-color: #e8e8e8;
        color: var(--dark);
    }

    .pagination-component .page-link.next:hover,
    .pagination-component .page-link.prev:hover {
        background-color: #d8d8d8;
    }

    @media (max-width: <?php echo BREAKPOINT_XS; ?>) {
        .pagination-component {
            gap: 4px;
        }

        .pagination-component .page-link,
        .pagination-component .page-item {
            min-width: 36px;
            height: 36px;
            font-size: 13px;
        }
    }
</style>

<nav class="pagination-component" aria-label="Phân trang">
    <?php
    // Nút Previous (Trước)
    if ($current_page > 1):
        $prev_url = $base_url . $page_param . '=' . ($current_page - 1);
    ?>
        <a href="<?php echo htmlspecialchars($prev_url); ?>" class="page-link prev" aria-label="Trang trước">
            <i class="fas fa-chevron-left"></i>
        </a>
    <?php else: ?>
        <span class="page-item disabled">
            <span class="page-link prev" aria-label="Trang trước">
                <i class="fas fa-chevron-left"></i>
            </span>
        </span>
    <?php endif; ?>

    <?php
    // Hiển thị trang đầu nếu cần
    if ($start_page > 1):
        $first_url = $base_url . $page_param . '=1';
    ?>
        <a href="<?php echo htmlspecialchars($first_url); ?>" class="page-link">1</a>
        <?php if ($start_page > 2): ?>
            <span class="page-item disabled">
                <span class="page-link">...</span>
            </span>
        <?php endif; ?>
    <?php endif; ?>

    <?php
    // Hiển thị các trang trong phạm vi
    for ($i = $start_page; $i <= $end_page; $i++):
        if ($i == $current_page):
    ?>
            <span class="page-item active">
                <span class="page-link"><?php echo $i; ?></span>
            </span>
        <?php else: ?>
            <?php
            $page_url = $base_url . $page_param . '=' . $i;
            ?>
            <a href="<?php echo htmlspecialchars($page_url); ?>" class="page-link"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php
    // Hiển thị trang cuối nếu cần
    if ($end_page < $total_pages):
        if ($end_page < $total_pages - 1):
    ?>
            <span class="page-item disabled">
                <span class="page-link">...</span>
            </span>
        <?php endif; ?>
        <?php
        $last_url = $base_url . $page_param . '=' . $total_pages;
        ?>
        <a href="<?php echo htmlspecialchars($last_url); ?>" class="page-link"><?php echo $total_pages; ?></a>
    <?php endif; ?>

    <?php
    // Nút Next (Sau)
    if ($current_page < $total_pages):
        $next_url = $base_url . $page_param . '=' . ($current_page + 1);
    ?>
        <a href="<?php echo htmlspecialchars($next_url); ?>" class="page-link next" aria-label="Trang sau">
            <i class="fas fa-chevron-right"></i>
        </a>
    <?php else: ?>
        <span class="page-item disabled">
            <span class="page-link next" aria-label="Trang sau">
                <i class="fas fa-chevron-right"></i>
            </span>
        </span>
    <?php endif; ?>
</nav>

