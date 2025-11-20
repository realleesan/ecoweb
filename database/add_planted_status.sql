-- Thêm trạng thái 'planted' vào bảng orders
ALTER TABLE `orders` 
MODIFY COLUMN `status` ENUM('pending','paid','planted','cancelled') NOT NULL DEFAULT 'pending';

-- Cập nhật các đơn hàng đã có cây trồng thành status = 'planted'
UPDATE `orders` o
SET o.status = 'planted'
WHERE o.status = 'paid' 
AND EXISTS (
    SELECT 1 FROM tree_plantings tp WHERE tp.order_id = o.order_id
);
