    <?php 
    // Get current page and set active states
    $current_page = basename($_SERVER['PHP_SELF']);
    $is_home = ($current_page == 'index.php' || $current_page == '');
    
    // Always use the full path for the homepage
    $index_link = '/ecoweb/index.php';
    
    // Set base path for other links
    $is_public = (strpos($_SERVER['PHP_SELF'], 'public') !== false);
    $base_path = $is_public ? '' : '/ecoweb/public/';
    ?>
    <!-- Footer -->
    <footer style="background-color: var(--dark); color: var(--white); padding: 50px 5% 20px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; max-width: 1200px; margin: 0 auto;">
            <!-- Về GROWHOPE -->
            <div style="flex: 1; min-width: 200px; margin-bottom: 30px; padding: 0 15px;">
                <h3 style="color: var(--secondary); margin-bottom: 20px; font-size: 18px; position: relative; padding-bottom: 10px;">
                    Về GROWHOPE
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--secondary);"></span>
                </h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 8px;"><a href="<?php echo $index_link; ?>" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Trang chủ</a></li>
                    <li style="margin-bottom: 8px;"><a href="<?php echo $base_path; ?>about.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Giới thiệu</a></li>
                    <li style="margin-bottom: 8px;"><a href="<?php echo $base_path; ?>products.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Sản phẩm</a></li>
                    <li style="margin-bottom: 8px;"><a href="<?php echo $base_path; ?>news.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Tin tức</a></li>
                    <li><a href="<?php echo $base_path; ?>contact.php" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Liên hệ</a></li>
                </ul>
            </div>

            <!-- Sản phẩm của GROWHOPE -->
            <div style="flex: 1; min-width: 200px; margin-bottom: 30px; padding: 0 15px;">
                <h3 style="color: var(--secondary); margin-bottom: 20px; font-size: 18px; position: relative; padding-bottom: 10px;">
                    Sản phẩm của GROWHOPE
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--secondary);"></span>
                </h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Tổ ong</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Cây táo</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Cây xoài</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Cây sầu riêng</a></li>
                    <li><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Cây chanh leo</a></li>
                </ul>
            </div>

            <!-- Chính sách -->
            <div style="flex: 1; min-width: 200px; margin-bottom: 30px; padding: 0 15px;">
                <h3 style="color: var(--secondary); margin-bottom: 20px; font-size: 18px; position: relative; padding-bottom: 10px;">
                    Chính sách
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--secondary);"></span>
                </h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách bảo mật</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách riêng tư</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách thanh toán</a></li>
                    <li style="margin-bottom: 8px;"><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách vận chuyển</a></li>
                    <li><a href="#" style="color: #ddd; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--secondary)'" onmouseout="this.style.color='#ddd'">Chính sách bảo hành</a></li>
                </ul>
            </div>

            <!-- Thông tin liên hệ -->
            <div style="flex: 1; min-width: 250px; margin-bottom: 30px; padding: 0 15px;">
                <h3 style="color: var(--secondary); margin-bottom: 20px; font-size: 18px; position: relative; padding-bottom: 10px;">
                    Thông tin liên hệ
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--secondary);"></span>
                </h3>
                <div style="margin-bottom: 20px;">
                    <p style="margin: 0 0 15px 0; color: #ddd; display: flex; align-items: flex-start;">
                        <i class="fas fa-map-marker-alt" style="color: var(--secondary); margin-right: 10px; margin-top: 5px;"></i>
                        <span>123 Đường Số 1, Phường 2, Quận 3, TP.HCM, Việt Nam</span>
                    </p>
                    <p style="margin: 0 0 15px 0; color: #ddd; display: flex; align-items: center;">
                        <i class="fas fa-phone-alt" style="color: var(--secondary); margin-right: 10px;"></i>
                        <span>0123 456 789</span>
                    </p>
                    <p style="margin: 0 0 15px 0; color: #ddd; display: flex; align-items: center;">
                        <i class="fas fa-envelope" style="color: var(--secondary); margin-right: 10px;"></i>
                        <span>info@growhope.vn</span>
                    </p>
                </div>
                
                <h4 style="color: var(--white); margin-bottom: 15px; font-size: 16px;">Phương thức thanh toán</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=VISA" alt="Visa" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=MC" alt="Mastercard" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=COD" alt="COD" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                    <img src="https://via.placeholder.com/50x30/3C603C/FFFFFF?text=MOMO" alt="Momo" style="height: 30px; background: white; padding: 5px; border-radius: 4px;">
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div style="text-align: center; padding: 20px 0 0; margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1);">
            <p style="margin: 0; color: #aaa; font-size: 14px;">
                &copy; 2023 GROWHOPE. Tất cả các quyền được bảo lưu.
            </p>
        </div>
    </footer>
    
    <script>
        // Add active class to current menu item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop() || 'index.php';
            const menuItems = document.querySelectorAll('.menu-list li a');
            
            menuItems.forEach(item => {
                if (item.getAttribute('href') === currentPage) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>