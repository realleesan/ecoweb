-- Create ECOWEB database (if not exists)
CREATE DATABASE IF NOT EXISTS `ecoweb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ecoweb`;


-- Create users table compatible with auth/auth.php (if not exists)
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Seed default admin account (only if not exists)
-- Bcrypt password for 'admin123' generated via PHP password_hash
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`)
SELECT 'admin123', 'admin@example.com', '$2y$10$vwn1N4d7y5pj59IogNUQfe9ndzzfIquZTkDB5NXQGJPYcOLIKvmOq', 'Administrator', 'admin', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `users` WHERE `username` = 'admin123'
);


-- Ensure existing admin account has correct hashed password
UPDATE `users`
SET `password` = '$2y$10$vwn1N4d7y5pj59IogNUQfe9ndzzfIquZTkDB5NXQGJPYcOLIKvmOq'
WHERE `username` = 'admin123' AND `role` = 'admin';

