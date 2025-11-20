-- Coupons table schema for admin module
DROP TABLE IF EXISTS coupons;
CREATE TABLE coupons (
    coupon_id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_code VARCHAR(50) NOT NULL UNIQUE,
    coupon_name VARCHAR(150) NOT NULL,
    description TEXT,
    discount_type ENUM('percent', 'amount') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_discount_value DECIMAL(10,2) DEFAULT NULL,
    min_order_value DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT NOT NULL DEFAULT 0,
    per_customer_limit INT DEFAULT NULL,
    status ENUM('draft', 'active', 'inactive', 'scheduled', 'expired') NOT NULL DEFAULT 'draft',
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed sample coupons
INSERT INTO coupons (coupon_code, coupon_name, description, discount_type, discount_value, max_discount_value, min_order_value, usage_limit, per_customer_limit, status, start_date, end_date)
VALUES
('GREEN10', 'Giảm 10% toàn bộ sản phẩm', 'Áp dụng cho mọi đơn hàng, tối đa 100.000đ.', 'percent', 10, 100000, 200000, 500, 1, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('FREESHIP', 'Miễn phí vận chuyển', 'Miễn phí vận chuyển cho đơn hàng từ 300.000đ.', 'amount', 30000, NULL, 300000, NULL, NULL, 'scheduled', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 32 DAY)),
('WELCOME50K', 'Tặng 50.000đ cho khách mới', 'Áp dụng cho khách hàng mới thanh toán lần đầu.', 'amount', 50000, NULL, 400000, 100, 1, 'draft', NULL, NULL);
