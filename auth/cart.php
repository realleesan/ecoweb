<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Require login
requireLogin();

// Get current user
$user = getCurrentUser();
if (!$user) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$cartItems = [];
$totalAmount = 0;

try {
    $pdo = getPDO();
    $userId = $_SESSION['user_id'];
    
    // Get cart items with product details
    $stmt = $pdo->prepare('
        SELECT c.cart_id, c.quantity, c.created_at,
               p.product_id, p.name, p.price, p.stock, p.short_description
        FROM cart c
        INNER JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = :user_id
        ORDER BY c.created_at DESC
    ');
    $stmt->execute(['user_id' => $userId]);
    $cartItems = $stmt->fetchAll();
    
    // Calculate total amount
    foreach ($cartItems as $item) {
        $totalAmount += $item['price'] * $item['quantity'];
    }
} catch (Exception $e) {
    $cartItems = [];
    $totalAmount = 0;
}

include '../includes/header.php';
?>

<style>
    body {
        background-color: var(--light);
        min-height: 100vh;
    }

    .account-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 40px auto;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
    }

    .account-content {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
        align-items: start;
    }

    .account-main {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 40px;
    }

    .account-main-header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--light);
    }

    .account-main-header h1 {
        font-size: 28px;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .account-main-header p {
        color: var(--dark);
        font-size: 14px;
    }

    .cart-empty {
        text-align: center;
        padding: 60px 20px;
    }

    .cart-empty i {
        font-size: 64px;
        color: #ddd;
        margin-bottom: 20px;
    }

    .cart-empty h2 {
        font-size: 24px;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .cart-empty p {
        color: #999;
        margin-bottom: 30px;
    }

    .btn-shopping {
        display: inline-block;
        padding: 12px 30px;
        background-color: var(--primary);
        color: var(--white);
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        transition: background-color 0.3s ease;
    }

    .btn-shopping:hover {
        background-color: #2d4a2d;
    }

    .cart-items {
        margin-bottom: 30px;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 100px 1fr auto auto;
        gap: 20px;
        padding: 20px;
        border-bottom: 1px solid #f0f0f0;
        align-items: center;
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .cart-item-image {
        width: 100px;
        height: 100px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 32px;
    }

    .cart-item-info {
        flex: 1;
    }

    .cart-item-info h3 {
        font-size: 18px;
        color: var(--primary);
        margin-bottom: 8px;
    }

    .cart-item-info p {
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }

    .cart-item-price {
        font-size: 18px;
        font-weight: 600;
        color: var(--secondary);
        white-space: nowrap;
    }

    .cart-item-quantity {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quantity-control {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        border: none;
        background-color: var(--light);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s ease;
    }

    .quantity-btn:hover {
        background-color: #e0e0e0;
    }

    .quantity-input {
        width: 50px;
        height: 30px;
        border: none;
        text-align: center;
        font-size: 14px;
    }

    .cart-item-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .btn-remove {
        padding: 8px 15px;
        background-color: #dc3545;
        color: var(--white);
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }

    .btn-remove:hover {
        background-color: #c33;
    }

    .cart-summary {
        background-color: var(--light);
        padding: 25px;
        border-radius: 10px;
        margin-top: 30px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 16px;
    }

    .summary-row:last-child {
        margin-bottom: 0;
        padding-top: 15px;
        border-top: 2px solid var(--primary);
        font-size: 20px;
        font-weight: 600;
        color: var(--primary);
    }

    .btn-checkout {
        width: 100%;
        padding: 15px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-top: 20px;
    }

    .btn-checkout:hover {
        background-color: #2d4a2d;
    }

    .btn-checkout:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .account-content {
            grid-template-columns: 1fr;
        }

        .account-main {
            padding: 25px 20px;
        }

        .cart-item {
            grid-template-columns: 80px 1fr;
            gap: 15px;
        }

        .cart-item-price,
        .cart-item-quantity,
        .cart-item-actions {
            grid-column: 2;
        }

        .cart-item-price {
            margin-top: 10px;
        }
    }
</style>

<div class="account-container">
    <div class="account-content">
        <?php include 'sidebar-account.php'; ?>
        
        <div class="account-main">
            <div class="account-main-header">
                <h1>Giỏ hàng</h1>
                <p>Quản lý sản phẩm trong giỏ hàng của bạn</p>
            </div>

            <?php if (empty($cartItems)): ?>
                <div class="cart-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Giỏ hàng trống</h2>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                    <a href="<?php echo BASE_URL; ?>/public/products.php" class="btn-shopping">
                        <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="cart-item-image">
                                <i class="fas fa-seedling"></i>
                            </div>
                            <div class="cart-item-info">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <?php if (!empty($item['short_description'])): ?>
                                    <p><?php echo htmlspecialchars($item['short_description']); ?></p>
                                <?php endif; ?>
                                <p style="color: #999; font-size: 12px;">Tồn kho: <?php echo $item['stock']; ?> sản phẩm</p>
                            </div>
                            <div class="cart-item-price">
                                <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                            </div>
                            <div class="cart-item-quantity">
                                <div class="quantity-control">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] - 1; ?>, <?php echo $item['stock']; ?>)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock']; ?>"
                                           onchange="updateQuantity(<?php echo $item['cart_id']; ?>, this.value, <?php echo $item['stock']; ?>)">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>, <?php echo $item['stock']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="cart-item-actions">
                                <button class="btn-remove" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Tổng số sản phẩm:</span>
                        <span><?php echo count($cartItems); ?> sản phẩm</span>
                    </div>
                    <div class="summary-row">
                        <span>Tổng tiền:</span>
                        <span><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</span>
                    </div>
                    <button class="btn-checkout" onclick="checkout()">
                        <i class="fas fa-credit-card"></i> Thanh toán
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function updateQuantity(cartId, newQuantity, maxStock) {
        if (newQuantity < 1) {
            newQuantity = 1;
        }
        if (newQuantity > maxStock) {
            alert('Số lượng vượt quá tồn kho. Tồn kho hiện tại: ' + maxStock);
            newQuantity = maxStock;
        }

        fetch('<?php echo BASE_URL; ?>/api/update-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cart_id: cartId,
                quantity: newQuantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi cập nhật số lượng');
        });
    }

    function removeFromCart(cartId) {
        if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            return;
        }

        fetch('<?php echo BASE_URL; ?>/api/remove-from-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cart_id: cartId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi xóa sản phẩm');
        });
    }

    function checkout() {
        window.location.href = '<?php echo BASE_URL; ?>/payment/checkout.php';
    }
</script>

<?php include '../includes/footer.php'; ?>

