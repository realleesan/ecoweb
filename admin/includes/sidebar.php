<?php
require_once __DIR__ . '/../../includes/config.php';
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$adminBase = rtrim(BASE_URL, '/') . '/admin/';
$current_page = trim(str_replace($adminBase, '', $uriPath), '/');
$current_page = $current_page === '' ? 'index.php' : $current_page;

$menu = [
    ['key' => 'index.php', 'label' => 'Tổng quát', 'icon' => 'fas fa-chart-pie', 'href' => BASE_URL . '/admin/index.php'],
    ['key' => 'categories/index.php', 'match' => 'categories/', 'label' => 'Danh mục', 'icon' => 'fas fa-list', 'href' => BASE_URL . '/admin/categories/index.php'],
    ['key' => 'orders/index.php', 'match' => 'orders/', 'label' => 'Đơn hàng', 'icon' => 'fas fa-shopping-bag', 'href' => BASE_URL . '/admin/orders/index.php'],
    ['key' => 'products/index.php', 'match' => 'products/', 'label' => 'Sản phẩm', 'icon' => 'fas fa-leaf', 'href' => BASE_URL . '/admin/products/index.php'],
    ['key' => 'coupons/index.php', 'match' => 'coupons/', 'label' => 'Khuyến mại', 'icon' => 'fas fa-tags', 'href' => BASE_URL . '/admin/coupons/index.php'],
    ['key' => 'news/index.php', 'match' => 'news/', 'label' => 'Tin Tức', 'icon' => 'fas fa-newspaper', 'href' => BASE_URL . '/admin/news/index.php'],
    ['key' => 'lands/index.php', 'match' => 'lands/', 'label' => 'Mẫu đất', 'icon' => 'fas fa-map', 'href' => BASE_URL . '/admin/lands/index.php'],
    ['key' => 'contacts/index.php', 'match' => 'contacts/', 'label' => 'Liên hệ', 'icon' => 'fas fa-envelope', 'href' => BASE_URL . '/admin/contacts/index.php'],
    ['key' => 'reviews/index.php', 'match' => 'reviews/', 'label' => 'Đánh giá', 'icon' => 'fas fa-comments', 'href' => BASE_URL . '/admin/reviews/index.php'],
    ['key' => 'users/index.php', 'match' => 'users/', 'label' => 'Người dùng', 'icon' => 'fas fa-users', 'href' => BASE_URL . '/admin/users/index.php'],
    ['key' => 'settings/index.php', 'match' => 'settings/', 'label' => 'Cài đặt', 'icon' => 'fas fa-cog', 'href' => BASE_URL . '/admin/settings/index.php'],
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
        <?php foreach ($menu as $item):
            $active = ($current_page === $item['key']);
            if (!$active && isset($item['match'])) {
                $active = strpos($current_page, $item['match']) === 0;
            }
        ?>
            <li><a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($item['href']); ?>"><i class="<?php echo $item['icon']; ?>"></i><span><?php echo htmlspecialchars($item['label']); ?></span></a></li>
        <?php endforeach; ?>
    </ul>
</aside>
