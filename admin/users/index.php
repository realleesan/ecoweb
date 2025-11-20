<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$pdo = getPDO();

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    if ($action === 'deactivate') {
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        if ($userId <= 0) {
            $errors[] = 'Người dùng không hợp lệ.';
        } else {
            try {
                $userStmt = $pdo->prepare('SELECT user_id, username, role, is_active FROM users WHERE user_id = :id');
                $userStmt->execute([':id' => $userId]);
                $user = $userStmt->fetch();
                if (!$user) {
                    $errors[] = 'Người dùng không tồn tại.';
                } else {
                    if ((int)($user['user_id'] ?? 0) === (int)($_SESSION['admin_id'] ?? 0)) {
                        $errors[] = 'Không thể ngừng hoạt động tài khoản đang đăng nhập.';
                    } else {
                        if ((int)($user['is_active'] ?? 0) === 0) {
                            $successMessage = 'Tài khoản đã ở trạng thái ngừng hoạt động.';
                        } else {
                            $upd = $pdo->prepare('UPDATE users SET is_active = 0 WHERE user_id = :id');
                            $upd->execute([':id' => $userId]);
                            $successMessage = 'Đã chuyển người dùng sang trạng thái ngừng hoạt động.';
                        }
                    }
                }
            } catch (Throwable $e) {
                $errors[] = 'Không thể cập nhật trạng thái.';
            }
        }
    } elseif ($action === 'delete') {
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        if ($userId <= 0) {
            $errors[] = 'Người dùng không hợp lệ.';
        } else {
            try {
                $userStmt = $pdo->prepare('SELECT user_id, username, role, is_active FROM users WHERE user_id = :id');
                $userStmt->execute([':id' => $userId]);
                $user = $userStmt->fetch();
                if (!$user) {
                    $errors[] = 'Người dùng không tồn tại.';
                } else {
                    if ($user['role'] === 'admin') {
                        $errors[] = 'Không thể xóa tài khoản quản trị.';
                    } elseif ((int)($user['user_id'] ?? 0) === (int)($_SESSION['admin_id'] ?? 0)) {
                        $errors[] = 'Không thể xóa tài khoản đang đăng nhập.';
                    } else {
                        $orderCountStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = :id');
                        $orderCountStmt->execute([':id' => $userId]);
                        $orderCount = (int)$orderCountStmt->fetchColumn();
                        if ($orderCount > 0) {
                            $errors[] = 'Không thể xóa người dùng đang có ' . $orderCount . ' đơn hàng.';
                        } else {
                            $del = $pdo->prepare('DELETE FROM users WHERE user_id = :id');
                            $del->execute([':id' => $userId]);
                            $successMessage = 'Đã xóa người dùng "' . ($user['username'] ?? '') . '".';
                        }
                    }
                }
            } catch (Throwable $e) {
                $errors[] = 'Không thể xóa người dùng.';
            }
        }
    }
}

$conditions = [];
$params = [];
if ($keyword !== '') {
    $conditions[] = '(u.username LIKE :kw OR u.email LIKE :kw OR u.full_name LIKE :kw OR u.phone LIKE :kw OR u.address LIKE :kw)';
    $params[':kw'] = '%' . $keyword . '%';
}
if (in_array($roleFilter, ['user', 'admin'], true)) {
    $conditions[] = 'u.role = :role';
    $params[':role'] = $roleFilter;
}
if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $conditions[] = 'u.is_active = :st';
    $params[':st'] = $statusFilter === 'active' ? 1 : 0;
}
$whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$itemsPerPage = PAGINATION_CATEGORIES_PER_PAGE;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

$totalUsers = 0;
try {
    $countSql = "SELECT COUNT(*) FROM users u $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) { $countStmt->bindValue($k, $v); }
    $countStmt->execute();
    $totalUsers = (int) $countStmt->fetchColumn();
} catch (Throwable $e) { $totalUsers = 0; }

$users = [];
try {
    $sql = "
        SELECT
            u.user_id, u.username, u.email, u.full_name, u.phone, u.address, u.role, u.is_active, u.created_at,
            COALESCE(o.order_count, 0) AS order_count,
            COALESCE(o.total_spend, 0) AS total_spend
        FROM users u
        LEFT JOIN (
            SELECT user_id, COUNT(*) AS order_count, SUM(COALESCE(final_amount, total_amount)) AS total_spend
            FROM orders
            GROUP BY user_id
        ) o ON o.user_id = u.user_id
        $whereSql
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Throwable $e) { $users = []; }

