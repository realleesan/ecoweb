<?php
// File: payment/callback.php
// Trạm trung chuyển - Nhận callback từ SePay và chuyển về payment.php

require_once __DIR__ . '/../includes/config.php';

// Lấy thông tin từ SePay trả về
$orderCode = $_GET['order_code'] ?? '';
$status = $_GET['status'] ?? ''; // SePay trả về status=paid hoặc cancel

if ($orderCode) {
    // Chuyển hướng về trang chi tiết thanh toán để khách xem kết quả
    header('Location: ' . BASE_URL . '/payment/payment.php?order_code=' . urlencode($orderCode) . '&check_status=1');
    exit;
} else {
    // Nếu không có mã đơn, về trang chủ
    header('Location: ' . BASE_URL);
    exit;
}
