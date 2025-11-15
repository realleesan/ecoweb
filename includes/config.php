<?php

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecoweb');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// SITE INFORMATION
// ============================================
define('SITE_NAME', 'GROWHOPE');
define('SITE_TAGLINE', 'Trồng cây gây rừng');
define('BRAND_NAME', 'GROWHOPE');

// ============================================
// CONTACT INFORMATION
// ============================================
define('CONTACT_PHONE', '0123 456 789');
define('CONTACT_HOTLINE', '0123 456 789');
define('CONTACT_EMAIL', 'info@growhope.vn');
define('CONTACT_EMAIL_SUPPORT', 'support@growhope.vn');
define('CONTACT_EMAIL_PARTNER', 'partner@growhope.vn');
define('CONTACT_ADDRESS', '123 Đường Số 1, Phường 2, Quận 3, TP.HCM');
define('CONTACT_ADDRESS_BRANCH', '456 Đường ABC, Phường XYZ, Quận 1, TP.HCM');
define('CONTACT_WORKING_HOURS', 'Thứ 2 - Thứ 6: 8:00 - 17:30, Thứ 7: 8:00 - 12:00');

// ============================================
// SOCIAL MEDIA LINKS
// ============================================
define('SOCIAL_FACEBOOK', 'https://facebook.com/growhope');
define('SOCIAL_INSTAGRAM', 'https://instagram.com/growhope');
define('SOCIAL_ZALO', '0123456789');

// ============================================
// DESIGN SYSTEM - COLORS
// ============================================
define('COLOR_PRIMARY', '#3C603C');
define('COLOR_SECONDARY', '#D26426');
define('COLOR_DARK', '#74493D');
define('COLOR_LIGHT', '#FFF7ED');
define('COLOR_WHITE', '#FFFFFF');
define('COLOR_BG_GREEN', '#9FBD48');

// ============================================
// DESIGN SYSTEM - TYPOGRAPHY
// ============================================
define('FONT_FAMILY', 'Poppins');
define('FONT_WEIGHTS', '300,400,500,600,700');
define('FONT_GOOGLE_URL', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

// ============================================
// LAYOUT CONFIGURATION
// ============================================
define('CONTAINER_MAX_WIDTH', '1200px');
define('CONTAINER_MAX_WIDTH_SMALL', '900px');
define('CONTAINER_MAX_WIDTH_XSMALL', '700px');
define('CONTAINER_PADDING', '5%');
define('CONTAINER_PADDING_LARGE', '60px 5%');
define('CONTAINER_PADDING_MEDIUM', '40px 5%');
define('GRID_GAP', '30px');
define('GRID_GAP_SMALL', '20px');

// ============================================
// RESPONSIVE BREAKPOINTS
// ============================================
define('BREAKPOINT_XL', '1200px');
define('BREAKPOINT_LG', '992px');
define('BREAKPOINT_MD', '768px');
define('BREAKPOINT_SM', '576px');
define('BREAKPOINT_XS', '480px');

// ============================================
// PAGINATION SETTINGS
// ============================================
define('PAGINATION_PRODUCTS_PER_PAGE', 16);
define('PAGINATION_CATEGORIES_PER_PAGE', 12);
define('PAGINATION_NEWS_PER_PAGE', 8);
define('PAGINATION_GALLERY_PER_PAGE', 16);

// ============================================
// PATHS & URLs
// ============================================
define('BASE_URL', '/ecoweb');
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('ASSETS_URL', BASE_URL . '/assets');

// ============================================
// DATE & TIME SETTINGS
// ============================================
define('TIMEZONE', 'Asia/Ho_Chi_Minh');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');

// Set timezone
date_default_timezone_set(TIMEZONE);

// ============================================
// EMAIL CONFIGURATION (PHPMailer)
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'baominhkpkp@gmail.com');
define('SMTP_PASSWORD', 'gjvz qdrq pogq sheb'); // Mật khẩu ứng dụng sẽ được cung cấp sau
define('SMTP_FROM_EMAIL', 'baominhkpkp@gmail.com');
define('SMTP_FROM_NAME', 'GROWHOPE');
define('SMTP_SECURE', 'tls'); // 'tls' hoặc 'ssl'
define('SMTP_CHARSET', 'UTF-8');

// Email nhận tin nhắn liên hệ từ form (email admin thực tế)
define('CONTACT_EMAIL_RECEIVE', 'baominhkpkp@gmail.com'); // Email thực tế để nhận tin nhắn liên hệ

