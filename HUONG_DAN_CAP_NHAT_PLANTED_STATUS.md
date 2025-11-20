# Hướng dẫn cập nhật trạng thái "Đã trồng cây"

## Bước 1: Chạy SQL Migration

Mở **phpMyAdmin** và chạy file SQL sau:

```sql
-- File: database/add_planted_status.sql

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
```

## Bước 2: Kiểm tra kết quả

Sau khi chạy SQL, hệ thống sẽ:

### ✅ Trạng thái đơn hàng mới:
- **pending**: Chờ thanh toán
- **paid**: Đã thanh toán (chưa trồng cây)
- **planted**: Đã trồng cây ✨ (MỚI)
- **cancelled**: Đã hủy

### ✅ Luồng hoạt động:
1. Đặt hàng → `pending`
2. Thanh toán → `paid`
3. Trồng cây → `planted` ✨
4. Không thể trồng lại

### ✅ Bảo vệ:
- Đơn hàng `planted` không thể vào `payment.php`
- Đơn hàng `planted` không thể vào `map.php`
- Tự động chuyển về trang "Cây của tôi"

### ✅ Hiển thị trong trang đơn hàng:
- **Đã trồng cây**: Nút "Xem cây đã trồng" → `my-trees.php`
- **Đã thanh toán**: Nút "Trồng cây ngay" → `map.php`
- **Chờ thanh toán**: Nút "Thanh toán ngay" → `payment.php`

## Bước 3: Test

1. Tạo đơn hàng mới
2. Thanh toán → Status = `paid`
3. Trồng cây → Status = `planted`
4. Thử vào lại `map.php` → Tự động chuyển về `my-trees.php`
5. Kiểm tra trang đơn hàng → Hiển thị "🌳 Đã trồng cây"

## Lưu ý

- Các đơn hàng cũ đã có cây sẽ tự động được cập nhật thành `planted`
- Không thể trồng cây 2 lần cho cùng 1 đơn hàng
- Mỗi ô lưới chỉ có thể trồng 1 cây (unique constraint)
