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

$wishlistItems = [];

try {
    $pdo = getPDO();
    $userId = $_SESSION['user_id'];
    
    // Get wishlist items with product details
    $stmt = $pdo->prepare('
        SELECT w.wishlist_id, w.created_at,
               p.product_id, p.name, p.price, p.stock, p.short_description, p.rating, p.reviews_count
        FROM wishlist w
        INNER JOIN products p ON w.product_id = p.product_id
        WHERE w.user_id = :user_id
        ORDER BY w.created_at DESC
    ');
    $stmt->execute(['user_id' => $userId]);
    $wishlistItems = $stmt->fetchAll();
} catch (Exception $e) {
    $wishlistItems = [];
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

    .wishlist-empty {
        text-align: center;
        padding: 60px 20px;
    }

    .wishlist-empty i {
        font-size: 64px;
        color: #ddd;
        margin-bottom: 20px;
    }

    .wishlist-empty h2 {
        font-size: 24px;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .wishlist-empty p {
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

    .wishlist-items {
        display: grid;
        gap: 20px;
    }

    .wishlist-item {
        display: grid;
        grid-template-columns: 120px 1fr auto;
        gap: 20px;
        padding: 20px;
        border: 1px solid #f0f0f0;
        border-radius: 10px;
        transition: box-shadow 0.3s ease;
        align-items: center;
    }

    .wishlist-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .wishlist-item-image {
        width: 120px;
        height: 120px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 48px;
    }

    .wishlist-item-info {
        flex: 1;
    }

    .wishlist-item-info h3 {
        font-size: 20px;
        color: var(--primary);
        margin-bottom: 8px;
    }

    .wishlist-item-info h3 a {
        color: var(--primary);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .wishlist-item-info h3 a:hover {
        color: var(--secondary);
    }

    .wishlist-item-info p {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
        line-height: 1.5;
    }

    .wishlist-item-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 10px;
    }

    .wishlist-item-price {
        font-size: 20px;
        font-weight: 600;
        color: var(--secondary);
    }

    .wishlist-item-rating {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #ffc107;
        font-size: 14px;
    }

    .wishlist-item-stock {
        font-size: 14px;
        color: #999;
    }

    .wishlist-item-stock.in-stock {
        color: #28a745;
    }

    .wishlist-item-stock.out-of-stock {
        color: #dc3545;
    }

    .wishlist-item-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .btn-add-cart {
        padding: 10px 20px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: background-color 0.3s ease;
        white-space: nowrap;
    }

    .btn-add-cart:hover {
        background-color: #2d4a2d;
    }

    .btn-add-cart:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    .btn-remove-wishlist {
        padding: 10px 20px;
        background-color: #dc3545;
        color: var(--white);
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s ease;
        white-space: nowrap;
    }

    .btn-remove-wishlist:hover {
        background-color: #c33;
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .account-content {
            grid-template-columns: 1fr;
        }

        .account-main {
            padding: 25px 20px;
        }

        .wishlist-item {
            grid-template-columns: 100px 1fr;
            gap: 15px;
        }

        .wishlist-item-actions {
            grid-column: 1 / -1;
            flex-direction: row;
        }

        .btn-add-cart,
        .btn-remove-wishlist {
            flex: 1;
        }
    }
</style>

<div class="account-container">
    <div class="account-content">
        <?php include 'sidebar-account.php'; ?>
        
        <div class="account-main">
            <div class="account-main-header">
                <h1>Yêu thích</h1>
                <p>Danh sách sản phẩm bạn đã yêu thích</p>
            </div>

            <?php if (empty($wishlistItems)): ?>
                <div class="wishlist-empty">
                    <i class="fas fa-heart"></i>
                    <h2>Danh sách yêu thích trống</h2>
                    <p>Bạn chưa có sản phẩm nào trong danh sách yêu thích</p>
                    <a href="<?php echo BASE_URL; ?>/public/products.php" class="btn-shopping">
                        <i class="fas fa-shopping-bag"></i> Khám phá sản phẩm
                    </a>
                </div>
            <?php else: ?>
                <div class="wishlist-items">
                    <?php foreach ($wishlistItems as $item): ?>
                        <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="wishlist-item-image">
                                <i class="fas fa-seedling"></i>
                            </div>
                            <div class="wishlist-item-info">
                                <h3>
                                    <a href="<?php echo BASE_URL; ?>/views/products-detail.php?id=<?php echo $item['product_id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($item['short_description'])): ?>
                                    <p><?php echo htmlspecialchars($item['short_description']); ?></p>
                                <?php endif; ?>
                                <div class="wishlist-item-meta">
                                    <span class="wishlist-item-price">
                                        <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                                    </span>
                                    <?php if ($item['rating'] > 0): ?>
                                        <span class="wishlist-item-rating">
                                            <i class="fas fa-star"></i>
                                            <?php echo number_format($item['rating'], 1); ?>
                                            <span style="color: #999; margin-left: 5px;">(<?php echo $item['reviews_count']; ?>)</span>
                                        </span>
                                    <?php endif; ?>
                                    <span class="wishlist-item-stock <?php echo $item['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                        <?php echo $item['stock'] > 0 ? 'Còn hàng' : 'Hết hàng'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="wishlist-item-actions">
                                <button class="btn-add-cart" 
                                        onclick="addToCart(<?php echo $item['product_id']; ?>)"
                                        <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                                </button>
                                <button class="btn-remove-wishlist" onclick="removeFromWishlist(<?php echo $item['wishlist_id']; ?>, <?php echo $item['product_id']; ?>)">
                                    <i class="fas fa-heart-broken"></i> Xóa khỏi yêu thích
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function addToCart(productId) {
        fetch('<?php echo BASE_URL; ?>/api/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
                // Update cart count
                updateCartCount();
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng');
        });
    }

    function removeFromWishlist(wishlistId, productId) {
        if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi danh sách yêu thích?')) {
            return;
        }

        fetch('<?php echo BASE_URL; ?>/api/wishlist-toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove item from DOM
                const item = document.querySelector(`[data-product-id="${productId}"]`);
                if (item) {
                    item.style.transition = 'opacity 0.3s ease';
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.remove();
                        // Check if wishlist is empty
                        const items = document.querySelectorAll('.wishlist-item');
                        if (items.length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi xóa sản phẩm khỏi yêu thích');
        });
    }

    function updateCartCount() {
        fetch('<?php echo BASE_URL; ?>/api/get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.count;
                        if (data.count > 0) {
                            cartCountElement.style.display = 'flex';
                        } else {
                            cartCountElement.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching cart count:', error);
            });
    }
</script>

<?php include '../includes/footer.php'; ?>

