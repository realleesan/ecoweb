# 🌐 TRUY CẬP QUA NGROK

## ✅ Cấu hình hiện tại

**Ngrok URL:** https://paradelike-inge-unlitigating.ngrok-free.dev  
**BASE_URL:** /ecoweb  
**Full URL:** https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb

---

## 🚀 CÁCH TRUY CẬP

### Bước 1: Đảm bảo ngrok đang chạy

Mở Command Prompt và chạy:
```cmd
ngrok http 80
```

Kiểm tra URL hiển thị có đúng là:
```
https://paradelike-inge-unlitigating.ngrok-free.dev
```

### Bước 2: Đảm bảo XAMPP đang chạy

- Apache: Running ✅
- MySQL: Running ✅

### Bước 3: Truy cập qua ngrok

Mở trình duyệt và truy cập:
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb
```

**Lưu ý:** Lần đầu truy cập ngrok có thể hiện trang cảnh báo, click "Visit Site" để tiếp tục.

---

## 📋 CÁC TRANG QUAN TRỌNG

### Trang chủ:
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb
```

### Đăng nhập:
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/auth/login.php
```

### Sản phẩm:
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/public/products.php
```

### Checkout:
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/payment/checkout.php
```

---

## 🔧 CÁCH HOẠT ĐỘNG

Khi bạn truy cập qua ngrok:

1. **Request từ trình duyệt:**
   ```
   Browser → https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb
   ```

2. **Ngrok forward về localhost:**
   ```
   Ngrok → http://localhost:80/ecoweb
   ```

3. **Apache xử lý:**
   ```
   Apache → C:\xampp\htdocs\ecoweb
   ```

4. **Code tự động detect:**
   - Phát hiện header `X-Forwarded-Host` từ ngrok
   - Tự động dùng NGROK_URL để tạo callback URL
   - SePay sẽ gọi webhook về ngrok URL

---

## ✅ KIỂM TRA CẤU HÌNH

### File: `includes/config.php`
```php
define('NGROK_URL', 'https://paradelike-inge-unlitigating.ngrok-free.dev');
define('BASE_URL', '/ecoweb');
```

### File: `includes/sepay_config.php`
```php
define('SEPAY_MERCHANT_ID', 'SP-TEST-NHB36596');
define('SEPAY_SECRET_KEY', 'spsk_test_GhirZka7wTrNcoKQBvAGH4DUCCsJgkdD');
define('SEPAY_ENDPOINT_URL', 'https://my.sepay.vn/userapi/transactions/create');
```

---

## 🧪 TEST THANH TOÁN QUA NGROK

### Bước 1: Truy cập và đăng nhập
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/auth/login.php
```

### Bước 2: Thêm sản phẩm vào giỏ
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/public/products.php
```

### Bước 3: Checkout
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/payment/checkout.php
```

### Bước 4: Đặt hàng
- Chọn địa chỉ giao hàng
- Click "Đặt hàng ngay"
- Hệ thống sẽ gọi API SePay
- SePay trả về thông tin chuyển khoản

### Bước 5: Chuyển khoản
- Chuyển khoản theo thông tin SePay cung cấp
- SePay nhận tiền và gọi webhook về:
  ```
  https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/api/ipn_sepay.php
  ```

### Bước 6: Kiểm tra
- Xem log: `logs/sepay_ipn.log`
- Kiểm tra database: bảng `orders` → `status` = `paid`
- Xem ngrok inspector: `http://127.0.0.1:4040`

---

## 🔍 DEBUG

### Xem request real-time

Mở ngrok web interface:
```
http://127.0.0.1:4040
```

Bạn sẽ thấy tất cả request đến ngrok, bao gồm:
- Request từ trình duyệt
- Webhook từ SePay
- Response từ server

### Kiểm tra log

```
C:\xampp\htdocs\ecoweb\logs\sepay_ipn.log
```

---

## ⚠️ LƯU Ý

### 1. Ngrok free có giới hạn
- Mỗi session chỉ tồn tại 2 giờ
- URL sẽ thay đổi khi restart
- Có thể bị rate limit

### 2. Khi ngrok restart
Nếu URL ngrok thay đổi, cập nhật:

**File `includes/config.php`:**
```php
define('NGROK_URL', 'URL_NGROK_MOI');
```

**SePay Dashboard:**
- Cập nhật webhook URL mới

### 3. Trang cảnh báo ngrok
Lần đầu truy cập có thể thấy:
```
You are about to visit: paradelike-inge-unlitigating.ngrok-free.dev
```
Click "Visit Site" để tiếp tục.

---

## 🎉 HOÀN TẤT

Bây giờ bạn có thể:
- ✅ Truy cập website qua ngrok
- ✅ Test thanh toán SePay thật
- ✅ Nhận webhook từ SePay
- ✅ Debug real-time qua ngrok inspector

**Chúc bạn thành công!** 🚀
