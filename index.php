<?php include 'includes/header.php'; ?>

<style>
    :root {
        --primary: #3C603C;
        --secondary: #D26426;
        --dark: #74493D;
        --light: #FFF7ED;
        --white: #FFFFFF;
        --bg-green: #9FBD48;
    }

    body {
        background-color: var(--light);
        font-family: 'Poppins', sans-serif;
    }

    /* Hero Section */
    .hero-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin: 30px auto;
        max-width: 1200px;
        padding: 0 5%;
    }

    .hero-banner {
        position: relative;
        border-radius: 15px;
        overflow: hidden;
        height: 500px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--bg-green) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .hero-content {
        text-align: center;
        padding: 40px;
        z-index: 2;
    }

    .hero-content h1 {
        font-size: 42px;
        font-weight: 700;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .hero-content p {
        font-size: 18px;
        margin-bottom: 30px;
        line-height: 1.6;
    }

    .hero-btn {
        background-color: var(--secondary);
        color: var(--white);
        padding: 15px 40px;
        border: none;
        border-radius: 30px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .hero-btn:hover {
        background-color: var(--dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(210, 100, 38, 0.4);
    }

    /* News Section */
    .news-sidebar {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .news-header {
        background-color: var(--primary);
        color: var(--white);
        padding: 15px 20px;
        border-radius: 10px 10px 0 0;
        font-weight: 700;
        font-size: 20px;
    }

    .news-list {
        background-color: var(--white);
        border-radius: 0 0 10px 10px;
        padding: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        gap: 15px;
        flex: 1;
    }

    .news-item {
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .news-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .news-item:hover {
        transform: translateX(5px);
    }

    .news-item a {
        text-decoration: none;
        color: var(--dark);
        display: block;
    }

    .news-item h3 {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
        color: var(--primary);
        line-height: 1.4;
    }

    .news-item p {
        font-size: 14px;
        color: #666;
        line-height: 1.5;
        margin-bottom: 5px;
    }

    .news-date {
        font-size: 12px;
        color: var(--secondary);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Section Styles */
    .section {
        margin: 40px auto;
        max-width: 1200px;
        padding: 0 5%;
    }

    .section-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .section-header h2 {
        font-size: 36px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 15px;
        position: relative;
        display: inline-block;
    }

    .section-header h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: var(--secondary);
        border-radius: 2px;
    }

    .section-header p {
        font-size: 16px;
        color: var(--dark);
        margin-top: 20px;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    /* Introduction Section */
    .intro-content {
        background-color: var(--white);
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        line-height: 1.8;
        font-size: 16px;
        color: var(--dark);
    }

    .intro-content p {
        margin-bottom: 20px;
    }

    /* Map Section */
    .map-container {
        background-color: var(--white);
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid var(--bg-green);
    }

    .map-placeholder {
        text-align: center;
        color: var(--dark);
    }

    .map-placeholder i {
        font-size: 64px;
        color: var(--bg-green);
        margin-bottom: 20px;
        display: block;
    }

    .map-placeholder h3 {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }

    /* Products Section */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
    }

    .product-card {
        background-color: var(--white);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .product-image {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, var(--bg-green) 0%, var(--primary) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 48px;
    }

    .product-info {
        padding: 20px;
    }

    .product-info h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .product-info p {
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
        line-height: 1.5;
    }

    .product-price {
        font-size: 18px;
        font-weight: 700;
        color: var(--secondary);
    }

    /* Gallery Section */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }

    .gallery-item {
        position: relative;
        border-radius: 15px;
        overflow: hidden;
        height: 250px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--bg-green) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 18px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .gallery-item:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, var(--primary) 0%, var(--bg-green) 100%);
        padding: 60px 5%;
        text-align: center;
        color: var(--white);
        margin: 40px 0 0;
    }

    .cta-content {
        max-width: 1200px;
        margin: 0 auto;
    }

    .cta-content h2 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .cta-content p {
        font-size: 18px;
        margin-bottom: 30px;
        line-height: 1.6;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .cta-btn {
        background-color: var(--secondary);
        color: var(--white);
        padding: 18px 50px;
        border: none;
        border-radius: 30px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    .cta-btn:hover {
        background-color: var(--dark);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    }

    /* Responsive */
    @media (max-width: 992px) {
        .hero-section {
            grid-template-columns: 1fr;
        }

        .hero-banner {
            height: 400px;
        }

        .hero-content h1 {
            font-size: 32px;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        .gallery-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
    }

    @media (max-width: 576px) {
        .hero-content h1 {
            font-size: 24px;
        }

        .hero-content p {
            font-size: 16px;
        }

        .section-header h2 {
            font-size: 28px;
        }

        .intro-content {
            padding: 30px 20px;
        }

        .map-container {
            height: 300px;
        }

        .cta-section {
            padding: 40px 5%;
        }

        .cta-content h2 {
            font-size: 28px;
        }

        .cta-content p {
            font-size: 16px;
        }
    }
</style>

<!-- Hero Section with News -->
<section class="hero-section">
    <div class="hero-banner">
        <div class="hero-content">
            <h1>Trồng cây gây rừng</h1>
            <p>Hãy cùng chúng tôi tạo nên một tương lai xanh cho Trái Đất</p>
            <a href="products.php" class="hero-btn">Khám phá ngay</a>
        </div>
    </div>
    
    <div class="news-sidebar">
        <div class="news-header">
            <i class="fas fa-newspaper"></i> Tin tức mới nhất
        </div>
        <div class="news-list">
            <div class="news-item">
                <a href="news.php">
                    <h3>Cách thức trồng cây gây rừng</h3>
                    <p>Hướng dẫn chi tiết các bước trồng cây và chăm sóc để tạo nên một khu rừng xanh...</p>
                    <div class="news-date">
                        <i class="far fa-calendar"></i>
                        <span>15/12/2023</span>
                    </div>
                </a>
            </div>
            <div class="news-item">
                <a href="news.php">
                    <h3>Dự án phủ xanh 1000 ha rừng tại Tây Nguyên</h3>
                    <p>Dự án lớn nhất trong năm với mục tiêu phủ xanh 1000 ha đất trống...</p>
                    <div class="news-date">
                        <i class="far fa-calendar"></i>
                        <span>12/12/2023</span>
                    </div>
                </a>
            </div>
            <div class="news-item">
                <a href="news.php">
                    <h3>Hạt giống mới: Giống cây chịu hạn tốt</h3>
                    <p>Giới thiệu các loại hạt giống mới có khả năng chịu hạn cao, phù hợp với khí hậu...</p>
                    <div class="news-date">
                        <i class="far fa-calendar"></i>
                        <span>10/12/2023</span>
                    </div>
                </a>
            </div>
            <div class="news-item">
                <a href="news.php">
                    <h3>Thành công từ dự án trồng rừng tại miền Bắc</h3>
                    <p>Kết quả tích cực từ dự án trồng rừng đã được triển khai tại các tỉnh miền Bắc...</p>
                    <div class="news-date">
                        <i class="far fa-calendar"></i>
                        <span>08/12/2023</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Introduction Section -->
<section class="section">
    <div class="section-header">
        <h2>Giới thiệu chung</h2>
    </div>
    <div class="intro-content">
        <p>
            <strong>GROWHOPE</strong> là dự án phi lợi nhuận với sứ mệnh góp phần phủ xanh Trái Đất thông qua việc trồng cây gây rừng. Chúng tôi tin rằng mỗi cây xanh được trồng là một bước tiến quan trọng trong việc bảo vệ môi trường và tạo nên một tương lai bền vững cho thế hệ mai sau.
        </p>
        <p>
            Với đội ngũ chuyên gia giàu kinh nghiệm và mạng lưới đối tác rộng khắp, chúng tôi đã và đang triển khai nhiều dự án trồng rừng tại các khu vực khác nhau trên cả nước. Mỗi dự án đều được lên kế hoạch kỹ lưỡng, chọn lọc giống cây phù hợp với điều kiện địa phương và được chăm sóc theo quy trình khoa học.
        </p>
        <p>
            Chúng tôi không chỉ dừng lại ở việc trồng cây, mà còn cam kết theo dõi, chăm sóc và đảm bảo tỷ lệ sống sót cao của cây trồng. Bên cạnh đó, chúng tôi cũng tổ chức các chương trình giáo dục, nâng cao nhận thức cộng đồng về tầm quan trọng của việc bảo vệ rừng và môi trường.
        </p>
    </div>
</section>

<!-- Map Section -->
<section class="section">
    <div class="section-header">
        <h2>Bản đồ phủ xanh</h2>
        <p>Theo dõi các dự án trồng rừng của chúng tôi trên khắp cả nước</p>
    </div>
    <div class="map-container">
        <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <h3>Bản đồ phủ xanh</h3>
            <p>Bản đồ tương tác hiển thị các khu vực đã được phủ xanh</p>
            <p style="font-size: 14px; margin-top: 10px; color: #999;">Bản đồ sẽ được tích hợp tại đây</p>
        </div>
    </div>
</section>

<!-- Trending Seeds Section -->
<section class="section">
    <div class="section-header">
        <h2>Hạt giống thịnh hành</h2>
        <p>Những loại hạt giống được yêu thích nhất hiện nay</p>
    </div>
    <div class="products-grid">
        <div class="product-card">
            <div class="product-image">
                <i class="fas fa-seedling"></i>
            </div>
            <div class="product-info">
                <h3>Hạt giống cây keo</h3>
                <p>Giống cây keo phát triển nhanh, chịu hạn tốt, phù hợp trồng rừng phòng hộ</p>
                <div class="product-price">50.000đ</div>
            </div>
        </div>
        <div class="product-card">
            <div class="product-image">
                <i class="fas fa-tree"></i>
            </div>
            <div class="product-info">
                <h3>Hạt giống cây bạch đàn</h3>
                <p>Loại cây có giá trị kinh tế cao, sinh trưởng mạnh, thích hợp nhiều loại đất</p>
                <div class="product-price">45.000đ</div>
            </div>
        </div>
        <div class="product-card">
            <div class="product-image">
                <i class="fas fa-leaf"></i>
            </div>
            <div class="product-info">
                <h3>Hạt giống cây tràm</h3>
                <p>Cây tràm chịu mặn tốt, phù hợp trồng ven biển và vùng đất nhiễm mặn</p>
                <div class="product-price">40.000đ</div>
            </div>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section class="section">
    <div class="section-header">
        <h2>Hình ảnh dự án</h2>
        <p>Những khoảnh khắc đẹp từ các dự án trồng rừng đã hoàn thành</p>
    </div>
    <div class="gallery-grid">
        <div class="gallery-item">
            <div>
                <i class="fas fa-mountain" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Dự án Tây Nguyên</p>
            </div>
        </div>
        <div class="gallery-item">
            <div>
                <i class="fas fa-forest" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Rừng phòng hộ miền Bắc</p>
            </div>
        </div>
        <div class="gallery-item">
            <div>
                <i class="fas fa-water" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Trồng rừng ven biển</p>
            </div>
        </div>
        <div class="gallery-item">
            <div>
                <i class="fas fa-hands-holding-seedling" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Hoạt động tình nguyện</p>
            </div>
        </div>
        <div class="gallery-item">
            <div>
                <i class="fas fa-globe-americas" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Khu vực phủ xanh</p>
            </div>
        </div>
        <div class="gallery-item">
            <div>
                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Cộng đồng tham gia</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-content">
        <h2>Hãy cùng gieo thêm một mầm xanh cho Trái Đất hôm nay</h2>
        <p>Mỗi cây xanh bạn trồng là một đóng góp ý nghĩa cho tương lai của hành tinh. Hãy tham gia cùng chúng tôi trong hành trình phủ xanh Trái Đất!</p>
        <a href="products.php" class="cta-btn">Tham gia phủ xanh</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>


