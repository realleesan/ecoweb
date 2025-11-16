<?php
/**
 * CTA Section Component
 * 
 * Call-to-Action section với thiết kế thống nhất
 * 
 * Sử dụng:
 * $cta_heading = 'Tiêu đề CTA';
 * $cta_description = 'Mô tả CTA';
 * $cta_button_text = 'Text nút';
 * $cta_button_link = BASE_URL . '/public/products.php';
 * include __DIR__ . '/components/cta-section.php';
 */

// Đảm bảo config đã được load
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

// Thiết lập giá trị mặc định
$cta_heading = isset($cta_heading) ? $cta_heading : 'Hãy cùng gieo thêm một mầm xanh cho Trái Đất hôm nay';
$cta_description = isset($cta_description) ? $cta_description : 'Mỗi cây xanh bạn trồng là một đóng góp ý nghĩa cho tương lai của hành tinh. Hãy tham gia cùng chúng tôi trong hành trình phủ xanh Trái Đất!';
$cta_button_text = isset($cta_button_text) ? $cta_button_text : 'Tham gia phủ xanh';
$cta_button_link = isset($cta_button_link) ? $cta_button_link : BASE_URL . '/public/products.php';
?>

<style>
    .cta-section {
        background: linear-gradient(135deg, var(--primary) 0%, var(--bg-green) 100%);
        padding: 80px 20px;
        text-align: center;
        position: relative;
        margin-top: 60px;
    }

    .cta-container {
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        margin: 0 auto;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
    }

    .cta-heading {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 700;
        font-size: 42px;
        color: var(--white);
        margin-bottom: 25px;
        line-height: 1.3;
    }

    .cta-description {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 400;
        font-size: 18px;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 40px;
        line-height: 1.8;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .cta-button {
        display: inline-block;
        padding: 16px 40px;
        background-color: var(--secondary);
        color: var(--white);
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 600;
        font-size: 18px;
        text-decoration: none;
        border-radius: 30px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(210, 100, 38, 0.3);
        border: none;
        cursor: pointer;
    }

    .cta-button:hover {
        background-color: #b8551f;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(210, 100, 38, 0.4);
    }

    .cta-button:active {
        transform: translateY(0);
    }

    .cta-divider {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 8px;
        background-color: var(--dark);
    }

    /* Responsive */
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .cta-section {
            padding: 60px 20px;
        }

        .cta-heading {
            font-size: 32px;
        }

        .cta-description {
            font-size: 16px;
        }

        .cta-button {
            padding: 14px 35px;
            font-size: 16px;
        }
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        .cta-section {
            padding: 50px 15px;
        }

        .cta-heading {
            font-size: 28px;
            margin-bottom: 20px;
        }

        .cta-description {
            font-size: 15px;
            margin-bottom: 30px;
        }

        .cta-button {
            padding: 12px 30px;
            font-size: 15px;
        }
    }
</style>

<section class="cta-section">
    <div class="cta-container">
        <h2 class="cta-heading"><?php echo htmlspecialchars($cta_heading); ?></h2>
        <p class="cta-description"><?php echo htmlspecialchars($cta_description); ?></p>
        <a href="<?php echo htmlspecialchars($cta_button_link); ?>" class="cta-button">
            <?php echo htmlspecialchars($cta_button_text); ?>
        </a>
    </div>
    <div class="cta-divider"></div>
</section>
