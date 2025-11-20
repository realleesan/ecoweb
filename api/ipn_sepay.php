<?php
/**
 * SePay IPN (Instant Payment Notification) Handler
 * File xử lý webhook từ SePay khi khách hàng chuyển khoản thành công
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/sepay_config.php';

// Set header JSON
header('Content-Type: application/json; charset=utf-8');

// Log function để debug (có thể bật/tắt)
function logIPN($message, $data = null) {
    $logFile = __DIR__ . '/../logs/sepay_ipn.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= ' | Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

try {
    // Chỉ chấp nhận POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        logIPN('ERROR: Invalid request method', $_SERVER['REQUEST_METHOD']);
        exit;
    }
    
    // Nhận dữ liệu từ SePay (có thể là JSON hoặc form-urlencoded)
    $rawInput = file_get_contents('php://input');
    logIPN('Received IPN', $rawInput);
    
    if (empty($rawInput)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Empty request body']);
        logIPN('ERROR: Empty request body');
        exit;
    }
    
    // Thử decode JSON trước
    $ipnData = json_decode($rawInput, true);
    
    // Nếu không phải JSON, parse như form data
    if (json_last_error() !== JSON_ERROR_NONE) {
        parse_str($rawInput, $ipnData);
        logIPN('Parsed as form data', $ipnData);
    }
    
    // Lấy mã đơn hàng từ content (nội dung chuyển khoản)
    $content = trim($ipnData['content'] ?? $ipnData['description'] ?? '');
    $orderCode = '';
    
    // Tìm mã đơn hàng trong content (format: HBN20251121XXXXXX)
    if (preg_match('/HBN\d{8}[A-Z0-9]{6}/', $content, $matches)) {
        $orderCode = $matches[0];
    }
    
    // Fallback: Lấy từ order_id nếu có
    if (empty($orderCode)) {
        $orderCode = trim($ipnData['order_id'] ?? $ipnData['order_code'] ?? '');
    }
    
    $transferAmount = floatval($ipnData['transferAmount'] ?? $ipnData['amount'] ?? 0);
    $transactionId = trim($ipnData['transaction_id'] ?? $ipnData['transactionId'] ?? $ipnData['referenceCode'] ?? '');
    $status = trim($ipnData['status'] ?? 'success');
    
    // Validate dữ liệu đầu vào
    if (empty($orderCode)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing order_code']);
        logIPN('ERROR: Missing order_code', $ipnData);
        exit;
    }
    
    if ($transferAmount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid transfer amount']);
        logIPN('ERROR: Invalid amount', ['order_code' => $orderCode, 'amount' => $transferAmount]);
        exit;
    }
    
    // Kết nối database
    $pdo = getPDO();
    $pdo->beginTransaction();
    
    // Kiểm tra đơn hàng trong database
    $stmt = $pdo->prepare('
        SELECT order_id, order_code, final_amount, status 
        FROM orders 
        WHERE order_code = :order_code
        LIMIT 1
    ');
    $stmt->execute([':order_code' => $orderCode]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $pdo->rollBack();
        http_response_cod(200);
        echo json_encode(['success' => false, 'message' => 'KET NOI OK - NHUNG KHONG TIM THAY DON HANG:' . $orderCode]);
        logIPN('ERROR: Order not found', ['order_code' => $orderCode]);
        exit;
    }
    
    // Kiểm tra số tiền chuyển khoản
    $finalAmount = floatval($order['final_amount']);
    
    if ($transferAmount < $finalAmount) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Transfer amount is less than order amount',
            'expected' => $finalAmount,
            'received' => $transferAmount
        ]);
        logIPN('ERROR: Amount mismatch', [
            'order_code' => $orderCode,
            'expected' => $finalAmount,
            'received' => $transferAmount
        ]);
        exit;
    }
    
    // Kiểm tra nếu đơn hàng đã được thanh toán rồi
    if ($order['status'] === 'paid') {
        $pdo->rollBack();
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => 'Order already paid',
            'order_code' => $orderCode
        ]);
        logIPN('WARNING: Order already paid', ['order_code' => $orderCode]);
        exit;
    }
    
    // Cập nhật trạng thái đơn hàng thành 'paid'
    $updateStmt = $pdo->prepare('
        UPDATE orders 
        SET status = :status,
            updated_at = NOW()
        WHERE order_id = :order_id
    ');
    
    $updateStmt->execute([
        ':status' => 'paid',
        ':order_id' => $order['order_id']
    ]);
    
    // Lưu thông tin giao dịch (nếu có bảng payment_transactions)
    try {
        $transStmt = $pdo->prepare('
            INSERT INTO payment_transactions 
            (order_id, transaction_id, amount, payment_method, status, created_at) 
            VALUES (:order_id, :transaction_id, :amount, :payment_method, :status, NOW())
        ');
        
        $transStmt->execute([
            ':order_id' => $order['order_id'],
            ':transaction_id' => $transactionId,
            ':amount' => $transferAmount,
            ':payment_method' => 'sepay',
            ':status' => 'completed'
        ]);
    } catch (PDOException $e) {
        // Bảng payment_transactions có thể chưa tồn tại, bỏ qua lỗi này
        logIPN('WARNING: Could not save transaction', $e->getMessage());
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Trả về success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'order_code' => $orderCode,
        'amount' => $transferAmount
    ]);
    
    logIPN('SUCCESS: Payment processed', [
        'order_code' => $orderCode,
        'amount' => $transferAmount,
        'transaction_id' => $transactionId
    ]);
    
} catch (PDOException $e) {
    // Rollback nếu có lỗi database
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    
    logIPN('ERROR: Database exception', $e->getMessage());
    
} catch (Exception $e) {
    // Rollback nếu có lỗi khác
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    
    logIPN('ERROR: General exception', $e->getMessage());
}
