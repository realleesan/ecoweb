<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();


include __DIR__ . '/includes/header.php';
?>


<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; }
    .admin-content h1 { font-size: 24px; color: var(--primary); font-weight: 700; margin-bottom: 15px; }
    .admin-content p { color: #555; }
</style>


<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-content">
        <h1>Bảng điều khiển</h1>
        <p>Chào mừng quay lại, <?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?>.</p>
    </main>
</div>


<?php include __DIR__ . '/includes/footer.php'; ?>

