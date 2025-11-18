<?php
require_once __DIR__ . '/../../includes/config.php';
$current_page = basename($_SERVER['PHP_SELF']);
$menu = [
    ['key' => 'index.php', 'label' => 'Tổng quát', 'icon' => 'fas fa-chart-pie', 'href' => BASE_URL . '/admin/index.php'],
    ['key' => 'categories.php', 'label' => 'Danh mục', 'icon' => 'fas fa-list', 'href' => BASE_URL . '/admin/categories.php'],
    ['key' => 'orders.php', 'label' => 'Đơn hàng', 'icon' => 'fas fa-shopping-bag', 'href' => BASE_URL . '/admin/orders.php'],
    ['key' => 'products.php', 'label' => 'Sản phẩm', 'icon' => 'fas fa-leaf', 'href' => BASE_URL . '/admin/products.php'],
    ['key' => 'promotions.php', 'label' => 'Khuyến mại', 'icon' => 'fas fa-tags', 'href' => BASE_URL . '/admin/promotions.php'],
    ['key' => 'news.php', 'label' => 'Tin Tức', 'icon' => 'fas fa-newspaper', 'href' => BASE_URL . '/admin/news.php'],
    ['key' => 'reviews.php', 'label' => 'Đánh giá', 'icon' => 'fas fa-comments', 'href' => BASE_URL . '/admin/reviews.php'],
    ['key' => 'users.php', 'label' => 'Người dùng', 'icon' => 'fas fa-users', 'href' => BASE_URL . '/admin/users.php'],
    ['key' => 'settings.php', 'label' => 'Cài đặt', 'icon' => 'fas fa-cog', 'href' => BASE_URL . '/admin/settings.php'],
];
?>
<style>
    .admin-sidebar { position: sticky; top: 0; height: 100vh; width: 260px; background-color: var(--white); border-right: 2px solid #eee; padding: 20px 10px; }
    .admin-sidebar .menu-title { font-weight: 700; color: var(--primary); font-size: 16px; padding: 0 10px 10px; }
    .admin-menu { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
    .admin-menu a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; color: var(--dark); text-decoration: none; transition: background 0.25s ease, color 0.25s ease; }
    .admin-menu a i { width: 20px; text-align: center; }
    .admin-menu a:hover { background-color: var(--light); }
    .admin-menu a.active { background-color: var(--secondary); color: var(--white); }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 2px solid #eee; } }
</style>
<aside class="admin-sidebar" aria-label="Admin Sidebar">
    <div class="menu-title">Điều hướng</div>
    <ul class="admin-menu">
        <?php foreach ($menu as $item): $active = ($current_page === $item['key']); ?>
            <li><a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($item['href']); ?>"><i class="<?php echo $item['icon']; ?>"></i><span><?php echo htmlspecialchars($item['label']); ?></span></a></li>
        <?php endforeach; ?>
    </ul>
</aside>

