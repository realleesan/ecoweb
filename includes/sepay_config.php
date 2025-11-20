<?php
/**
 * SePay Payment Gateway Configuration
 * Cấu hình thanh toán SePay
 */

// Thông tin từ SePay Dashboard
define('SEPAY_MERCHANT_ID', 'SP-TEST-NHB36596');
define('SEPAY_SECRET_KEY', 'spsk_test_GhirZka7wTrNcoKQBvAGH4DUCCsJgkdD');

// Thông tin ngân hàng để tạo QR code
define('SEPAY_BANK_CODE', '970422');        // Mã ngân hàng (VD: 970422 = MB Bank)
define('SEPAY_ACCOUNT_NUMBER', '0389654785'); // Số tài khoản nhận tiền
define('SEPAY_ACCOUNT_NAME', 'NGUYEN VAN HOANG NAM');  // Tên tài khoản
