-- Migration: Add updated_at column to orders table
-- Thêm cột updated_at để theo dõi thời gian cập nhật đơn hàng

SET @db_name := DATABASE();

-- Thêm cột updated_at nếu chưa tồn tại
SET @sql := (SELECT IF(
    EXISTS (
        SELECT 1 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = @db_name 
        AND TABLE_NAME = 'orders' 
        AND COLUMN_NAME = 'updated_at'
    ),
    'DO 0',
    'ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
