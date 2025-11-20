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
            
            <style>
                .flip-card-container {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: <?php echo GRID_GAP; ?>;
                    perspective: 1000px;
                }
                
                .flip-card {
                    height: 300px;
                    perspective: 1000px;
                    cursor: pointer;
                }
                
                .flip-card-inner {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    text-align: center;
                    transition: transform 0.8s;
                    transform-style: preserve-3d;
                }
                
                .flip-card:hover .flip-card-inner {
                    transform: rotateY(180deg);
                }
                
                .flip-card-front, .flip-card-back {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    -webkit-backface-visibility: hidden;
                    backface-visibility: hidden;
                    border-radius: 10px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    padding: 35px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                }
                
                .flip-card-front {
                    background: linear-gradient(135deg, var(--primary) 0%, #4a7a4a 100%);
                    color: var(--white);
                }
                
                .flip-card-back {
                    background: linear-gradient(135deg, var(--primary) 0%, #4a7a4a 100%);
                    color: var(--white);
                    transform: rotateY(180deg);
                }
                
                .flip-card-front h3, .flip-card-back h3 {
                    font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
                    font-weight: 700;
                    font-size: 24px;
                    color: var(--white);
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                }
                
                .flip-card-front p, .flip-card-back p {
                    font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
                    font-weight: 400;
                    font-size: 15px;
                    line-height: 1.8;
                    color: rgba(255,255,255,0.95);
                    margin: 0;
                }
                
                .flip-card-front .icon {
                    margin-right: 10px;
                    color: var(--secondary);
                    font-size: 28px;
                }
                
                .flip-card-back .icon {
                    margin-right: 10px;
                    color: var(--secondary);
                    font-size: 28px;
                }
                
                /* Card thứ hai - Sứ mệnh */
                .flip-card.mission .flip-card-front,
                .flip-card.mission .flip-card-back {
                    background: linear-gradient(135deg, var(--dark) 0%, #8a5a4a 100%);
                }
            </style>
            
            <div class="flip-card-container">
                <!-- Tầm nhìn -->
                <div class="flip-card">
                    <div class="flip-card-inner">
                        <div class="flip-card-front">
                            <h3>
                                <i class="fas fa-eye icon"></i>
                                Tầm nhìn
                            </h3>
                            <p>Nhấp chuột để xem chi tiết</p>
                        </div>
                        <div class="flip-card-back">
                            <h3>
                                <i class="fas fa-eye icon"></i>
                                Tầm nhìn
                            </h3>
                            <p>
                                Công ty Cổ phần Nông nghiệp Sinh thái GrowHope, là xây dựng, phát triển và nhân rộng mô hình Làng du lịch đặc sản sinh thái – nơi đồng hành cùng bà con nông dân làm ra nông sản ngon, lành mạnh và đáng tin cậy – nơi đồng hành cùng người tiêu dùng, cam kết chất lượng và sự minh bạch của nông sản – nơi đất, nước, môi trường, hệ sinh thái được trả lại sự trong sạch, cân bằng vốn có.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Sứ mệnh -->
                <div class="flip-card mission">
                    <div class="flip-card-inner">
                        <div class="flip-card-front">
                            <h3>
                                <i class="fas fa-bullseye icon"></i>
                                Sứ mệnh
                            </h3>
                            <p>Nhấp chuột để xem chi tiết</p>
                        </div>
                        <div class="flip-card-back">
                            <h3>
                                <i class="fas fa-bullseye icon"></i>
                                Sứ mệnh
                            </h3>
                            <p>
                                Gìn giữ, bảo tồn hương vị nguyên bản của nông sản đặc sản quê hương – trên con đường chinh phục thị trường quốc tế cũng như hướng tới mục tiêu – một đất nước của nền Nông nghiệp Sinh thái.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Đội ngũ chúng tôi -->
        <section style="margin-bottom: 60px;">
            <h2 style="font-family: '<?php echo FONT_FAMILY; ?>', sans-serif; font-weight: 700; font-size: 32px; color: var(--primary); margin-bottom: 30px; text-align: center; position: relative; padding-bottom: 20px;">
                Đội ngũ phát triển website GROWHOPE
                <span style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 100px; height: 3px; background: var(--secondary);"></span>
            </h2>
            
            <style>
                .team-container {
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: center;
                    grid-template-columns: repeat(3, minmax(260px, 1fr));
                    gap: 30px;
                    justify-items: center;
                    align-items: stretch;
                }

                .team-container .team-member-card { width: 100%;}

                .team-container .team-member-card:nth-child(4) { grid-column: 1; grid-row: 2; }
                .team-container .team-member-card:nth-child(5) { grid-column: 3; grid-row: 2; }

                @media (max-width: 992px) {
                    .team-container { grid-template-columns: repeat(2, minmax(260px, 1fr)); }
                    .team-container .team-member-card:nth-child(4),
                    .team-container .team-member-card:nth-child(5) { grid-column: auto; grid-row: auto; }
                }
                @media (max-width: 600px) {
                    .team-container { grid-template-columns: 1fr; }
                }

                .team-member-card {
                    height: 300px;
                    position: relative;
                    perspective: 1000px;
                    cursor: pointer;
                    width: 300px;            
                    max-width: 30%;
                    margin-bottom: 20px;
                }

                .team-member-card-inner {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    text-align: center;
                    transition: transform 0.8s;
                    transform-style: preserve-3d;
                }

                .team-member-card:hover .team-member-card-inner {
                    transform: rotateY(180deg);
                }

                .team-member-card-front, .team-member-card-back {
                    position: absolute;
                    top: 0; left: 0;
                    width: 100%;
                    height: 100%;
                    -webkit-backface-visibility: hidden;
                    backface-visibility: hidden;
                    border-radius: 15px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    padding: 30px;
                    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
                    box-sizing: border-box;
                }

                .team-member-card-front {
                    background: var(--white);
                    border: 1px solid rgba(0,0,0,0.08);
                    color: var(--dark);
                }

                .team-member-card-back {
                    background: var(--white);
                    border: 1px solid rgba(0,0,0,0.08);
                    color: var(--dark);
                    transform: rotateY(180deg);
                }

                .member-name {
                    font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
                    font-weight: 700;
                    font-size: 22px;
                    color: var(--primary);
                    margin: 0;
                }

                .member-role {
                    font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
                    font-weight: 500;
                    font-size: 18px;
                    color: var(--secondary);
                    margin: 0;
                    text-align: center;
                }

                .member-photo {
                    width: 309px;
                    height: 300px;
                    object-fit: cover;
                    object-position: top center;
                    border-radius: 12px;
                    background: #f5f7f9;
                    border: 1px dashed rgba(0,0,0,0.12);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--secondary);
                    font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
                    font-size: 14px;
                }
                .member-photo img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    object-position: 50% 20%;
                    border-radius: 12px;
                    display: block;
                }
            </style>
            
            <div class="team-container">
                <!-- Thành viên 1 -->
                <div class="team-member-card">
                    <div class="team-member-card-inner">
                        <div class="team-member-card-front">
                            <div class="member-photo"><img src="<?php echo BASE_URL; ?>/assets/about_us/nam.png" alt="Nguyễn Văn Hoàng Nam"></div>
                        </div>
                        <div class="team-member-card-back">
                            <h3 class="member-name">Nguyễn Văn Hoàng Nam</h3>
                            <p class="member-role"> Coder/Designer/Bố leader/ Co-founder </p>
                        </div>
                    </div>
                </div>

                <!-- Thành viên 2 -->
                <div class="team-member-card">
                    <div class="team-member-card-inner">
                        <div class="team-member-card-front">
                            <div class="member-photo"><img src="<?php echo BASE_URL; ?>/assets/about_us/nhat.png" alt="Lê Vũ Bảo Nhật"></div>
                        </div>
                        <div class="team-member-card-back">
                            <h3 class="member-name">Lê Vũ Bảo Nhật</h3>
                            <p class="member-role"> Leader/Founder </p>
                        </div>
                    </div>
                </div>

                <!-- Thành viên 3 -->
                <div class="team-member-card">
                    <div class="team-member-card-inner">
                        <div class="team-member-card-front">
                            <div class="member-photo"><img src="<?php echo BASE_URL; ?>/assets/about_us/nhung.png" alt="Nguyễn Thị Nhung"></div>
                        </div>
                        <div class="team-member-card-back">
                            <h3 class="member-name">Nguyễn Thị Nhung</h3>
                            <p class="member-role"> Coder/Designer/Vợ leader </p>
                        </div>
                    </div>
                </div>

                <!-- Thành viên 4 -->
                <div class="team-member-card">
                    <div class="team-member-card-inner">
                        <div class="team-member-card-front">
                            <div class="member-photo"><img src="<?php echo BASE_URL; ?>/assets/about_us/thu.png" alt="Trần Phương Thư"></div>
                        </div>
                        <div class="team-member-card-back">
                            <h3 class="member-name">Trần Phương Thư</h3>
                            <p class="member-role">Coder/Designer/Powerpoint producer</p>
                        </div>
                    </div>
                </div>
                <div class="team-member-card">
                    <div class="team-member-card-inner">
                        <div class="team-member-card-front">
                            <div class="member-photo"><img src="<?php echo BASE_URL; ?>/assets/about_us/trang.png" alt="Trần Thị Thùy Trang"></div>
                        </div>
                        <div class="team-member-card-back">
                            <h3 class="member-name">Trần Thị Thùy Trang</h3>
                            <p class="member-role"> Coder/ Designer/Chị Leader </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
$cta_heading = 'Bạn muốn tìm hiểu thêm về chúng tôi?';
$cta_description = 'Hãy liên hệ với chúng tôi để biết thêm về các dự án và cách bạn có thể tham gia vào hành trình phủ xanh Trái Đất.';
$cta_button_text = 'Liên hệ ngay';
$cta_button_link = BASE_URL . '/public/contact.php';
include '../includes/components/cta-section.php';
?>
<?php include '../includes/footer.php'; ?>

