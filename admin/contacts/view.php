<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$contactId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($contactId <= 0) {
    header('Location: ' . BASE_URL . '/admin/contacts/index.php');
    exit;
}

$errors = [];
$successMessage = '';
$contact = null;

$allowedStatuses = [
    'new' => 'Mới',
    'read' => 'Đã đọc',
    'replied' => 'Đã phản hồi',
    'archived' => 'Lưu trữ',
];

try {
    $pdo = getPDO();

    $contactStmt = $pdo->prepare('SELECT * FROM contact_messages WHERE message_id = :id');
    $contactStmt->execute([':id' => $contactId]);
    $contact = $contactStmt->fetch();

    if (!$contact) {
        $errors[] = 'Không tìm thấy liên hệ.';
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

            if ($action === 'update_status') {
                $newStatus = isset($_POST['status']) ? (string) $_POST['status'] : '';
                if (!array_key_exists($newStatus, $allowedStatuses)) {
                    $errors[] = 'Trạng thái không hợp lệ.';
                } else {
                    $updateStmt = $pdo->prepare('UPDATE contact_messages SET status = :status, updated_at = NOW() WHERE message_id = :id');
                    $updateStmt->execute([
                        ':status' => $newStatus,
                        ':id' => $contactId,
                    ]);
                    $successMessage = 'Đã cập nhật trạng thái liên hệ.';
                }
            }
        }

        if (!$errors) {
            $contactStmt->execute([':id' => $contactId]);
            $contact = $contactStmt->fetch();

            if ($contact && $contact['status'] === 'new') {
                $markReadStmt = $pdo->prepare('UPDATE contact_messages SET status = "read", updated_at = NOW() WHERE message_id = :id');
                $markReadStmt->execute([':id' => $contactId]);
                $contact['status'] = 'read';
            }
        }
    }
} catch (Throwable $exception) {
    $errors[] = 'Không thể tải liên hệ. Vui lòng thử lại sau.';
    $contact = null;
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

    .contact-view__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
    }

    .contact-view__title h1 {
        font-size: 26px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 6px;
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

    .contact-meta-card {
        background: rgba(255, 247, 237, 0.85);
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid rgba(210, 100, 38, 0.15);
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
    }

    .contact-meta-card__item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .contact-meta-card__label {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.55);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .contact-meta-card__value {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark);
    }

    .contact-content {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 16px;
        border: 1px solid #f0ebe3;
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        line-height: 1.65;
        color: rgba(0, 0, 0, 0.75);
    }

    .contact-content__subject {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary);
    }

    .contact-status {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(210, 100, 38, 0.12);
        color: var(--secondary);
        font-weight: 600;
    }

    .contact-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .contact-actions form {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .contact-actions select {
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        padding: 8px 12px;
        font-size: 14px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--secondary), #f7c76a);
        color: var(--white);
        border: none;
        border-radius: 10px;
        padding: 10px 18px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 18px rgba(210, 100, 38, 0.25);
    }

    .btn-secondary {
        background: rgba(0, 0, 0, 0.08);
        color: var(--dark);
        border: none;
        border-radius: 10px;
        padding: 10px 18px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-secondary:hover {
        background: rgba(0, 0, 0, 0.12);
    }

    .notice {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        border-radius: 12px;
        padding: 14px 18px;
        border: 1px solid transparent;
    }

    .notice--error {
        background: rgba(210, 64, 38, 0.12);
        color: #a52f1c;
        border-color: rgba(210, 64, 38, 0.35);
    }

    .notice--success {
        background: rgba(63, 142, 63, 0.12);
        color: #2a6a2a;
        border-color: rgba(63, 142, 63, 0.35);
    }

    .back-link {
        text-decoration: none;
        font-weight: 600;
        color: var(--secondary);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .contact-view__header {
            flex-direction: column;
            align-items: flex-start;
        }

        .contact-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .contact-actions form {
            width: 100%;
        }

        .contact-actions select,
        .btn-primary,
        .btn-secondary {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content contact-view">
        <div class="contact-view__header">
            <div class="contact-view__title">
                <h1>Chi Tiết Liên Hệ</h1>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a>
                    <span>/</span>
                    <a href="<?php echo BASE_URL; ?>/admin/contacts/index.php">Quản Lý Liên Hệ</a>
                    <span>/</span>
                    <span>Chi tiết</span>
                </nav>
                <?php if ($contact): ?>
                    <span class="contact-status"><i class="fas fa-envelope-open"></i> <?php echo $allowedStatuses[$contact['status']] ?? ucfirst($contact['status']); ?></span>
                <?php endif; ?>
            </div>
            <a class="back-link" href="<?php echo BASE_URL; ?>/admin/contacts/index.php"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
        </div>

        <?php if ($errors): ?>
            <div class="notice notice--error" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Không thể hiển thị liên hệ:</strong>
                    <ul style="margin:8px 0 0;padding-left:20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php elseif ($successMessage): ?>
            <div class="notice notice--success" role="status">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($contact): ?>
            <div class="contact-meta-card" role="presentation">
                <div class="contact-meta-card__item">
                    <span class="contact-meta-card__label">Họ tên</span>
                    <span class="contact-meta-card__value"><?php echo htmlspecialchars($contact['name']); ?></span>
                </div>
                <div class="contact-meta-card__item">
                    <span class="contact-meta-card__label">Email</span>
                    <span class="contact-meta-card__value"><?php echo htmlspecialchars($contact['email']); ?></span>
                </div>
                <div class="contact-meta-card__item">
                    <span class="contact-meta-card__label">Số điện thoại</span>
                    <span class="contact-meta-card__value"><?php echo htmlspecialchars($contact['phone']); ?></span>
                </div>
                <div class="contact-meta-card__item">
                    <span class="contact-meta-card__label">Ngày gửi</span>
                    <span class="contact-meta-card__value"><?php echo date('d/m/Y H:i', strtotime((string) $contact['created_at'])); ?></span>
                </div>
                <div class="contact-meta-card__item">
                    <span class="contact-meta-card__label">Cập nhật gần nhất</span>
                    <span class="contact-meta-card__value"><?php echo date('d/m/Y H:i', strtotime((string) $contact['updated_at'])); ?></span>
                </div>
            </div>

            <div class="contact-actions" role="group" aria-label="Hành động">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    <input type="hidden" name="action" value="update_status">
                    <label class="sr-only" for="status">Cập nhật trạng thái</label>
                    <select id="status" name="status">
                        <?php foreach ($allowedStatuses as $statusKey => $statusLabel): ?>
                            <option value="<?php echo htmlspecialchars($statusKey); ?>" <?php echo ($contact['status'] === $statusKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Lưu trạng thái</button>
                </form>
            </div>

            <article class="contact-content" aria-labelledby="contact-subject">
                <header>
                    <h2 class="contact-content__subject" id="contact-subject"><?php echo htmlspecialchars($contact['subject']); ?></h2>
                </header>
                <div>
                    <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                </div>
            </article>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
