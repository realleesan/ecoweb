````markdown
# 🌿 EcoWeb – PHP Website Project

Dự án **EcoWeb** là website thương mại điện tử đơn giản viết bằng **PHP + CSS + JS + SQL (MariaDB)**, phục vụ mục tiêu học tập và mô phỏng hoạt động mua hàng, thanh toán và tương tác bản đồ (Map).  
Website hoạt động trên **XAMPP (localhost)**, cơ sở dữ liệu được quản lý bằng **PhpMyAdmin**.

---

## ⚙️ Công nghệ và môi trường

- **Ngôn ngữ:** PHP, HTML, CSS, JavaScript  
- **Cơ sở dữ liệu:** MariaDB (file `database.sql`)  
- **Máy chủ:** XAMPP (Apache + MySQL)  
- **IDE khuyến nghị:** VSCode / Cursor / Windsurf  
- **Thư viện hỗ trợ:** [PHPMailer](https://github.com/PHPMailer/PHPMailer) (gửi mail tự động)  
- **Công cụ bổ trợ:** ChatGPT, Wayback Machine, GoFullPage, F12 DevTools  
- **Quản lý mã nguồn:** GitHub  
- **Chạy thử & demo:** Ngrok

---

## 📁 Cấu trúc thư mục

```bash
ecoweb/
│   .htaccess
│   index.php
│   readme.md
│
├───admin
│   │   categories.php
│   │   dashboard.php
│   │   galleries.php
│   │   index.php
│   │   menu.php
│   │   news.php
│   │   orders.php
│   │   products.php
│   │   report.php
│   │   tags.php
│   │   users.php
│   │
│   └───assets
│       ├───css
│       ├───img
│       └───js
│
├───assets
│   ├───css
│   ├───img
│   └───js
│
├───auth
│       account.php
│       auth.php
│       forgot-password.php
│       login.php
│       logout.php
│       register.php
│
├───database
│       database.sql
│
├───error
│       404.php
│       500.php
│
├───includes
│   │   config.php
│   │   database.php
│   │   footer.php
│   │   header.php
│   │
│   └───components
│
├───library
│   └───phpmailer
│       ├───language
│       └───src
│
├───payment
│       checkout.php
│       map.php
│       payment.php
│
├───public
│       about.php
│       categories.php
│       contact.php
│       galleries.php
│       news.php
│       products.php
│       search.php
│
└───views
        news-detail.php
        products-detail.php
````

---

## 🧩 Mô tả các thành phần chính

### 🔸 Gốc dự án (`index.php`, `.htaccess`, `readme.md`)

* **index.php** – Trang chủ website, là điểm khởi đầu khi truy cập.
* **.htaccess** – Cấu hình rewrite URL, điều hướng và bảo mật.
* **readme.md** – Tài liệu mô tả dự án, cấu trúc, cách cài đặt.

---

### 🔸 `/admin/`

* Chứa toàn bộ giao diện và chức năng dành cho **quản trị viên** (Admin Dashboard).
* Bao gồm các trang:

  * `dashboard.php`: Trang tổng quan thống kê.
  * `products.php`, `orders.php`, `users.php`: Quản lý sản phẩm, đơn hàng, người dùng.
  * `news.php`, `galleries.php`, `tags.php`: Quản lý tin tức, hình ảnh, tag.
  * `report.php`: Xuất báo cáo.
  * `menu.php`: Quản lý menu điều hướng.
* Có thư mục con `/assets/` chứa CSS, JS, hình ảnh riêng cho giao diện admin.

---

### 🔸 `/assets/`

* Chứa **toàn bộ tài nguyên dùng chung** cho website người dùng:

  * `/css` – file style, theme.
  * `/js` – script xử lý giao diện, hiệu ứng.
  * `/img` – hình ảnh, biểu tượng.

---

### 🔸 `/auth/`

* Chứa các trang xử lý **xác thực tài khoản**:

  * `login.php`, `register.php`: đăng nhập & đăng ký.
  * `forgot-password.php`: khôi phục mật khẩu.
  * `logout.php`: đăng xuất.
  * `account.php`: quản lý thông tin người dùng.
  * `auth.php`: xử lý logic đăng nhập / kiểm tra quyền truy cập.

---

### 🔸 `/database/`

* Chứa file **`database.sql`** – mô tả cấu trúc cơ sở dữ liệu của dự án (bảng, khóa, dữ liệu mẫu).

---

### 🔸 `/error/`

* Trang hiển thị lỗi người dùng:

  * `404.php` – Trang không tồn tại.
  * `500.php` – Lỗi máy chủ hoặc truy vấn.

---

### 🔸 `/includes/`

* Chứa các file **tái sử dụng chung** cho toàn hệ thống:

  * `config.php` – cấu hình server, kết nối DB.
  * `database.php` – script kết nối và truy vấn SQL.
  * `header.php`, `footer.php` – thành phần giao diện chung.
* `/components/`: có thể chứa các module HTML/PHP nhỏ như banner, menu, form, giúp tái sử dụng nhanh.

---

### 🔸 `/library/`

* Thư mục chứa **thư viện bên thứ ba**, ví dụ:

  * `/phpmailer`: gửi email xác nhận, thông báo đơn hàng, khôi phục mật khẩu.
  * Có sẵn mã nguồn gốc và ngôn ngữ hỗ trợ quốc tế.

---

### 🔸 `/payment/`

* Các trang và logic xử lý **thanh toán và bản đồ**:

  * `checkout.php`: xác nhận giỏ hàng và đặt hàng.
  * `payment.php`: xử lý thanh toán qua cổng **Sepay**.
  * `map.php`: hiển thị bản đồ để người dùng chọn vị trí trồng cây.

---

### 🔸 `/public/`

* Chứa các trang **người dùng truy cập chính**:

  * `about.php`, `contact.php`, `galleries.php`: thông tin giới thiệu, liên hệ.
  * `products.php`, `categories.php`: danh mục và danh sách sản phẩm.
  * `news.php`: danh sách bài viết.
  * `search.php`: tìm kiếm nội dung trong web.

---

### 🔸 `/views/`

* Các **trang chi tiết** hiển thị nội dung cụ thể:

  * `news-detail.php`: chi tiết tin tức.
  * `products-detail.php`: chi tiết sản phẩm.

---

## 🚀 Cách khởi chạy

1. Cài đặt XAMPP → bật **Apache** và **MySQL**.
2. Copy thư mục `ecoweb` vào `C:\xampp\htdocs\`.
3. Mở [http://localhost/phpmyadmin](http://localhost/phpmyadmin), import file `database/database.sql`.
4. Truy cập website tại: [http://localhost/ecoweb/](http://localhost/ecoweb/).

---

## 👨‍💻 Nhóm phát triển

* **Nhóm trưởng:** Nhật
* **Thành viên:** 5 người – cùng thực hiện cả frontend và backend.
* **Phân công:** Mỗi người phụ trách 1 module, commit và review theo Sprint.
* **Phương pháp:** Agile – phát triển nhiều sprint song song.

---

## 📄 Ghi chú

* Dự án phục vụ **mục đích học tập** – không triển khai thương mại.
* Có thể mở rộng thêm chức năng **quản trị sản phẩm, đơn hàng, bản đồ trồng cây, thanh toán tự động**.
* Code được viết và kiểm thử trên môi trường **local (XAMPP)**.
