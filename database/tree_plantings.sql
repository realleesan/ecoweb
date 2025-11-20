-- Bảng lưu thông tin cây đã trồng sau khi thanh toán
CREATE TABLE IF NOT EXISTS `tree_plantings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `order_item_id` INT NOT NULL,
  `land_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `grid_row` INT NOT NULL,
  `grid_col` INT NOT NULL,
  `center_lat` DOUBLE NOT NULL,
  `center_lng` DOUBLE NOT NULL,
  `planted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`item_id`) ON DELETE CASCADE,
  FOREIGN KEY (`land_id`) REFERENCES `lands`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE RESTRICT,
  UNIQUE KEY `unique_grid_cell` (`land_id`, `grid_row`, `grid_col`),
  INDEX `idx_order` (`order_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_land` (`land_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lưu vị trí cây đã trồng trên lưới';
