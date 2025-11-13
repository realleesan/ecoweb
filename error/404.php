<?php include '../includes/header.php'; ?>

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

    .error-container {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 5%;
        max-width: 1200px;
        margin: 0 auto;
    }

    .error-content {
        text-align: center;
        background-color: var(--white);
        padding: 60px 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        max-width: 700px;
        width: 100%;
    }

    .error-icon {
        font-size: 120px;
        color: var(--bg-green);
        margin-bottom: 30px;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-20px);
        }
    }

    .error-code {
        font-size: 120px;
        font-weight: 700;
        color: var(--primary);
        line-height: 1;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .error-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 20px;
    }

    .error-message {
        font-size: 18px;
        color: var(--dark);
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .error-actions {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-home {
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
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .btn-home:hover {
        background-color: var(--dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(210, 100, 38, 0.4);
    }

    .btn-back {
        background-color: var(--white);
        color: var(--primary);
        padding: 15px 40px;
        border: 2px solid var(--primary);
        border-radius: 30px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .btn-back:hover {
        background-color: var(--primary);
        color: var(--white);
        transform: translateY(-2px);
    }

    .quick-links {
        margin-top: 50px;
        padding-top: 40px;
        border-top: 2px solid #e0e0e0;
    }

    .quick-links h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 25px;
    }

    .links-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .link-item {
        background-color: var(--light);
        padding: 15px 20px;
        border-radius: 10px;
        text-decoration: none;
        color: var(--dark);
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .link-item:hover {
        background-color: var(--bg-green);
        color: var(--white);
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .link-item i {
        font-size: 18px;
    }

    /* Decorative Elements */
    .leaf-decoration {
        position: absolute;
        font-size: 200px;
        color: var(--bg-green);
        opacity: 0.1;
        z-index: 0;
    }

    .leaf-1 {
        top: 10%;
        left: 5%;
        transform: rotate(-20deg);
    }

    .leaf-2 {
        bottom: 10%;
        right: 5%;
        transform: rotate(20deg);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .error-content {
            padding: 40px 25px;
        }

        .error-code {
            font-size: 80px;
        }

        .error-title {
            font-size: 24px;
        }

        .error-message {
            font-size: 16px;
        }

        .error-icon {
            font-size: 80px;
        }

        .error-actions {
            flex-direction: column;
        }

        .btn-home,
        .btn-back {
            width: 100%;
            justify-content: center;
        }

        .links-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="error-container">
    <div class="leaf-decoration leaf-1">
        <i class="fas fa-leaf"></i>
    </div>
    <div class="leaf-decoration leaf-2">
        <i class="fas fa-seedling"></i>
    </div>
    
    <div class="error-content">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <div class="error-code">404</div>
        
        <h1 class="error-title">Trang không tìm thấy</h1>
        
        <p class="error-message">
            Xin lỗi, trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển. 
            Có thể liên kết đã bị hỏng hoặc địa chỉ URL không chính xác.
        </p>
        
        <div class="error-actions">
            <a href="../index.php" class="btn-home">
                <i class="fas fa-home"></i>
                Về trang chủ
            </a>
            <a href="javascript:history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Quay lại
            </a>
        </div>
        
        <div class="quick-links">
            <h3>Liên kết nhanh</h3>
            <div class="links-grid">
                <a href="../index.php" class="link-item">
                    <i class="fas fa-home"></i>
                    <span>Trang chủ</span>
                </a>
                <a href="../public/about.php" class="link-item">
                    <i class="fas fa-info-circle"></i>
                    <span>Giới thiệu</span>
                </a>
                <a href="../public/products.php" class="link-item">
                    <i class="fas fa-seedling"></i>
                    <span>Sản phẩm</span>
                </a>
                <a href="../public/categories.php" class="link-item">
                    <i class="fas fa-list"></i>
                    <span>Danh mục</span>
                </a>
                <a href="../public/news.php" class="link-item">
                    <i class="fas fa-newspaper"></i>
                    <span>Tin tức</span>
                </a>
                <a href="../public/contact.php" class="link-item">
                    <i class="fas fa-envelope"></i>
                    <span>Liên hệ</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

