# 🔄 QUY TRÌNH THANH TOÁN HOÀN CHỈNH

## ✅ Đã sửa lỗi và hoàn thiện

Lỗi "There is no active transaction" đã được sửa.

---

## 📋 QUY TRÌNH TỪNG BƯỚC

### 1️⃣ Đặt hàng (checkout.php)
- User chọn sản phẩm, địa chỉ giao hàng
- Nhập mã giảm giá (optional)
- Click "Đặt hàng ngay"

### 2️⃣ Tạo đơn hàng (api/create-order.php)
- Validate địa chỉ và giỏ hàng
- Tính toán tổng tiền, giảm giá
- Tạo order_code (VD: HBN20241120ABC123)
- Lưu vào database với status = 'pending'
- Xóa giỏ hàng
- Trả về: `{success: true, order_code: "...", amount: 100000}`

### 3️⃣ Hiển thị thông tin thanh toán (payment/payment.php)
- Hiển thị thông tin đơn hàng
- Hiển thị thông tin chuyển khoản SePay:
  - Tài khoản: SP-TEST-NHB36596
  - Nội dung: HBN20241120ABC123
  - Số tiền: 100,000 đ
- Có nút "Copy" để copy nhanh
- Có nút "Kiểm tra thanh toán"

### 4️⃣ User chuyển khoản
- User mở app ngân hàng
- Chuyển khoản đến tài khoản SePay
- Nhập đúng nội dung: order_code

### 5️⃣ SePay nhận tiền và gọi webhook (api/ipn_sepay.php)
- SePay phát hiện có tiền chuyển vào
- Đọc nội dung chuyển khoản (order_code)
- Gọi webhook: `https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/api/ipn_sepay.php`
- Gửi data: `{order_code: "...", transferAmount: 100000, ...}`

### 6️⃣ Server xử lý webhook (api/ipn_sepay.php)
- Nhận data từ SePay
- Validate order_code và số tiền
- Cập nhật database: status = 'paid'
- Lưu log vào `logs/sepay_ipn.log`
- Trả về: `{success: true}`

### 7️⃣ User kiểm tra thanh toán
- Click "Kiểm tra thanh toán" trên payment.php
- Trang reload và kiểm tra status mới
- Nếu status = 'paid' → Hiển thị nút "Xem bản đồ trồng cây"

### 8️⃣ Chuyển sang map (payment/map.php)
- Click "Xem bản đồ trồng cây"
- Hiển thị thông tin đơn hàng đã thanh toán
- Hiển thị bản đồ vị trí trồng cây (placeholder)

---

## 🗂️ CÁC FILE QUAN TRỌNG

### Backend:
- `api/create-order.php` - Tạo đơn hàng
- `api/ipn_sepay.php` - Xử lý webhook từ SePay
- `includes/sepay_config.php` - Cấu hình SePay

### Frontend:
- `payment/checkout.php` - Trang đặt hàng
- `payment/payment.php` - Trang hiển thị thông tin thanh toán
- `payment/map.php` - Trang bản đồ (sau khi thanh toán)

### Database:
- Bảng `orders` - Lưu đơn hàng
- Bảng `order_items` - Lưu chi tiết sản phẩm
- Bảng `payment_transactions` - Lưu lịch sử giao dịch (optional)

---

## 🧪 CÁCH TEST

### Test 1: Tạo đơn hàng
1. Truy cập: `https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb`
2. Đăng nhập
3. Thêm sản phẩm vào giỏ
4. Checkout và đặt hàng
5. Kiểm tra: Có redirect đến payment.php không?

### Test 2: Kiểm tra database
1. Vào phpMyAdmin → bảng `orders`
2. Tìm đơn hàng vừa tạo
3. Kiểm tra:
   - order_code: HBN20241120...
   - status: pending
   - final_amount: có giá trị đúng

### Test 3: Test webhook (Giả lập)
Dùng Postman hoặc curl:

```bash
curl -X POST https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/api/ipn_sepay.php \
-H "Content-Type: application/json" \
-d '{"order_code":"HBN20241120ABC123","transferAmount":100000,"transaction_id":"TEST123","status":"success"}'
```

Thay `HBN20241120ABC123` bằng order_code thật.

### Test 4: Kiểm tra thanh toán
1. Sau khi gọi webhook
2. Vào payment.php
3. Click "Kiểm tra thanh toán"
4. Kiểm tra: Có hiện nút "Xem bản đồ trồng cây" không?

### Test 5: Xem map
1. Click "Xem bản đồ trồng cây"
2. Kiểm tra: Có hiển thị trang map không?

---

## 🔍 DEBUG

### Kiểm tra log
```
C:\xampp\htdocs\ecoweb\logs\sepay_ipn.log
```

### Kiểm tra ngrok
```
http://127.0.0.1:4040
```

### Kiểm tra database
```sql
SELECT * FROM orders ORDER BY created_at DESC LIMIT 10;
```

---

## 📞 CẤU HÌNH WEBHOOK TRÊN SEPAY

Đăng nhập: https://my.sepay.vn

Vào **Cài đặt** → **Webhook**, thêm URL:
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/api/ipn_sepay.php
```

---

## ✅ CHECKLIST

- [ ] Database đã cập nhật (chạy SETUP_PAYMENT_COMPLETE.sql)
- [ ] Thư mục logs đã tạo
- [ ] Ngrok đang chạy
- [ ] NGROK_URL trong config.php đúng
- [ ] SEPAY_MERCHANT_ID và SEPAY_SECRET_KEY đúng
- [ ] Webhook đã cấu hình trên SePay
- [ ] Test tạo đơn hàng thành công
- [ ] Test webhook thành công
- [ ] Test xem map thành công

---

**🎉 Hoàn tất! Hệ thống thanh toán đã sẵn sàng!**
