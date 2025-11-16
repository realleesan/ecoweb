<?php
/**
 * Related Section Component
 * 
 * Hiển thị 4 tin tức/sản phẩm liên quan dựa trên tags và danh mục
 * 
 * @param string $type Loại: 'news' hoặc 'product'
 * @param int $current_id ID của tin tức/sản phẩm hiện tại
 * @param string|int $category Danh mục (string cho news, int cho product)
 * @param array $tags Mảng các tags
 * @param PDO $pdo Kết nối database (tự động lấy nếu không truyền)
 */

// Đảm bảo có kết nối database
if (!isset($pdo)) {
    require_once __DIR__ . '/../database.php';
    try {
        $pdo = getPDO();
    } catch (RuntimeException $e) {
        $pdo = null;
    }
}

// Không hiển thị nếu không có kết nối database
if (!$pdo) {
    return;
}

// Đảm bảo các tham số được truyền vào
if (!isset($type) || !in_array($type, ['news', 'product'])) {
    return;
}

if (!isset($current_id) || $current_id <= 0) {
    return;
}

$limit = 4; // Số lượng item liên quan cần hiển thị
$related_items = [];

try {
    if ($type === 'news') {
        $related_items = [];
        $existing_ids = [$current_id];
        
        // Bước 1: Tìm tin tức có cùng tags (ưu tiên cao nhất)
        if (!empty($tags) && is_array($tags) && count($tags) > 0) {
            try {
                $placeholders = [];
                $params_sql = [];
                foreach ($tags as $index => $tag) {
                    $placeholders[] = ":tag{$index}";
                    $params_sql[":tag{$index}"] = $tag;
                }
                
                $sql = "SELECT DISTINCT n.news_id, n.title, n.slug, n.publish_date, n.author, n.category, n.excerpt, n.description
                        FROM news n
                        INNER JOIN news_tags nt ON n.news_id = nt.news_id
                        WHERE n.news_id != :current_id AND nt.tag IN (" . implode(',', $placeholders) . ")
                        ORDER BY n.publish_date DESC, n.created_at DESC
                        LIMIT :limit";
                
                $params_sql[':current_id'] = $current_id;
                $params_sql[':limit'] = $limit;
                
                $stmt = $pdo->prepare($sql);
                foreach ($params_sql as $key => $value) {
                    $stmt->bindValue($key, $value, ($key === ':limit' || $key === ':current_id') ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                $stmt->execute();
                $related_items = $stmt->fetchAll();
                if (!empty($related_items)) {
                    $existing_ids = array_merge($existing_ids, array_column($related_items, 'news_id'));
                }
            } catch (PDOException $e) {
                // Nếu có lỗi, tiếp tục với các bước khác
            }
        }
        
        // Bước 2: Nếu không đủ 4 items, lấy thêm từ cùng category
        if (count($related_items) < $limit && !empty($category)) {
            try {
                $remaining = $limit - count($related_items);
                $placeholders = implode(',', array_fill(0, count($existing_ids), '?'));
                
                $sql2 = "SELECT news_id, title, slug, publish_date, author, category, excerpt, description
                         FROM news
                         WHERE news_id NOT IN ($placeholders) AND category = ?
                         ORDER BY publish_date DESC, created_at DESC
                         LIMIT ?";
                
                $params2 = array_merge($existing_ids, [$category, $remaining]);
                $stmt2 = $pdo->prepare($sql2);
                $stmt2->execute($params2);
                $additional_items = $stmt2->fetchAll();
                if (!empty($additional_items)) {
                    $related_items = array_merge($related_items, $additional_items);
                    $existing_ids = array_merge($existing_ids, array_column($additional_items, 'news_id'));
                }
            } catch (PDOException $e) {
                // Nếu có lỗi, tiếp tục với bước 3
            }
        }
        
        // Bước 3: Nếu vẫn chưa đủ, lấy thêm bất kỳ (luôn chạy để đảm bảo có kết quả)
        if (count($related_items) < $limit) {
            try {
                $remaining = $limit - count($related_items);
                $placeholders = implode(',', array_fill(0, count($existing_ids), '?'));
                
                $sql3 = "SELECT news_id, title, slug, publish_date, author, category, excerpt, description
                         FROM news
                         WHERE news_id NOT IN ($placeholders)
                         ORDER BY publish_date DESC, created_at DESC
                         LIMIT ?";
                
                $params3 = array_merge($existing_ids, [$remaining]);
                $stmt3 = $pdo->prepare($sql3);
                $stmt3->execute($params3);
                $additional_items = $stmt3->fetchAll();
                if (!empty($additional_items)) {
                    $related_items = array_merge($related_items, $additional_items);
                }
            } catch (PDOException $e) {
                // Nếu có lỗi, giữ nguyên related_items hiện tại
            }
        }
        
    } elseif ($type === 'product') {
        // Tìm sản phẩm liên quan dựa trên tags và category
        $where_conditions = ["p.product_id != :current_id"];
        $params_sql = [':current_id' => $current_id];
        
        $sql = "SELECT DISTINCT p.product_id, p.code, p.name, p.price, p.short_description, p.category_id, c.slug AS category_slug, c.category_name
                FROM products p
                INNER JOIN categories c ON p.category_id = c.category_id";
        
        // Thêm điều kiện tags nếu có
        if (!empty($tags) && is_array($tags) && count($tags) > 0) {
            $sql .= " INNER JOIN product_tags pt ON p.product_id = pt.product_id";
            $placeholders = [];
            foreach ($tags as $index => $tag) {
                $placeholders[] = ":tag{$index}";
                $params_sql[":tag{$index}"] = $tag;
            }
            $where_conditions[] = "pt.tag IN (" . implode(',', $placeholders) . ")";
        }
        
        // Thêm điều kiện category nếu có
        if (!empty($category) && is_numeric($category)) {
            $where_conditions[] = "p.category_id = :category_id";
            $params_sql[':category_id'] = (int)$category;
        }
        
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params_sql as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $related_items = $stmt->fetchAll();
        
        // Nếu không đủ 4 items, lấy thêm từ cùng category
        if (count($related_items) < $limit && !empty($category) && is_numeric($category)) {
            $remaining = $limit - count($related_items);
            $existing_ids = array_column($related_items, 'product_id');
            $existing_ids[] = $current_id;
            $placeholders = implode(',', array_fill(0, count($existing_ids), '?'));
            
            $sql2 = "SELECT p.product_id, p.code, p.name, p.price, p.short_description, p.category_id, c.slug AS category_slug, c.category_name
                     FROM products p
                     INNER JOIN categories c ON p.category_id = c.category_id
                     WHERE p.product_id NOT IN ($placeholders) AND p.category_id = ?
                     ORDER BY p.created_at DESC
                     LIMIT ?";
            
            $params2 = array_merge($existing_ids, [(int)$category, $remaining]);
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute($params2);
            $additional_items = $stmt2->fetchAll();
            $related_items = array_merge($related_items, $additional_items);
        }
        
        // Nếu vẫn chưa đủ, lấy thêm bất kỳ
        if (count($related_items) < $limit) {
            $remaining = $limit - count($related_items);
            $existing_ids = array_column($related_items, 'product_id');
            $existing_ids[] = $current_id;
            $placeholders = implode(',', array_fill(0, count($existing_ids), '?'));
            
            $sql3 = "SELECT p.product_id, p.code, p.name, p.price, p.short_description, p.category_id, c.slug AS category_slug, c.category_name
                     FROM products p
                     INNER JOIN categories c ON p.category_id = c.category_id
                     WHERE p.product_id NOT IN ($placeholders)
                     ORDER BY p.created_at DESC
                     LIMIT ?";
            
            $params3 = array_merge($existing_ids, [$remaining]);
            $stmt3 = $pdo->prepare($sql3);
            $stmt3->execute($params3);
            $additional_items = $stmt3->fetchAll();
            $related_items = array_merge($related_items, $additional_items);
        }
    }
    
    // Giới hạn lại số lượng
    $related_items = array_slice($related_items, 0, $limit);
    
} catch (PDOException $e) {
    $related_items = [];
}

// Không hiển thị nếu không có item liên quan
if (empty($related_items)) {
    return;
}
?>

<style>
    .related-section {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 60px auto 0;
        padding: 40px <?php echo CONTAINER_PADDING; ?>;
        border-top: 2px solid #e0e0e0;
    }

    .related-section-title {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 28px;
        color: var(--primary);
        margin-bottom: 30px;
        text-align: center;
    }

    .related-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: <?php echo GRID_GAP_SMALL; ?>;
        margin-bottom: 40px;
    }

    /* News Card Styles */
    .related-news-card {
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        cursor: pointer;
        display: flex;
        flex-direction: column;
    }

    .related-news-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .related-news-image {
        width: 100%;
        height: 160px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .related-news-image-placeholder {
        color: var(--dark);
        font-size: 14px;
    }

    .related-news-info-card {
        padding: 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .related-news-category {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 12px;
        color: var(--secondary);
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .related-news-title {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 18px;
        color: var(--dark);
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 50px;
        line-height: 1.4;
    }

    .related-news-title a {
        color: var(--dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .related-news-title a:hover {
        color: var(--primary);
    }

    .related-news-excerpt {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 12px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }

    .related-news-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-size: 12px;
        color: #666;
    }

    .related-news-date {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .related-news-author {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .related-read-more-btn {
        width: 100%;
        padding: 12px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 5px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: background-color 0.3s ease;
        margin-top: 12px;
    }

    .related-read-more-btn:hover {
        background-color: #2d4a2d;
    }

    /* Product Card Styles */
    .related-product-card {
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        cursor: pointer;
    }

    .related-product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .related-product-image {
        width: 100%;
        height: 160px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .related-product-image-placeholder {
        color: var(--dark);
        font-size: 14px;
    }

    .related-product-info {
        padding: 16px;
    }

    .related-product-name {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 18px;
        color: var(--primary);
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 50px;
    }

    .related-product-name a {
        color: var(--dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .related-product-name a:hover {
        color: var(--primary);
    }

    .related-product-price {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 600;
        font-size: 20px;
        color: var(--secondary);
        margin-bottom: 8px;
    }

    .related-product-description {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 12px;
        line-height: 1.5;
    }

    .related-add-to-cart-btn {
        width: 100%;
        padding: 12px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 5px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: background-color 0.3s ease;
    }

    .related-add-to-cart-btn:hover {
        background-color: #2d4a2d;
    }

    @media (max-width: <?php echo BREAKPOINT_XL; ?>) {
        .related-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .related-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: <?php echo GRID_GAP_SMALL; ?>;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_XS; ?>) {
        .related-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="related-section">
    <h2 class="related-section-title">
        <?php echo $type === 'news' ? 'Tin Tức Liên Quan' : 'Sản Phẩm Liên Quan'; ?>
    </h2>
    
    <div class="related-grid">
        <?php if ($type === 'news'): ?>
            <?php foreach ($related_items as $item): ?>
                <div class="related-news-card">
                    <div class="related-news-image">
                        <div class="related-news-image-placeholder">Hình ảnh tin tức</div>
                    </div>
                    <div class="related-news-info-card">
                        <div class="related-news-category"><?php echo htmlspecialchars($item['category']); ?></div>
                        <h3 class="related-news-title">
                            <a href="<?php echo BASE_URL; ?>/views/news-detail.php?slug=<?php echo htmlspecialchars($item['slug']); ?>">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </h3>
                        <p class="related-news-excerpt">
                            <?php echo htmlspecialchars($item['excerpt'] ?: $item['description']); ?>
                        </p>
                        <div class="related-news-meta">
                            <div class="related-news-date">
                                <i class="far fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($item['publish_date'])); ?>
                            </div>
                            <div class="related-news-author">
                                <i class="far fa-user"></i>
                                <?php echo htmlspecialchars($item['author']); ?>
                            </div>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/views/news-detail.php?slug=<?php echo htmlspecialchars($item['slug']); ?>" class="related-read-more-btn">
                            ĐỌC THÊM
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($related_items as $item): ?>
                <div class="related-product-card">
                    <div class="related-product-image">
                        <div class="related-product-image-placeholder">Hình ảnh sản phẩm</div>
                    </div>
                    <div class="related-product-info">
                        <h3 class="related-product-name">
                            <a href="<?php echo BASE_URL; ?>/views/products-detail.php?id=<?php echo (int)$item['product_id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        </h3>
                        <div class="related-product-price">
                            <?php echo number_format((float)$item['price'], 0, ',', '.') . ' đ'; ?>
                        </div>
                        <p class="related-product-description">
                            <?php 
                            $desc = $item['short_description'] ?? '';
                            $words = explode(' ', $desc);
                            echo htmlspecialchars(implode(' ', array_slice($words, 0, 8)) . (count($words) > 8 ? '...' : ''));
                            ?>
                        </p>
                        <button class="related-add-to-cart-btn" onclick="handleRelatedAddToCart(<?php echo (int)$item['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')">
                            THÊM VÀO GIỎ HÀNG
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($type === 'product'): ?>
<script>
    function handleRelatedAddToCart(productId, productName) {
        // Check if user is logged in
        fetch('<?php echo BASE_URL; ?>/api/get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                // If not logged in, redirect to login
                if (!data.success && data.message) {
                    if (confirm('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng. Bạn có muốn đăng nhập không?')) {
                        window.location.href = '<?php echo BASE_URL; ?>/auth/login.php';
                    }
                    return;
                }
                
                // Add to cart
                return fetch('<?php echo BASE_URL; ?>/api/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });
            })
            .then(response => response ? response.json() : null)
            .then(data => {
                if (data && data.success) {
                    // Show toast notification
                    showToast(data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
                    // Update cart count
                    updateCartCount();
                } else if (data && !data.success) {
                    if (data.message && data.message.includes('đăng nhập')) {
                        if (confirm('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng. Bạn có muốn đăng nhập không?')) {
                            window.location.href = '<?php echo BASE_URL; ?>/auth/login.php';
                        }
                    } else {
                        alert(data.message || 'Có lỗi xảy ra');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng');
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

    function showToast(message) {
        let toast = document.getElementById('toast-notification');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast-notification';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
</script>
<?php endif; ?>

