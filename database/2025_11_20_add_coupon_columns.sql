# Migration: add coupon tracking columns and coupon_redemptions table

SET @db_name := DATABASE();

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'coupon_code'),
    'DO 0',
    'ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) NULL AFTER total_amount'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'coupon_id'),
    'DO 0',
    'ALTER TABLE orders ADD COLUMN coupon_id INT NULL AFTER coupon_code'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'discount_amount'),
    'DO 0',
    'ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER coupon_id'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'final_amount'),
    'DO 0',
    'ALTER TABLE orders ADD COLUMN final_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER discount_amount'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
