-- SQL migration: create sites and plantings tables (run in phpMyAdmin or CLI)
CREATE TABLE IF NOT EXISTS `sites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `center_lat` DOUBLE NOT NULL,
  `center_lng` DOUBLE NOT NULL,
  `bbox_lat1` DOUBLE DEFAULT NULL,
  `bbox_lng1` DOUBLE DEFAULT NULL,
  `bbox_lat2` DOUBLE DEFAULT NULL,
  `bbox_lng2` DOUBLE DEFAULT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `plantings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `lat` DOUBLE NOT NULL,
  `lng` DOUBLE NOT NULL,
  `planted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`site_id`) REFERENCES `sites`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data (3 sites, a few plantings) - adjust product_id and user_id to match your DB
INSERT INTO `sites` (`name`, `center_lat`, `center_lng`, `bbox_lat1`, `bbox_lng1`, `bbox_lat2`, `bbox_lng2`, `description`)
VALUES
('Mẫu đất 1', 10.762622, 106.660172, 10.757, 106.654, 10.768, 106.668, 'Mẫu thử tại TP.HCM'),
('Mẫu đất 2', 21.027764, 105.834160, 21.023, 105.829, 21.033, 105.839, 'Mẫu thử tại Hà Nội'),
('Mẫu đất 3', 16.054407, 108.202167, 16.049, 108.198, 16.059, 108.206, 'Mẫu thử tại Đà Nẵng');

-- Note: change product_id and user_id to match existing rows in products/users table
INSERT INTO `plantings` (`site_id`, `product_id`, `user_id`, `lat`, `lng`, `planted_at`)
VALUES
(1, 1, 1, 10.7635, 106.6610, NOW()),
(1, 2, 1, 10.7640, 106.6620, NOW()),
(2, 3, 1, 21.0285, 105.8348, NOW()),
(3, 1, 1, 16.0550, 108.2030, NOW());


