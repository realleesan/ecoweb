-- ============================================
-- SETUP COMPLETE PAYMENT SYSTEM
-- Chạy file này trong phpMyAdmin để cập nhật database
-- ============================================

-- Thêm các cột cần thiết cho bảng orders
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `coupon_code` VARCHAR(50) NULL AFTER `total_amount`,
ADD COLUMN IF NOT EXISTS `coupon_id` INT NULL AFTER `coupon_code`,
ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `coupon_id`,
ADD COLUMN IF NOT EXISTS `final_amount` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `discount_amount`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Cập nhật final_amount cho các đơn hàng cũ (nếu có)
UPDATE `orders` 
SET `final_amount` = `total_amount` 
WHERE `final_amount` = 0 OR `final_amount` IS NULL;

-- Tạo bảng coupon_redemptions
CREATE TABLE IF NOT EXISTS `coupon_redemptions` (
    `redemption_id` INT AUTO_INCREMENT PRIMARY KEY,
    `coupon_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `order_id` INT NULL,
    `redeemed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_coupon_user_order` (`coupon_id`, `user_id`, `order_id`),
    INDEX `idx_coupon_user` (`coupon_id`, `user_id`),
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`coupon_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tạo bảng payment_transactions (lưu lịch sử giao dịch)
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `transaction_id` VARCHAR(100) PRIMARY KEY,
    `order_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'sepay',
    `status` ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_order` (`order_id`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hiển thị kết quả
SELECT 'Setup completed successfully!' AS status;
SELECT COUNT(*) AS total_orders FROM `orders`;
SELECT COUNT(*) AS total_transactions FROM `payment_transactions`;
