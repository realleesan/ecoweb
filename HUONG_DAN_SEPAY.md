# 🔧 HƯỚNG DẪN TÍCH HỢP SEPAY

## ✅ Thông tin đã cấu hình

**Mã đơn vị:** SP-TEST-NHB36596  
**Secret Key:** spsk_test_GhirZka7wTrNcoKQBvAGH4DUCCsJgkdD  
**API Endpoint:** https://my.sepay.vn/userapi/transactions/create  
**Ngrok URL:** https://paradelike-inge-unlitigating.ngrok-free.dev

## ⚠️ LƯU Ý QUAN TRỌNG

**BASE_URL phải là `/ecoweb` (chỉ path), KHÔNG phải full URL:**
```php
// ✅ ĐÚNG
define('BASE_URL', '/ecoweb');

// ❌ SAI
define('BASE_URL', 'https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb');
```

**Lý do:** 
- Khi truy cập qua localhost: `http://localhost/ecoweb`
- Khi truy cập qua ngrok: `https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb`
- Code sẽ tự động detect và dùng đúng domain

---

## 📋 Bước 1: Cập nhật Database

Chạy file SQL trong phpMyAdmin:
```
database/SETUP_PAYMENT_COMPLETE.sql
```

---

## 📋 Bước 2: Tạo thư mục logs

```cmd
cd C:\xampp\htdocs\ecoweb
mkdir logs
```

---

## 📋 Bước 3: Chạy ngrok

```cmd
ngrok http 80
```

Nếu URL ngrok thay đổi, cập nhật trong `includes/config.php`:
```php
define('NGROK_URL', 'URL_NGROK_MOI_CUA_BAN');
```

---

## 📋 Bước 4: Cấu hình Webhook trên SePay

Đăng nhập: https://my.sepay.vn

Vào **Cài đặt** → **Webhook/IPN**, thêm URL:
```
https://paradelike-inge-unlitigating.ngrok-free.dev/ecoweb/api/ipn_sepay.php
```

---

## 🧪 Test thanh toán

1. Truy cập: http://localhost/ecoweb
2. Đăng nhập và thêm sản phẩm vào giỏ
3. Checkout và đặt hàng
4. Hệ thống sẽ tạo giao dịch trên SePay
5. Chuyển khoản theo thông tin SePay cung cấp
6. SePay gọi webhook về server
7. Đơn hàng tự động cập nhật trạng thái "paid"

---

## 🔍 Kiểm tra

- **Log:** `logs/sepay_ipn.log`
- **Database:** Bảng `orders` → kiểm tra `status` = `paid`
- **Ngrok Inspector:** http://127.0.0.1:4040
