<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

$errors = [];
$contacts = [];
$totalContacts = 0;

try {
    $pdo = getPDO();

    $conditions = [];
    $params = [];

    if ($keyword !== '') {
        $conditions[] = '(
            cm.name LIKE :keyword OR
            cm.email LIKE :keyword OR
            cm.phone LIKE :keyword OR
            cm.subject LIKE :keyword OR
            cm.message LIKE :keyword
        )';
        $params[':keyword'] = '%' . $keyword . '%';
    }

    if (in_array($statusFilter, ['new', 'read', 'replied', 'archived'], true)) {
        $conditions[] = 'cm.status = :status';
        $params[':status'] = $statusFilter;
    }

    $dateErrors = false;
    if ($fromDate !== '') {
        $fromDateObject = DateTime::createFromFormat('d/m/Y', $fromDate);
        if ($fromDateObject === false) {
            $errors[] = 'Ngày bắt đầu không hợp lệ.';
            $dateErrors = true;
        } else {
            $conditions[] = 'cm.created_at >= :from_date';
            $params[':from_date'] = $fromDateObject->format('Y-m-d 00:00:00');
        }
    }

    if ($toDate !== '') {
        $toDateObject = DateTime::createFromFormat('d/m/Y', $toDate);
        if ($toDateObject === false) {
            $errors[] = 'Ngày kết thúc không hợp lệ.';
            $dateErrors = true;
        } else {
            $conditions[] = 'cm.created_at <= :to_date';
            $params[':to_date'] = $toDateObject->format('Y-m-d 23:59:59');
        }
    }

    if ($fromDate !== '' && $toDate !== '' && !$dateErrors) {
        if ($fromDateObject > $toDateObject) {
            $errors[] = 'Ngày bắt đầu phải trước hoặc cùng ngày kết thúc.';
        }
    }

    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $sql = "
        SELECT
            cm.message_id,
            cm.name,
            cm.email,
            cm.phone,
            cm.subject,
            cm.message,
            cm.status,
            cm.created_at,
            cm.updated_at
        FROM contact_messages cm
        $whereClause
        ORDER BY cm.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    $contacts = $stmt->fetchAll();
    $totalContacts = count($contacts);
} catch (Throwable $exception) {
    $errors[] = 'Không thể tải danh sách liên hệ. Vui lòng thử lại sau.';
    $contacts = [];
    $totalContacts = 0;
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-layout {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 20px auto;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: <?php echo GRID_GAP; ?>;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .admin-layout {
            grid-template-columns: 1fr;
        }
    }

    .admin-content {
        background-color: var(--white);
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .contacts-page__header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }

    .contacts-page__title-group h1 {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 6px;
    }

    .contacts-page__title-group p {
        color: rgba(0, 0, 0, 0.55);
        font-size: 14px;
    }

    .breadcrumb {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 14px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }

    .breadcrumb a {
        color: var(--secondary);
        text-decoration: none;
        font-weight: 600;
    }

    .breadcrumb span {
        color: rgba(0, 0, 0, 0.55);
    }

    .contacts-filter {
        background-color: rgba(255, 247, 237, 0.9);
        border-radius: 16px;
        padding: 14px 16px;
        border: 1px solid rgba(210, 100, 38, 0.15);
        display: grid;
        grid-template-columns: minmax(0, 2.8fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 0.6fr);
        column-gap: 12px;
        row-gap: 10px;
        align-items: end;
        overflow: hidden;
    }

    .contacts-filter__field label {
        font-weight: 600;
        font-size: 12px;
        color: var(--dark);
        margin-bottom: 4px;
        display: block;
    }

    .contacts-filter__field input,
    .contacts-filter__field select {
        width: 100%;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        padding: 8px 12px;
        font-size: 13px;
        background-color: var(--white);
        transition: border 0.2s ease, box-shadow 0.2s ease;
    }

    .contacts-filter__field--status {
        max-width: 160px;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .contacts-filter__field input:focus,
    .contacts-filter__field select:focus {
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(210, 100, 38, 0.15);
        outline: none;
    }

    .contacts-filter__actions {
        display: flex;
        justify-content: stretch;
        align-items: flex-end;
        align-self: end;
    }

    .btn-filter-submit {
        padding: 0 14px;
        width: 100%;
        height: 38px;
        border-radius: 9px;
        border: 1px solid rgba(210, 100, 38, 0.35);
        font-weight: 600;
        cursor: pointer;
        background: var(--white);
        color: var(--secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-filter-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(210, 100, 38, 0.18);
    }

    .contacts-table-wrapper {
        border-radius: 16px;
        border: 1px solid #f0ebe3;
        overflow: hidden;
        background: var(--white);
    }

    .contacts-table-scroll {
        overflow-x: auto;
    }

    .contacts-table {
        width: 100%;
    }

    .contacts-row {
        display: grid;
        grid-template-columns: 40px minmax(0, 1.3fr) minmax(0, 1.2fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 0.8fr) 60px;
        gap: 12px;
        align-items: center;
        padding: 18px 22px;
        border-bottom: 1px solid #f3f1ed;
    }

    .contacts-row:last-child {
        border-bottom: none;
    }

    .contacts-row--head {
        background-color: rgba(255, 247, 237, 0.75);
        font-weight: 600;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.75px;
    }

    .contacts-col {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .contacts-col span {
        display: block;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .contacts-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 12px;
    }

    .contacts-status--new { background: rgba(60, 96, 60, 0.15); color: var(--primary); }
    .contacts-status--read { background: rgba(39, 88, 185, 0.15); color: #2758b9; }
    .contacts-status--replied { background: rgba(63, 142, 63, 0.18); color: #3f8e3f; }
    .contacts-status--archived { background: rgba(120, 120, 120, 0.15); color: rgba(80, 80, 80, 0.95); }

    .contacts-actions a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.06);
        color: var(--dark);
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .contacts-actions a:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
    }

    .empty-state {
        padding: 60px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: rgba(0, 0, 0, 0.55);
        text-align: center;
    }

    .empty-state__icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(210, 100, 38, 0.12);
        color: var(--secondary);
        font-size: 28px;
    }

    .empty-state__title {
        font-weight: 600;
        font-size: 18px;
        color: var(--dark);
    }

    @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
        .contacts-row {
            grid-template-columns: 60px minmax(160px, 1.2fr) minmax(150px, 1fr) minmax(200px, 1.2fr) minmax(220px, 1.4fr) minmax(120px, 0.8fr) 80px;
            grid-template-areas:
                'index name email phone subject subject status actions';
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .contacts-filter {
            grid-template-columns: 1fr;
        }

        .contacts-filter__actions {
            justify-content: stretch;
        }

        .btn-filter-submit {
            width: 100%;
            justify-content: center;
        }

        .contacts-table {
            min-width: 100%;
        }

        .contacts-row {
            grid-template-columns: 1fr;
            grid-template-areas:
                'index'
                'name'
                'email'
                'phone'
                'subject'
                'message'
                'status'
                'actions';
            padding: 16px 18px;
        }

        .contacts-row--head {
            display: none;
        }

        .contacts-col {
            justify-content: flex-start;
            align-items: flex-start;
        }

        .contacts-col__label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.55);
            margin-bottom: 4px;
        }

        .contacts-actions {
            width: 100%;
        }

        .contacts-actions a {
            width: 42px;
            height: 42px;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .contacts-page__header {
            align-items: flex-start;
        }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content contacts-page">
        <div class="contacts-page__header">
            <div class="contacts-page__title-group">
                <h1>Quản Lý Liên Hệ</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <span>Quản Lý Liên Hệ</span>
                </nav>
                <p>Tổng cộng: <?php echo $totalContacts; ?> liên hệ</p>
            </div>
        </div>

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

        <form class="contacts-filter" method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="contacts-filter__field">
                <label for="keyword">Tìm Kiếm</label>
                <input type="text" id="keyword" name="keyword" placeholder="Tên, email, SĐT, tiêu đề..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="contacts-filter__field contacts-filter__field--status">
                <label for="status">Trạng Thái</label>
                <select id="status" name="status">
                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>Mới</option>
                    <option value="read" <?php echo $statusFilter === 'read' ? 'selected' : ''; ?>>Đã đọc</option>
                    <option value="replied" <?php echo $statusFilter === 'replied' ? 'selected' : ''; ?>>Đã phản hồi</option>
                    <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Lưu trữ</option>
                </select>
            </div>
            <div class="contacts-filter__field">
                <label for="from_date">Từ ngày</label>
                <input type="text" id="from_date" name="from_date" placeholder="dd/mm/yyyy" value="<?php echo htmlspecialchars($fromDate); ?>">
            </div>
            <div class="contacts-filter__field">
                <label for="to_date">Đến ngày</label>
                <input type="text" id="to_date" name="to_date" placeholder="dd/mm/yyyy" value="<?php echo htmlspecialchars($toDate); ?>">
            </div>
            <div class="contacts-filter__actions">
                <button type="submit" class="btn-filter-submit" aria-label="Lọc"><i class="fas fa-search"></i><span class="sr-only">Lọc</span></button>
            </div>
        </form>

        <div class="contacts-table-wrapper">
            <div class="contacts-table-scroll">
                <?php if ($contacts): ?>
                    <div class="contacts-table" role="table" aria-label="Danh sách liên hệ">
                        <div class="contacts-row contacts-row--head" role="row">
                            <div class="contacts-col" role="columnheader">#</div>
                            <div class="contacts-col" role="columnheader">Tên</div>
                            <div class="contacts-col" role="columnheader">Email</div>
                            <div class="contacts-col" role="columnheader">SĐT</div>
                            <div class="contacts-col" role="columnheader">Tiêu đề</div>
                            <div class="contacts-col" role="columnheader">Trạng thái</div>
                            <div class="contacts-col" role="columnheader">Chi tiết</div>
                        </div>

                        <?php foreach ($contacts as $index => $contact):
                            $status = (string) ($contact['status'] ?? 'new');
                            $statusClassMap = [
                                'new' => 'contacts-status--new',
                                'read' => 'contacts-status--read',
                                'replied' => 'contacts-status--replied',
                                'archived' => 'contacts-status--archived',
                            ];
                            $statusLabelMap = [
                                'new' => 'Mới',
                                'read' => 'Đã đọc',
                                'replied' => 'Đã phản hồi',
                                'archived' => 'Lưu trữ',
                            ];
                            $statusClass = $statusClassMap[$status] ?? 'contacts-status--new';
                            $statusLabel = $statusLabelMap[$status] ?? 'Mới';
                            $messageExcerpt = mb_substr(trim((string) ($contact['message'] ?? '')), 0, 80);
                            if (mb_strlen((string) ($contact['message'] ?? '')) > 80) {
                                $messageExcerpt .= '…';
                            }
                            $viewUrl = BASE_URL . '/admin/contacts/view.php?id=' . (int) $contact['message_id'];
                        ?>
                            <div class="contacts-row" role="row">
                                <div class="contacts-col" role="cell">
                                    <strong><?php echo $index + 1; ?></strong>
                                </div>
                                <div class="contacts-col" role="cell">
                                    <div>
                                        <strong><?php echo htmlspecialchars($contact['name']); ?></strong>
                                        <div style="font-size:12px;color:rgba(0,0,0,0.55);">
                                            <?php echo date('d/m/Y H:i', strtotime((string) $contact['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="contacts-col" role="cell">
                                    <span><?php echo htmlspecialchars($contact['email']); ?></span>
                                </div>
                                <div class="contacts-col" role="cell">
                                    <span><?php echo htmlspecialchars($contact['phone']); ?></span>
                                </div>
                                <div class="contacts-col" role="cell">
                                    <span><?php echo htmlspecialchars($contact['subject']); ?></span>
                                </div>
                                <div class="contacts-col" role="cell">
                                    <span class="contacts-status <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                </div>
                                <div class="contacts-col contacts-actions" role="cell">
                                    <a href="<?php echo htmlspecialchars($viewUrl); ?>" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" role="status">
                        <div class="empty-state__icon"><i class="fas fa-envelope-open-text"></i></div>
                        <div class="empty-state__title">Không tìm thấy liên hệ nào</div>
                        <p>Hãy thử thay đổi bộ lọc hoặc kiểm tra lại sau.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
