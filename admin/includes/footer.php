<?php
require_once __DIR__ . '/../../includes/config.php';
?>
<style>
    .admin-footer { width: 100%; margin-top: 30px; padding: 20px <?php echo CONTAINER_PADDING; ?>; background-color: var(--white); border-top: 2px solid #eee; color: var(--dark); display: flex; align-items: center; justify-content: space-between; }
    .admin-footer .brand { font-weight: 700; color: var(--primary); }
    .admin-footer .links { display: flex; gap: 15px; }
    .admin-footer a { color: var(--dark); text-decoration: none; }
    .admin-footer a:hover { color: var(--primary); }
    @media (max-width: <?php echo BREAKPOINT_SM; ?>) { .admin-footer { flex-direction: column; gap: 10px; } }
</style>
<footer class="admin-footer">
    <div class="brand"><?php echo BRAND_NAME; ?> Admin</div>
    <div class="links">
        <span>&copy; <?php echo date('Y'); ?></span>
        <a href="<?php echo BASE_URL; ?>/index.php">Trang người dùng</a>
    </div>
</footer>
</body>
</html>

