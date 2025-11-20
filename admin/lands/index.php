<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
requireAdminLogin();

$pdo = getPDO();
$stmt = $pdo->query("SELECT l.id, l.name, l.status, l.created_at, p.full_name AS partner_name
                    FROM lands l
                    JOIN partners p ON p.id = l.partner_id
                    ORDER BY l.created_at DESC");
$lands = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; }
    .admin-content h1 { font-size: 24px; color: var(--primary); font-weight: 700; margin-bottom: 15px; }
    .admin-content p { color: #555; }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-content">
        <h1>Quản lý Mẫu đất</h1>
        <p>Danh sách mẫu đất (mới nhất trước)</p>
        <table style="width:100%; border-collapse: collapse; margin-top:12px;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid #eee;">
                    <th style="padding:8px;">ID</th>
                    <th style="padding:8px;">Tên</th>
                    <th style="padding:8px;">Chủ đất</th>
                    <th style="padding:8px;">Trạng thái</th>
                    <th style="padding:8px;">Ngày tạo</th>
                    <th style="padding:8px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lands)): ?>
                <tr><td colspan="6" style="padding:12px;">Chưa có mẫu đất.</td></tr>
                <?php else: foreach ($lands as $l): ?>
                <tr style="border-bottom:1px solid #fafafa;">
                    <td style="padding:8px;"><?php echo $l['id']; ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($l['name']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($l['partner_name']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($l['status']); ?></td>
                    <td style="padding:8px;"><?php echo $l['created_at']; ?></td>
                    <td style="padding:8px;">
                        <a href="<?php echo BASE_URL; ?>/admin/lands/view.php?id=<?php echo $l['id']; ?>" class="btn-link">Xem</a>
                        <?php if ($l['status'] === 'pending'): ?>
                            <form method="POST" action="<?php echo BASE_URL; ?>/admin/lands/view.php" style="display:inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                                <button type="submit" class="btn-link">Duyệt</button>
                            </form>
                            <form method="POST" action="<?php echo BASE_URL; ?>/admin/lands/view.php" style="display:inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                                <button type="submit" class="btn-link" style="color:#c33">Từ chối</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/admin/lands/view.php" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa mẫu đất này? Hành động không thể hoàn tác.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                            <button type="submit" class="btn-link" style="color:#c33">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


