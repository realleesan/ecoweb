-- User address book table
CREATE TABLE IF NOT EXISTS user_addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    ward VARCHAR(150) NOT NULL,
    city VARCHAR(150) NOT NULL,
    address_type ENUM('home', 'office', 'school') DEFAULT 'home',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_addresses_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_addresses_user (user_id),
    INDEX idx_user_addresses_default (user_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
