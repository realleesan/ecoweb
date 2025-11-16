<?php 
require_once '../includes/config.php';
include '../includes/header.php'; 
?>

<!-- Main Content -->
<main style="min-height: 60vh; padding: 0; background-color: var(--light);">
    <?php
    $page_title = "Giới Thiệu";
    include __DIR__ . '/../includes/components/page-header.php';
    ?>
    
    <div style="max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 0 auto; padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; padding-top: 20px;">
        
        <!-- Câu chuyện GrowHope -->
        <section style="margin-bottom: 60px;">
            <h2 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 32px; color: var(--primary); margin-bottom: 30px; text-align: center; position: relative; padding-bottom: 20px;">
                Câu chuyện <?php echo BRAND_NAME; ?>
                <span style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 100px; height: 3px; background: var(--secondary);"></span>
            </h2>
            <div style="background-color: #f8f9fa; padding: 40px; border-radius: 10px; border-left: 5px solid var(--secondary);">
                <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 16px; line-height: 1.8; color: var(--dark); text-align: justify; margin: 0;">
                    Công ty Cổ phần GrowHope là doanh nghiệp tạo tác động xã hội. Chúng tôi mong muốn tìm ra một phương pháp mới để đồng hành, hỗ trợ những người nông dân – làm sao để họ có thể sống tốt bằng nghề của mình, trên chính mảnh đất quê hương mình. Đồng thời, hướng tới mục tiêu chung – một nền nông nghiệp sinh thái, vì sức khỏe người tiêu dùng và môi trường bền vững.
                </p>
            </div>
        </section>

        <!-- Tầm nhìn & Sứ mệnh -->
        <section style="margin-bottom: 60px;">
            <h2 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 32px; color: var(--primary); margin-bottom: 30px; text-align: center; position: relative; padding-bottom: 20px;">
                Tầm nhìn & Sứ mệnh
                <span style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 100px; height: 3px; background: var(--secondary);"></span>
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: <?php echo GRID_GAP; ?>;">
                <div style="background: linear-gradient(135deg, var(--primary) 0%, #4a7a4a 100%); padding: 35px; border-radius: 10px; color: var(--white); box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 24px; color: var(--white); margin-bottom: 20px; display: flex; align-items: center;">
                        <i class="fas fa-eye" style="margin-right: 10px; color: var(--secondary);"></i>
                        Tầm nhìn
                    </h3>
                    <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 15px; line-height: 1.8; color: rgba(255,255,255,0.95); margin: 0;">
                        Công ty Cổ phần Nông nghiệp Sinh thái GrowHope, là xây dựng, phát triển và nhân rộng mô hình Làng du lịch đặc sản sinh thái – nơi đồng hành cùng bà con nông dân làm ra nông sản ngon, lành mạnh và đáng tin cậy – nơi đồng hành cùng người tiêu dùng, cam kết chất lượng và sự minh bạch của nông sản – nơi đất, nước, môi trường, hệ sinh thái được trả lại sự trong sạch, cân bằng vốn có.
                    </p>
                </div>
                <div style="background: linear-gradient(135deg, var(--dark) 0%, #8a5a4a 100%); padding: 35px; border-radius: 10px; color: var(--white); box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 24px; color: var(--white); margin-bottom: 20px; display: flex; align-items: center;">
                        <i class="fas fa-bullseye" style="margin-right: 10px; color: var(--secondary);"></i>
                        Sứ mệnh
                    </h3>
                    <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 15px; line-height: 1.8; color: rgba(255,255,255,0.95); margin: 0;">
                        Gìn giữ, bảo tồn hương vị nguyên bản của nông sản đặc sản quê hương – trên con đường chinh phục thị trường quốc tế cũng như hướng tới mục tiêu – một đất nước của nền Nông nghiệp Sinh thái.
                    </p>
                </div>
            </div>
        </section>

        <!-- Giá trị cốt lõi -->
        <section style="margin-bottom: 60px;">
            <h2 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 32px; color: var(--primary); margin-bottom: 30px; text-align: center; position: relative; padding-bottom: 20px;">
                Giá trị cốt lõi
                <span style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 100px; height: 3px; background: var(--secondary);"></span>
            </h2>
            
            <!-- Mục tiêu chính -->
            <div style="background: linear-gradient(135deg, var(--secondary) 0%, #e87a3a 100%); padding: 30px; border-radius: 10px; margin-bottom: 30px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 18px; line-height: 1.8; color: var(--white); margin: 0;">
                    Trở thành thương hiệu đẳng cấp quốc tế trong ngành thực phẩm và đồ uống, nơi mọi người đặt trọn niềm tin vào các sản phẩm dinh dưỡng và sức khỏe.
                </p>
            </div>

            <!-- Các giá trị -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: <?php echo GRID_GAP_SMALL; ?>;">
                <div style="background-color: var(--white); padding: 30px; border-radius: 10px; border: 2px solid var(--primary); box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(0,0,0,0.1)'">
                    <div style="width: 60px; height: 60px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-shield-alt" style="font-size: 28px; color: var(--white);"></i>
                    </div>
                    <h3 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 20px; color: var(--primary); margin-bottom: 15px; text-align: center;">
                        Thanh liêm
                    </h3>
                    <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 15px; line-height: 1.7; color: var(--dark); text-align: center; margin: 0;">
                        Chính trực và minh bạch trong các hành động và giao dịch.
                    </p>
                </div>

                <div style="background-color: var(--white); padding: 30px; border-radius: 10px; border: 2px solid var(--primary); box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(0,0,0,0.1)'">
                    <div style="width: 60px; height: 60px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-handshake" style="font-size: 28px; color: var(--white);"></i>
                    </div>
                    <h3 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 20px; color: var(--primary); margin-bottom: 15px; text-align: center;">
                        Kính trọng
                    </h3>
                    <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 15px; line-height: 1.7; color: var(--dark); text-align: center; margin: 0;">
                        Có tự trọng, tôn trọng đồng nghiệp. Tôn trọng Công ty và các đối tác. Để hợp tác với sự tôn trọng.
                    </p>
                </div>

                <div style="background-color: var(--white); padding: 30px; border-radius: 10px; border: 2px solid var(--primary); box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(0,0,0,0.1)'">
                    <div style="width: 60px; height: 60px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-balance-scale" style="font-size: 28px; color: var(--white);"></i>
                    </div>
                    <h3 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 20px; color: var(--primary); margin-bottom: 15px; text-align: center;">
                        Công bằng
                    </h3>
                    <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 15px; line-height: 1.7; color: var(--dark); text-align: center; margin: 0;">
                        Công bằng với nhân viên, khách hàng, nhà cung cấp và các bên khác.
                    </p>
                </div>

                <div style="background-color: var(--white); padding: 30px; border-radius: 10px; border: 2px solid var(--primary); box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(0,0,0,0.1)'">
                    <div style="width: 60px; height: 60px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-heart" style="font-size: 28px; color: var(--white);"></i>
                    </div>
                    <h3 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 20px; color: var(--primary); margin-bottom: 15px; text-align: center;">
                        Đạo đức
                    </h3>
                    <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 15px; line-height: 1.7; color: var(--dark); text-align: center; margin: 0;">
                        Tôn trọng các tiêu chuẩn đạo đức đã được thiết lập và hành động phù hợp.
                    </p>
                </div>

                <div style="background-color: var(--white); padding: 30px; border-radius: 10px; border: 2px solid var(--primary); box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(0,0,0,0.1)'">
                    <div style="width: 60px; height: 60px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-check-circle" style="font-size: 28px; color: var(--white);"></i>
                    </div>
                    <h3 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 20px; color: var(--primary); margin-bottom: 15px; text-align: center;">
                        Tuân thủ
                    </h3>
                    <p style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 400; font-size: 15px; line-height: 1.7; color: var(--dark); text-align: center; margin: 0;">
                        Công bằng với nhân viên, khách hàng, nhà cung cấp và các bên khác.
                    </p>
                </div>
            </div>
        </section>

    </div>
</main>

<?php include '../includes/footer.php'; ?>

