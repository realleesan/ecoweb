# Hướng dẫn Bản đồ Phủ xanh

## ✅ Đã cập nhật

### 1. **API mới** (`api/all-planted-trees.php`)
- Lấy tất cả cây đã trồng của mọi người
- Bao gồm thông tin: Tên cây, vị trí, người trồng, ngày trồng, mã đơn hàng

### 2. **Bản đồ phủ xanh** (`index.php`)
- Tự động load và hiển thị **TẤT CẢ** cây đã trồng khi vào trang
- Icon cây xanh 🌱 cho mỗi cây
- Click vào icon → Hiển thị popup với thông tin chi tiết

## 🎯 Tính năng

### Hiển thị trên map:
- **Pin đỏ** 📍: Các mẫu đất (lands)
- **Icon cây xanh** 🌱: Tất cả cây đã trồng của mọi người

### Thông tin popup khi click vào cây:
- 🌳 Tên cây
- 📍 Mẫu đất
- 📊 Vị trí ô [row, col]
- 👤 Người trồng
- 🔖 Mã đơn hàng
- 📅 Ngày trồng
- 🌱 Tình trạng: Khỏe mạnh

### Luồng hoạt động:
1. Vào trang chủ → Bản đồ phủ xanh
2. Tự động hiển thị tất cả cây đã trồng
3. Click vào pin đỏ → Zoom vào mẫu đất
4. Click vào icon cây → Xem thông tin chi tiết
5. Thể hiện sứ mệnh: **Phủ xanh Trái Đất** 🌍

## 📊 Thống kê

Bản đồ sẽ hiển thị:
- Tổng số cây đã trồng của tất cả người dùng
- Phân bố cây trên các mẫu đất khác nhau
- Tiến độ phủ xanh theo thời gian

## 🌟 Ý nghĩa

- **Minh bạch**: Mọi người đều thấy được cây đã trồng
- **Động lực**: Thấy được đóng góp của cộng đồng
- **Sứ mệnh**: Thể hiện rõ mục tiêu phủ xanh Trái Đất
- **Cộng đồng**: Kết nối những người yêu môi trường

## 🔄 Cập nhật tự động

- Mỗi khi có người trồng cây mới → Tự động hiển thị trên map
- Không cần refresh thủ công
- Dữ liệu realtime từ database

## 🎨 Giao diện

- Icon cây xanh đẹp mắt
- Popup thông tin chi tiết
- Dễ dàng phân biệt giữa mẫu đất và cây trồng
- Responsive trên mọi thiết bị
