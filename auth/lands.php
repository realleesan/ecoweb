<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getPDO();

// Find partner record(s) for this user
$stmt = $pdo->prepare("SELECT p.id AS partner_id, p.full_name, l.id AS land_id, l.name AS land_name, l.status, l.created_at
                       FROM partners p
                       LEFT JOIN lands l ON l.partner_id = p.id
                       WHERE p.user_id = :uid
                       ORDER BY l.created_at DESC");
$stmt->execute([':uid' => $user['user_id']]);
$rows = $stmt->fetchAll();

// Build a list of lands
$lands = [];
foreach ($rows as $r) {
    if ($r['land_id']) {
        $lands[] = $r;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .account-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .account-layout { grid-template-columns: 1fr; } }
    .account-content { background-color: var(--white); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; }
    .lands-empty { text-align: center; padding: 40px; border-radius: 12px; background: linear-gradient(135deg, var(--bg-green), #9fbf59); color: #fff; }
    .lands-empty h2 { font-size: 22px; margin-bottom: 12px; }
    .lands-empty p { color: rgba(255,255,255,0.95); margin-bottom: 18px; }
    .btn-join { background: var(--secondary); color: var(--white); padding: 12px 22px; border-radius: 28px; text-decoration: none; font-weight:700; }
    .lands-list table { width: 100%; border-collapse: collapse; }
    .lands-list th, .lands-list td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
</style>

<div class="account-layout">
    <?php include __DIR__ . '/sidebar-account.php'; ?>
    <main class="account-content">
        <?php if (empty($lands)): ?>
            <div class="lands-empty">
                <i class="fas fa-map-marked-alt" style="font-size:40px;margin-bottom:12px;display:block;"></i>
                <h2>Đăng ký trở thành đối tác cung cấp đất của GROWHOPE</h2>
                <p>Hiện tại bạn chưa có mẫu đất nào được đăng ký. Hãy đăng ký trở thành đối tác để gửi thông tin mẫu đất và quản lý khu đất của bạn trên bản đồ phủ xanh.</p>
                <a class="btn-join" href="<?php echo BASE_URL; ?>/views/partners.php">Đăng ký ngay</a>
            </div>
        <?php else: ?>
            <h2 style="margin-bottom:10px;">Mẫu đất của bạn</h2>
            <div class="lands-list">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên mẫu đất</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lands as $l): ?>
                        <tr>
                            <td><?php echo $l['land_id']; ?></td>
                            <td><?php echo htmlspecialchars($l['land_name']); ?></td>
                            <td><?php echo htmlspecialchars($l['status']); ?></td>
                            <td><?php echo $l['created_at']; ?></td>
                            <td>
                                <?php if ($l['status'] === 'pending'): ?>
                                    <span style="color:#d08">Đang chờ duyệt</span>
                                <?php elseif ($l['status'] === 'approved'): ?>
                                    <a href="<?php echo BASE_URL; ?>/index.php#">Xem trên bản đồ</a>
                                <?php else: ?>
                                    <span style="color:#c33">Bị từ chối</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:18px;">
                <a class="btn-join" href="<?php echo BASE_URL; ?>/views/partners.php">Đăng ký thêm mẫu đất</a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


