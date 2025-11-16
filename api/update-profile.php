<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$fullName = isset($input['full_name']) ? trim($input['full_name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';

// Validate input
if (empty($fullName)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập họ và tên']);
    exit;
}

if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ']);
    exit;
}

try {
    $result = updateUserProfile($_SESSION['user_id'], $fullName, $phone);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

