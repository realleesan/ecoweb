<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function isAdminLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $hasAdminId = isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    $hasUserAdmin = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    return $hasAdminId || $hasUserAdmin;
}


function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}


function loginAdmin($usernameOrEmail, $password) {
    try {
        $pdo = getPDO();


        $stmt = $pdo->prepare('SELECT user_id, username, email, password, full_name, role, is_active FROM users WHERE (username = :identifier OR email = :identifier) AND role = "admin"');
        $stmt->execute(['identifier' => $usernameOrEmail]);
        $user = $stmt->fetch();


        if (!$user) {
            return ['success' => false, 'message' => 'Tài khoản admin không tồn tại'];
        }


        if (!(int)$user['is_active']) {
            return ['success' => false, 'message' => 'Tài khoản admin đã bị khóa'];
        }


        $stored = (string)$user['password'];
        $isBcrypt = preg_match('/^\$2y\$\d{2}\$[A-Za-z0-9\.\/]{53}$/', $stored) === 1;
        $verified = $isBcrypt ? password_verify($password, $stored) : hash_equals($stored, $password);


        if (!$verified) {
            return ['success' => false, 'message' => 'Mật khẩu không chính xác'];
        }


        if (!$isBcrypt) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE users SET password = :password WHERE user_id = :user_id');
            $upd->execute(['password' => $newHash, 'user_id' => $user['user_id']]);
        }


        $_SESSION['admin_id'] = $user['user_id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['role'] = 'admin';


        return ['success' => true, 'redirect' => BASE_URL . '/admin/index.php'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
    }
}


function logoutAdmin() {
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_email']);
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['email'], $_SESSION['full_name'], $_SESSION['role']);
    }
}

