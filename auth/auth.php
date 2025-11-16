<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT user_id, username, email, full_name, phone, address, role FROM users WHERE user_id = :user_id AND is_active = 1');
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Login user
 */
function loginUser($usernameOrEmail, $password) {
    try {
        $pdo = getPDO();
        
        // Find user by username or email
        $stmt = $pdo->prepare('SELECT user_id, username, email, password, full_name, role, is_active FROM users WHERE (username = :identifier OR email = :identifier)');
        $stmt->execute(['identifier' => $usernameOrEmail]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Tên đăng nhập hoặc email không tồn tại'];
        }
        
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Tài khoản đã bị khóa'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu không chính xác'];
        }
        
        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        return ['success' => true, 'message' => 'Đăng nhập thành công'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
    }
}

/**
 * Register new user
 */
function registerUser($username, $email, $password, $fullName = '', $phone = '') {
    try {
        $pdo = getPDO();
        
        // Check if username already exists
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email đã được sử dụng'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, full_name, phone) VALUES (:username, :email, :password, :full_name, :phone)');
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'full_name' => $fullName,
            'phone' => $phone
        ]);
        
        return ['success' => true, 'message' => 'Đăng ký thành công'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
    }
}

/**
 * Change user password
 */
function changePassword($userId, $currentPassword, $newPassword) {
    try {
        $pdo = getPDO();
        
        // Get current user password
        $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = :user_id AND is_active = 1');
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Người dùng không tồn tại hoặc đã bị khóa'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu hiện tại không chính xác'];
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'];
        }
        
        // Check if new password is same as current password
        if (password_verify($newPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE user_id = :user_id');
        $stmt->execute([
            'password' => $hashedPassword,
            'user_id' => $userId
        ]);
        
        return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
    }
}

/**
 * Update user profile
 */
function updateUserProfile($userId, $fullName, $phone) {
    try {
        $pdo = getPDO();
        
        // Check if user exists
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE user_id = :user_id AND is_active = 1');
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Người dùng không tồn tại hoặc đã bị khóa'];
        }
        
        // Validate phone if provided
        if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
            return ['success' => false, 'message' => 'Số điện thoại không hợp lệ'];
        }
        
        // Update user profile
        $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, phone = :phone WHERE user_id = :user_id');
        $stmt->execute([
            'full_name' => trim($fullName),
            'phone' => trim($phone),
            'user_id' => $userId
        ]);
        
        // Update session
        $_SESSION['full_name'] = trim($fullName);
        
        return ['success' => true, 'message' => 'Cập nhật thông tin thành công'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