include __DIR__ . '/../includes/header.php';
?>
<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; display: flex; flex-direction: column; gap: 30px; }
    .users-page__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; }
    .users-page__title-group h1 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 14px; flex-wrap: wrap; margin-bottom: 6px; }
    .breadcrumb a { color: var(--secondary); text-decoration: none; font-weight: 600; }
    .breadcrumb span { color: rgba(0,0,0,0.55); }
    .users-filter { background-color: rgba(255, 247, 237, 0.9); border-radius: 14px; padding: 18px 20px; border: 1px solid rgba(210, 100, 38, 0.15); display: grid; grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) minmax(160px, 1fr) auto; gap: 16px; align-items: end; }
    .users-filter__field label { font-weight: 600; font-size: 13px; color: var(--dark); margin-bottom: 6px; display: block; }
    .users-filter__field input, .users-filter__field select { width: 100%; border-radius: 10px; border: 1px solid #e5e5e5; padding: 10px 14px; font-size: 14px; transition: border 0.2s ease, box-shadow 0.2s ease; background-color: var(--white); }
    .users-filter__field input:focus, .users-filter__field select:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.15); outline: none; }
    .users-filter__actions { display: flex; align-items: center; justify-content: flex-end; }
    .btn-filter-submit { padding: 10px 18px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; background: var(--secondary); color: var(--white); }
    .btn-filter-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(210, 100, 38, 0.28); }
    .user-table-wrapper { border-radius: 14px; border: 1px solid #f0ebe3; overflow: hidden; background: var(--white); }
    .user-table { width: 100%; display: block; }
    .user-table { width: 100%; display: block; }
    .user-row { width: 100%; display: grid; grid-template-columns: 60px minmax(140px, 1.5fr) 90px 70px 110px 80px 140px; gap: 8px; align-items: center; padding: 14px 16px; background-color: var(--white); border-bottom: 1px solid #f3f1ed; box-sizing: border-box; }
    .user-row:last-child { border-bottom: none; }
    .user-row--head { width: 100%; background-color: rgba(255, 247, 237, 0.75); font-weight: 600; color: rgba(0,0,0,0.6); text-transform: uppercase; font-size: 12px; letter-spacing: 0.75px; position: relative; box-sizing: border-box; }
    .user-row--head::before { content: ''; position: absolute; inset: 0; background: rgba(255, 247, 237, 0.75); z-index: 0; }
    .user-row--head .user-col { position: relative; z-index: 1; }
    .user-col { display: flex; align-items: center; gap: 8px; font-size: 14px; }
    .user-thumb { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; background: rgba(210, 100, 38, 0.1); color: var(--secondary); font-size: 16px; overflow: hidden; }
    .user-name { display: flex; flex-direction: column; gap: 4px; }
    .user-name strong { font-weight: 700; color: var(--dark); font-size: 14px; }
    .user-name span { font-size: 12px; color: rgba(0,0,0,0.55); }
    .badge-counter { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px; border-radius: 999px; font-weight: 600; color: var(--white); background: var(--secondary); font-size: 13px; }
    .status-toggle { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 26px; border-radius: 999px; background: rgba(60, 96, 60, 0.15); }
    .status-toggle__icon { width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(60, 96, 60, 0.9); color: var(--white); font-size: 10px; }
    .status-toggle--inactive { background: rgba(0,0,0,0.08); }
    .status-toggle--inactive .status-toggle__icon { background: rgba(120, 120, 120, 0.65); color: var(--white); }
    .user-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; width: 100%; max-width: 80px; }
    .user-actions a, .user-actions button { width: 100%; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: var(--dark); background: rgba(0, 0, 0, 0.06); text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; border: none; cursor: pointer; font-size: 13px; }
    .user-actions a:hover, .user-actions button:hover { transform: translateY(-2px); box-shadow: 0 8px 14px rgba(0, 0, 0, 0.12); }
    @media (max-width: <?php echo BREAKPOINT_LG; ?>) { .user-row { grid-template-columns: 60px 1.5fr 90px 70px 110px 80px 130px; padding: 14px; } }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .user-row { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-template-areas: 'thumb name' 'role role' 'orders spend' 'status status' 'actions actions'; gap: 12px 18px; }
        .user-row--head { display: none; }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content users-page">
        <div class="users-page__header">
            <div class="users-page__title-group">
                <h1>Quản Lý Người Dùng</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <span>Quản Lý Người Dùng</span>
                </nav>
                <p>Tổng cộng: <?php echo $totalUsers; ?> người dùng</p>
            </div>
            
        </div>

        <?php if ($successMessage): ?>
            <div class="notice notice--success" role="status" style="display:flex;align-items:flex-start;gap:12px;border-radius:12px;padding:14px 18px;background:rgba(63,142,63,0.12);color:#2a6a2a;border:1px solid rgba(63,142,63,0.35);">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="notice notice--error" role="alert" style="display:flex;align-items:flex-start;gap:12px;border-radius:12px;padding:14px 18px;background:rgba(210,64,38,0.12);color:#a52f1c;border:1px solid rgba(210,64,38,0.35);">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Không thể thực hiện thao tác:</strong>
                    <ul style="margin:8px 0 0;padding-left:20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form class="users-filter" method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="users-filter__field">
                <label for="q">Tìm Kiếm</label>
                <input type="text" id="q" name="q" placeholder="Tên, email, số điện thoại..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="users-filter__field">
                <label for="role">Vai Trò</label>
                <select id="role" name="role">
                    <option value="" <?php echo $roleFilter === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>Người dùng</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Quản trị</option>
                </select>
            </div>
            <div class="users-filter__field">
                <label for="status">Trạng Thái</label>
                <select id="status" name="status">
                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
                </select>
            </div>
            <div class="users-filter__actions">
                <button type="submit" class="btn-filter-submit"><i class="fas fa-filter"></i> Lọc</button>
            </div>
        </form>

        <div class="user-table-wrapper">
            <div class="user-table" role="table" aria-label="Danh sách người dùng">
                    <div class="user-row user-row--head" role="row">
                        <div class="user-col" role="columnheader">Ảnh</div>
                        <div class="user-col" role="columnheader">Người dùng</div>
                        <div class="user-col" role="columnheader">Vai trò</div>
                        <div class="user-col" role="columnheader">Đơn hàng</div>
                        <div class="user-col" role="columnheader">Tổng chi tiêu</div>
                        <div class="user-col" role="columnheader">Trạng thái</div>
                        <div class="user-col" role="columnheader">Thao tác</div>
                    </div>

                    <?php foreach ($users as $u):
                        $isActive = (int) ($u['is_active'] ?? 0) === 1;
                        $statusLabel = $isActive ? 'Hoạt động' : 'Không hoạt động';
                        $username = (string) ($u['username'] ?? '');
                        $initial = mb_substr($username, 0, 1, 'UTF-8');
                        $roleLabel = $u['role'] === 'admin' ? 'Quản trị' : 'Người dùng';
                        $viewUrl = BASE_URL . '/admin/users/view.php?id=' . (int) $u['user_id'];
                        $editUrl = BASE_URL . '/admin/users/edit.php?id=' . (int) $u['user_id'];
                    ?>
                        <div class="user-row" role="row">
                            <div class="user-col" role="cell"><span class="user-thumb"><span><?php echo htmlspecialchars($initial); ?></span></span></div>
                            <div class="user-col" role="cell">
                                <div class="user-name">
                                    <strong><?php echo htmlspecialchars($username); ?></strong>
                                </div>
                            </div>
                            <div class="user-col" role="cell"><span><?php echo htmlspecialchars($roleLabel); ?></span></div>
                            <div class="user-col" role="cell"><?php echo (int) ($u['order_count'] ?? 0); ?></div>
                            <div class="user-col" role="cell"><?php echo number_format((float) ($u['total_spend'] ?? 0), 0, ',', '.'); ?> đ</div>
                            <div class="user-col" role="cell">
                                <span class="status-toggle <?php echo $isActive ? '' : 'status-toggle--inactive'; ?>" aria-hidden="true">
                                    <span class="status-toggle__icon"><i class="fas <?php echo $isActive ? 'fa-check' : 'fa-minus'; ?>"></i></span>
                                </span>
                            </div>
                            <div class="user-col" role="cell">
                                <div class="user-actions">
                                    <a class="action-edit" href="<?php echo htmlspecialchars($editUrl); ?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                    <a class="action-view" href="<?php echo htmlspecialchars($viewUrl); ?>" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                    <button type="button" class="action-deactivate" data-user-id="<?php echo (int) $u['user_id']; ?>" title="Ngừng hoạt động"><i class="fas fa-ban"></i></button>
                                    <?php if (($u['role'] ?? 'user') !== 'admin'): ?>
                                        <button type="button" class="action-delete" data-user-id="<?php echo (int) $u['user_id']; ?>" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
            </div>
        </div>

        <div class="pagination-wrap">
            <?php
            $current_page = $page;
            $total_pages = max(1, (int) ceil($totalUsers / $itemsPerPage));
            include __DIR__ . '/../../includes/components/pagination.php';
            ?>
        </div>
    </main>
</div>

<form method="post" id="user-deactivate-form" class="sr-only">
    <input type="hidden" name="action" value="deactivate">
    <input type="hidden" name="user_id" id="deactivate-user-id" value="">
</form>

<form method="post" id="user-delete-form" class="sr-only">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="user_id" id="delete-user-id" value="">
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var deactivateForm = document.getElementById('user-deactivate-form');
        var deactivateInput = document.getElementById('deactivate-user-id');
        var deactivateButtons = document.querySelectorAll('.action-deactivate');
        deactivateButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var uid = btn.getAttribute('data-user-id');
                var confirmed = window.confirm('Bạn có chắc muốn ngừng hoạt động người dùng này?');
                if (!confirmed) return;
                if (!deactivateForm || !deactivateInput) return;
                deactivateInput.value = uid;
                deactivateForm.submit();
            });
        });

        var deleteForm = document.getElementById('user-delete-form');
        var deleteInput = document.getElementById('delete-user-id');
        var deleteButtons = document.querySelectorAll('.action-delete');
        deleteButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var uid = btn.getAttribute('data-user-id');
                var confirmed = window.confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động không thể hoàn tác.');
                if (!confirmed) return;
                if (!deleteForm || !deleteInput) return;
                deleteInput.value = uid;
                deleteForm.submit();
            });
        });
    });
</script>