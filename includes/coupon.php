<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * Ensure orders table has coupon related columns.
 */
function ensureOrderCouponColumns(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured && coupon_order_columns_available($pdo)) {
        return;
    }

    try {
        $columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_code'");
        if ($columns && $columns->rowCount() === 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) NULL AFTER total_amount");
        }
    } catch (Throwable $e) {
        // silently ignore inability to alter schema
    }

    try {
        $columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_id'");
        if ($columns && $columns->rowCount() === 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN coupon_id INT NULL AFTER coupon_code");
        }
    } catch (Throwable $e) {
        // ignore
    }

    try {
        $columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'discount_amount'");
        if ($columns && $columns->rowCount() === 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER coupon_id");
        }
    } catch (Throwable $e) {
        // ignore
    }

    try {
        $columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'final_amount'");
        if ($columns && $columns->rowCount() === 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN final_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER discount_amount");
        }
    } catch (Throwable $e) {
        // ignore
    }

    $ensured = coupon_order_columns_available($pdo);
}

/**
 * Check if orders table already contains coupon-related columns.
 */
function coupon_order_columns_available(PDO $pdo): bool
{
    $required = ['coupon_code', 'coupon_id', 'discount_amount', 'final_amount'];

    try {
        foreach ($required as $column) {
            $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE '" . $column . "'");
            if (!$stmt || $stmt->rowCount() === 0) {
                return false;
            }
        }
    } catch (Throwable $e) {
        return false;
    }

    return true;
}

/**
 * Ensure coupon redemptions table exists for tracking per-user usage.
 */
function ensureCouponRedemptionTable(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS coupon_redemptions (
    redemption_id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NULL,
    redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_coupon_user_order (coupon_id, user_id, order_id),
    INDEX idx_coupon_user (coupon_id, user_id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(coupon_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

    try {
        $pdo->exec($sql);
    } catch (Throwable $e) {
        // ignore inability to create table
    }

    $ensured = true;
}

/**
 * Fetch coupon row with locking for updates when requested.
 */
function fetchCouponByCode(PDO $pdo, string $couponCode, bool $forUpdate = false): ?array
{
    if ($couponCode === '') {
        return null;
    }

    $sql = 'SELECT * FROM coupons WHERE coupon_code = :code';
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':code' => $couponCode]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    return $coupon ?: null;
}

/**
 * Validate coupon eligibility and calculate discount amount.
 *
 * @throws RuntimeException when coupon is invalid.
 */
function calculateCouponDiscount(PDO $pdo, array $coupon, int $userId, float $subtotal): array
{
    $now = new DateTimeImmutable('now');
    $status = strtolower((string) ($coupon['status'] ?? ''));

    if (!in_array($status, ['active', 'scheduled', 'draft', 'inactive', 'expired'], true) || $status !== 'active') {
        throw new RuntimeException('Mã giảm giá không ở trạng thái hoạt động.');
    }

    $start = !empty($coupon['start_date']) ? new DateTimeImmutable((string) $coupon['start_date']) : null;
    $end = !empty($coupon['end_date']) ? new DateTimeImmutable((string) $coupon['end_date']) : null;

    if ($start && $now < $start) {
        throw new RuntimeException('Mã giảm giá chưa đến thời gian áp dụng.');
    }

    if ($end && $now > $end) {
        throw new RuntimeException('Mã giảm giá đã hết hạn.');
    }

    $minOrderValue = isset($coupon['min_order_value']) ? (float) $coupon['min_order_value'] : null;
    if ($minOrderValue !== null && $minOrderValue > 0 && $subtotal < $minOrderValue) {
        throw new RuntimeException('Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã giảm giá.');
    }

    $usageLimit = isset($coupon['usage_limit']) ? (int) $coupon['usage_limit'] : null;
    $usedCount = isset($coupon['used_count']) ? (int) $coupon['used_count'] : 0;
    if ($usageLimit !== null && $usageLimit > 0 && $usedCount >= $usageLimit) {
        throw new RuntimeException('Mã giảm giá đã đạt giới hạn sử dụng.');
    }

    $perCustomerLimit = isset($coupon['per_customer_limit']) ? (int) $coupon['per_customer_limit'] : null;
    if ($perCustomerLimit !== null && $perCustomerLimit > 0) {
        ensureCouponRedemptionTable($pdo);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM coupon_redemptions WHERE coupon_id = :cid AND user_id = :uid');
        $stmt->execute([
            ':cid' => (int) $coupon['coupon_id'],
            ':uid' => $userId,
        ]);
        $userUsage = (int) $stmt->fetchColumn();
        if ($userUsage >= $perCustomerLimit) {
            throw new RuntimeException('Bạn đã sử dụng mã giảm giá này quá số lần cho phép.');
        }
    }

    if ($subtotal <= 0) {
        throw new RuntimeException('Giỏ hàng trống hoặc không có giá trị hợp lệ để áp dụng mã.');
    }

    $discountType = strtolower((string) ($coupon['discount_type'] ?? 'percent'));
    $discountValue = (float) ($coupon['discount_value'] ?? 0);
    if ($discountValue <= 0) {
        throw new RuntimeException('Mã giảm giá không có giá trị hợp lệ.');
    }

    if ($discountType === 'percent') {
        $computed = $subtotal * $discountValue / 100;
        $maxDiscount = isset($coupon['max_discount_value']) ? (float) $coupon['max_discount_value'] : null;
        if ($maxDiscount !== null && $maxDiscount > 0) {
            $computed = min($computed, $maxDiscount);
        }
        $discountAmount = min($computed, $subtotal);
    } else {
        $discountAmount = min($discountValue, $subtotal);
    }

    if ($discountAmount <= 0) {
        throw new RuntimeException('Giá trị giảm không hợp lệ.');
    }

    $final = max($subtotal - $discountAmount, 0);

    return [
        'discount_amount' => round($discountAmount, 2),
        'final_amount' => round($final, 2),
    ];
}

/**
 * Format response payload for coupon preview.
 */
function buildCouponPreview(array $coupon, float $discountAmount, float $finalAmount): array
{
    return [
        'coupon_code' => (string) $coupon['coupon_code'],
        'coupon_name' => (string) $coupon['coupon_name'],
        'discount_type' => (string) $coupon['discount_type'],
        'discount_value' => (float) $coupon['discount_value'],
        'discount_amount' => $discountAmount,
        'final_amount' => $finalAmount,
        'min_order_value' => isset($coupon['min_order_value']) ? (float) $coupon['min_order_value'] : null,
        'max_discount_value' => isset($coupon['max_discount_value']) ? (float) $coupon['max_discount_value'] : null,
        'usage_limit' => isset($coupon['usage_limit']) ? (int) $coupon['usage_limit'] : null,
        'used_count' => isset($coupon['used_count']) ? (int) $coupon['used_count'] : 0,
        'per_customer_limit' => isset($coupon['per_customer_limit']) ? (int) $coupon['per_customer_limit'] : null,
        'status' => (string) $coupon['status'],
        'start_date' => $coupon['start_date'] ?? null,
        'end_date' => $coupon['end_date'] ?? null,
    ];
}

