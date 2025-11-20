-- Migration: partners and lands tables
CREATE TABLE IF NOT EXISTS `partners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `dob` DATE DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `document_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `partner_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `area` DECIMAL(12,2) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `images_json` JSON DEFAULT NULL,
  `polygon_geojson` JSON DEFAULT NULL,
  `grid_json` JSON DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note: run this migration via phpMyAdmin or MySQL CLI

