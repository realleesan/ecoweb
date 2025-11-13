<?php include '../includes/header.php'; ?>

<style>
    /* News Detail Page Styles */
    .news-detail-container {
        padding: 40px 5%;
        min-height: 80vh;
    }

    .news-detail-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .news-detail-header {
        padding: 40px 40px 30px;
        border-bottom: 1px solid #e0e0e0;
    }

    .news-breadcrumb {
        margin-bottom: 20px;
        font-size: 14px;
        color: var(--dark);
    }

    .news-breadcrumb a {
        color: var(--secondary);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .news-breadcrumb a:hover {
        color: var(--primary);
    }

    .news-breadcrumb span {
        margin: 0 8px;
        color: #999;
    }

    .news-category {
        display: inline-block;
        background-color: var(--primary);
        color: var(--white);
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 15px;
    }

    .news-title {
        font-size: 36px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 20px;
        line-height: 1.3;
    }

    .news-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e0e0e0;
    }

    .news-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--dark);
        font-size: 14px;
    }

    .news-meta-item i {
        color: var(--secondary);
        width: 16px;
    }

    .news-description {
        font-size: 18px;
        color: var(--dark);
        line-height: 1.8;
        font-style: italic;
        padding: 20px;
        background-color: #f8f9fa;
        border-left: 4px solid var(--secondary);
        margin-bottom: 30px;
    }

    .news-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 72px;
        margin-bottom: 30px;
    }

    .news-content {
        padding: 0 40px 40px;
    }

    .news-body {
        font-size: 16px;
        line-height: 1.9;
        color: var(--dark);
        margin-bottom: 30px;
    }

    .news-body p {
        margin-bottom: 20px;
    }

    .news-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e0e0e0;
    }

    .news-tags-label {
        font-weight: 600;
        color: var(--primary);
        margin-right: 10px;
    }

    .news-tag {
        display: inline-block;
        background-color: var(--light);
        color: var(--dark);
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid #e0e0e0;
    }

    .news-tag:hover {
        background-color: var(--secondary);
        color: var(--white);
        border-color: var(--secondary);
    }

    .news-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e0e0e0;
    }

    .news-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background-color: var(--primary);
        color: var(--white);
        text-decoration: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .news-action-btn:hover {
        background-color: var(--secondary);
        transform: translateY(-2px);
    }

    .news-action-btn.secondary {
        background-color: transparent;
        color: var(--primary);
        border: 2px solid var(--primary);
    }

    .news-action-btn.secondary:hover {
        background-color: var(--primary);
        color: var(--white);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .news-detail-header,
        .news-content {
            padding: 30px 20px;
        }

        .news-title {
            font-size: 28px;
        }

        .news-image {
            height: 250px;
            font-size: 48px;
        }

        .news-meta {
            flex-direction: column;
            gap: 10px;
        }

        .news-actions {
            flex-direction: column;
        }

        .news-action-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php
// Dữ liệu mẫu tin tức (giống như trong news.php nhưng có thêm thông tin chi tiết)
$all_news = [
    [
        'id' => 1,
        'title' => 'Chương trình trồng 1 triệu cây xanh năm 2024',
        'date' => '2024-01-15',
        'author' => 'Nguyễn Văn A',
        'category' => 'Hoạt động',
        'tags' => ['Môi trường', 'Trồng cây', 'Cộng đồng'],
        'excerpt' => 'Chương trình trồng cây quy mô lớn nhằm phủ xanh các khu vực đô thị và nông thôn, góp phần cải thiện chất lượng không khí và môi trường sống.',
        'description' => 'Chương trình trồng 1 triệu cây xanh năm 2024 là một sáng kiến lớn nhằm phủ xanh các khu vực đô thị và nông thôn trên toàn quốc, góp phần cải thiện chất lượng không khí và môi trường sống cho người dân.',
        'content' => 'Chương trình trồng 1 triệu cây xanh năm 2024 đã được khởi động với sự tham gia của hàng nghìn tình nguyện viên trên khắp cả nước. Chương trình tập trung vào việc trồng các loại cây bản địa phù hợp với điều kiện khí hậu và đất đai của từng vùng. Mục tiêu của chương trình không chỉ là trồng cây mà còn đảm bảo tỷ lệ sống sót cao và phát triển bền vững. Các chuyên gia môi trường đã tham gia tư vấn và giám sát quá trình thực hiện để đảm bảo hiệu quả tối đa.<br><br>Chương trình được chia thành nhiều giai đoạn, mỗi giai đoạn tập trung vào một khu vực cụ thể. Các tình nguyện viên được đào tạo về kỹ thuật trồng cây và chăm sóc cây non để đảm bảo tỷ lệ sống sót cao nhất. Ngoài ra, chương trình còn có sự hỗ trợ từ các tổ chức môi trường và chính quyền địa phương.'
    ],
    [
        'id' => 2,
        'title' => 'Kỹ thuật trồng cây ăn quả hiệu quả',
        'date' => '2024-01-12',
        'author' => 'Trần Thị B',
        'category' => 'Kỹ thuật',
        'tags' => ['Nông nghiệp', 'Cây ăn quả', 'Kỹ thuật'],
        'excerpt' => 'Hướng dẫn chi tiết về cách trồng và chăm sóc cây ăn quả để đạt năng suất cao, bao gồm các bước từ chọn giống đến thu hoạch.',
        'description' => 'Bài viết này sẽ hướng dẫn bạn các kỹ thuật cơ bản và nâng cao để trồng cây ăn quả hiệu quả, từ việc chọn giống phù hợp đến các phương pháp chăm sóc và thu hoạch.',
        'content' => 'Trồng cây ăn quả là một trong những phương pháp hiệu quả để vừa tạo ra giá trị kinh tế vừa góp phần bảo vệ môi trường. Để đạt được thành công, người trồng cần nắm vững các kỹ thuật cơ bản như chọn giống phù hợp, chuẩn bị đất trồng, bón phân đúng cách và tưới nước hợp lý.<br><br>Ngoài ra, việc phòng trừ sâu bệnh và cắt tỉa cây định kỳ cũng rất quan trọng. Với sự hỗ trợ của công nghệ hiện đại và kiến thức truyền thống, người nông dân có thể tăng năng suất và chất lượng sản phẩm một cách đáng kể.'
    ],
    [
        'id' => 3,
        'title' => 'Tác động tích cực của rừng đến biến đổi khí hậu',
        'date' => '2024-01-10',
        'author' => 'Lê Văn C',
        'category' => 'Nghiên cứu',
        'tags' => ['Biến đổi khí hậu', 'Rừng', 'Môi trường'],
        'excerpt' => 'Nghiên cứu mới cho thấy rừng đóng vai trò quan trọng trong việc giảm thiểu tác động của biến đổi khí hậu thông qua việc hấp thụ CO2.',
        'description' => 'Nghiên cứu khoa học mới nhất cho thấy rừng không chỉ là lá phổi xanh của Trái Đất mà còn là giải pháp quan trọng trong cuộc chiến chống biến đổi khí hậu.',
        'content' => 'Rừng được coi là lá phổi xanh của Trái Đất, đóng vai trò cực kỳ quan trọng trong việc điều hòa khí hậu. Thông qua quá trình quang hợp, cây xanh hấp thụ carbon dioxide từ khí quyển và giải phóng oxy, giúp giảm lượng khí nhà kính.<br><br>Ngoài ra, rừng còn có khả năng điều hòa nhiệt độ, giữ nước và ngăn chặn xói mòn đất. Các nghiên cứu khoa học đã chứng minh rằng việc bảo vệ và mở rộng diện tích rừng là một trong những giải pháp hiệu quả nhất để chống lại biến đổi khí hậu.'
    ],
    [
        'id' => 4,
        'title' => 'Hướng dẫn chọn cây giống chất lượng',
        'date' => '2024-01-08',
        'author' => 'Phạm Thị D',
        'category' => 'Hướng dẫn',
        'tags' => ['Cây giống', 'Nông nghiệp', 'Chất lượng'],
        'excerpt' => 'Những tiêu chí quan trọng khi chọn cây giống để đảm bảo tỷ lệ sống sót cao và phát triển tốt, giúp tiết kiệm chi phí và thời gian.',
        'description' => 'Việc chọn đúng cây giống chất lượng là yếu tố quyết định thành công của việc trồng cây. Bài viết này sẽ giúp bạn hiểu rõ các tiêu chí quan trọng.',
        'content' => 'Việc chọn cây giống chất lượng là bước đầu tiên và quan trọng nhất trong quá trình trồng cây. Một cây giống tốt sẽ có khả năng sinh trưởng nhanh, kháng bệnh tốt và cho năng suất cao.<br><br>Khi chọn cây giống, bạn cần chú ý đến các yếu tố như: cây phải khỏe mạnh, không có dấu hiệu sâu bệnh, rễ phát triển tốt và không bị tổn thương. Ngoài ra, nên chọn cây giống từ các nhà cung cấp uy tín, có giấy chứng nhận chất lượng.'
    ],
    [
        'id' => 5,
        'title' => 'Phương pháp tưới nước tiết kiệm cho cây trồng',
        'date' => '2024-01-05',
        'author' => 'Hoàng Văn E',
        'category' => 'Kỹ thuật',
        'tags' => ['Tưới nước', 'Tiết kiệm', 'Nông nghiệp'],
        'excerpt' => 'Các kỹ thuật tưới nước thông minh giúp tiết kiệm nước mà vẫn đảm bảo cây phát triển tốt, phù hợp với điều kiện khí hậu khô hạn.',
        'description' => 'Tưới nước đúng cách không chỉ giúp cây phát triển tốt mà còn tiết kiệm tài nguyên nước. Tìm hiểu các phương pháp tưới nước hiện đại và hiệu quả.',
        'content' => 'Tưới nước là một trong những yếu tố quan trọng nhất trong việc chăm sóc cây trồng. Tuy nhiên, việc tưới nước không đúng cách không chỉ lãng phí tài nguyên mà còn có thể gây hại cho cây.<br><br>Các phương pháp tưới nước tiết kiệm như tưới nhỏ giọt, tưới phun sương hoặc tưới theo chu kỳ đã được chứng minh là hiệu quả hơn nhiều so với tưới truyền thống.'
    ],
    [
        'id' => 6,
        'title' => 'Lợi ích của việc trồng cây trong đô thị',
        'date' => '2024-01-03',
        'author' => 'Võ Thị F',
        'category' => 'Đô thị',
        'tags' => ['Đô thị', 'Cây xanh', 'Sức khỏe'],
        'excerpt' => 'Cây xanh trong đô thị không chỉ làm đẹp cảnh quan mà còn mang lại nhiều lợi ích về sức khỏe và môi trường cho cư dân thành phố.',
        'description' => 'Không gian xanh trong đô thị đang trở thành xu hướng phổ biến trên toàn thế giới nhờ những lợi ích to lớn mà nó mang lại cho sức khỏe và môi trường.',
        'content' => 'Trồng cây trong đô thị đang trở thành xu hướng phổ biến trên toàn thế giới nhờ những lợi ích to lớn mà nó mang lại. Cây xanh giúp lọc không khí, giảm ô nhiễm tiếng ồn và điều hòa nhiệt độ.<br><br>Ngoài ra, không gian xanh còn có tác dụng tích cực đến sức khỏe tinh thần, giúp giảm căng thẳng và cải thiện chất lượng cuộc sống.'
    ],
    [
        'id' => 7,
        'title' => 'Công nghệ mới trong nông nghiệp bền vững',
        'date' => '2024-01-01',
        'author' => 'Đặng Văn G',
        'category' => 'Công nghệ',
        'tags' => ['Công nghệ', 'IoT', 'AI', 'Nông nghiệp'],
        'excerpt' => 'Ứng dụng công nghệ hiện đại như IoT, AI và cảm biến thông minh trong nông nghiệp để tối ưu hóa sản xuất và bảo vệ môi trường.',
        'description' => 'Nông nghiệp bền vững đang được cách mạng hóa bởi các công nghệ tiên tiến như Internet of Things và Trí tuệ nhân tạo.',
        'content' => 'Nông nghiệp bền vững đang được cách mạng hóa bởi các công nghệ tiên tiến. Internet of Things (IoT) cho phép nông dân theo dõi điều kiện đất, nước và khí hậu theo thời gian thực.<br><br>Trí tuệ nhân tạo (AI) giúp phân tích dữ liệu và đưa ra các khuyến nghị về thời điểm gieo trồng, tưới tiêu và thu hoạch tối ưu.'
    ],
    [
        'id' => 8,
        'title' => 'Bảo tồn đa dạng sinh học thông qua trồng cây',
        'date' => '2023-12-28',
        'author' => 'Bùi Thị H',
        'category' => 'Bảo tồn',
        'tags' => ['Đa dạng sinh học', 'Bảo tồn', 'Môi trường'],
        'excerpt' => 'Trồng cây bản địa và tạo môi trường sống tự nhiên giúp bảo tồn các loài động thực vật quý hiếm và duy trì cân bằng sinh thái.',
        'description' => 'Đa dạng sinh học là nền tảng của sự sống trên Trái Đất. Trồng cây bản địa là cách hiệu quả để bảo tồn hệ sinh thái tự nhiên.',
        'content' => 'Đa dạng sinh học là nền tảng của sự sống trên Trái Đất, và việc bảo tồn nó là trách nhiệm của tất cả chúng ta. Trồng cây bản địa là một trong những cách hiệu quả nhất để bảo tồn đa dạng sinh học.<br><br>Khi chúng ta trồng các loài cây đa dạng, chúng ta không chỉ tạo ra không gian xanh mà còn tạo ra một hệ sinh thái phong phú với nhiều loài chim, côn trùng và động vật nhỏ.'
    ],
    [
        'id' => 9,
        'title' => 'Chương trình giáo dục môi trường cho trẻ em',
        'date' => '2023-12-25',
        'author' => 'Ngô Văn I',
        'category' => 'Giáo dục',
        'tags' => ['Giáo dục', 'Trẻ em', 'Môi trường'],
        'excerpt' => 'Dạy trẻ em về tầm quan trọng của cây xanh và môi trường từ nhỏ để hình thành ý thức bảo vệ thiên nhiên cho thế hệ tương lai.',
        'description' => 'Giáo dục môi trường cho trẻ em là khoản đầu tư quan trọng cho tương lai của hành tinh, giúp hình thành thói quen sống xanh từ nhỏ.',
        'content' => 'Giáo dục môi trường cho trẻ em là một khoản đầu tư quan trọng cho tương lai của hành tinh. Khi trẻ em được học về tầm quan trọng của cây xanh và môi trường từ nhỏ, chúng sẽ phát triển ý thức bảo vệ thiên nhiên.<br><br>Các chương trình giáo dục môi trường thường bao gồm các hoạt động thực tế như trồng cây, chăm sóc vườn trường và tham quan các khu bảo tồn thiên nhiên.'
    ],
    [
        'id' => 10,
        'title' => 'Tác động của rừng ngập mặn đến môi trường biển',
        'date' => '2023-12-22',
        'author' => 'Lý Thị K',
        'category' => 'Nghiên cứu',
        'tags' => ['Rừng ngập mặn', 'Biển', 'Bảo vệ'],
        'excerpt' => 'Rừng ngập mặn đóng vai trò quan trọng trong việc bảo vệ bờ biển, lọc nước và là nơi sinh sống của nhiều loài sinh vật biển quý hiếm.',
        'description' => 'Rừng ngập mặn là một trong những hệ sinh thái quan trọng nhất trên Trái Đất, đóng vai trò như lá chắn tự nhiên bảo vệ bờ biển.',
        'content' => 'Rừng ngập mặn là một trong những hệ sinh thái quan trọng nhất trên Trái Đất, đóng vai trò như một lá chắn tự nhiên bảo vệ bờ biển khỏi sóng biển và bão tố.<br><br>Hệ thống rễ phức tạp của cây ngập mặn giúp giữ đất, ngăn chặn xói mòn và ổn định bờ biển. Ngoài ra, rừng ngập mặn còn có khả năng lọc nước, loại bỏ các chất ô nhiễm.'
    ],
    [
        'id' => 11,
        'title' => 'Kinh nghiệm trồng cây từ các chuyên gia',
        'date' => '2023-12-20',
        'author' => 'Trương Văn L',
        'category' => 'Kinh nghiệm',
        'tags' => ['Kinh nghiệm', 'Chuyên gia', 'Nông nghiệp'],
        'excerpt' => 'Chia sẻ những kinh nghiệm quý báu từ các chuyên gia nông nghiệp và môi trường về cách trồng và chăm sóc cây hiệu quả nhất.',
        'description' => 'Học hỏi từ các chuyên gia là cách tốt nhất để tránh sai lầm và đạt được thành công trong việc trồng cây.',
        'content' => 'Kinh nghiệm từ các chuyên gia là một nguồn tài nguyên vô giá cho những người mới bắt đầu trồng cây. Các chuyên gia đã tích lũy được nhiều kiến thức và kinh nghiệm thực tế qua nhiều năm làm việc.<br><br>Một số nguyên tắc quan trọng mà các chuyên gia thường nhấn mạnh bao gồm: hiểu rõ đặc tính của từng loại cây, chuẩn bị đất trồng kỹ lưỡng, tưới nước đúng cách và theo dõi sức khỏe của cây thường xuyên.'
    ],
    [
        'id' => 12,
        'title' => 'Tầm quan trọng của việc bảo vệ rừng đầu nguồn',
        'date' => '2023-12-18',
        'author' => 'Phan Thị M',
        'category' => 'Bảo vệ',
        'tags' => ['Rừng đầu nguồn', 'Bảo vệ', 'Nước'],
        'excerpt' => 'Rừng đầu nguồn có vai trò quan trọng trong việc điều tiết nước, ngăn chặn lũ lụt và bảo vệ nguồn nước cho các khu vực hạ lưu.',
        'description' => 'Rừng đầu nguồn đóng vai trò cực kỳ quan trọng trong việc điều tiết nước và bảo vệ môi trường, là nguồn sống của các khu vực hạ lưu.',
        'content' => 'Rừng đầu nguồn là những khu rừng nằm ở vùng thượng lưu của các con sông, đóng vai trò cực kỳ quan trọng trong việc điều tiết nước và bảo vệ môi trường.<br><br>Hệ thống rễ cây giúp giữ đất, ngăn chặn xói mòn và sạt lở đất, trong khi lớp thảm thực vật giúp hấp thụ và giữ nước mưa, làm chậm dòng chảy và giảm nguy cơ lũ lụt.'
    ]
];

// Lấy ID từ URL
$news_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Tìm bài viết theo ID
$article = null;
foreach ($all_news as $news_item) {
    if ($news_item['id'] == $news_id) {
        $article = $news_item;
        break;
    }
}

// Nếu không tìm thấy bài viết, chuyển về trang tin tức
if (!$article) {
    header('Location: /ecoweb/public/news.php');
    exit;
}
?>

<div class="news-detail-container">
    <div class="news-detail-wrapper">
        <!-- Header -->
        <div class="news-detail-header">
            <div class="news-breadcrumb">
                <a href="/ecoweb/public/news.php">Tin tức</a>
                <span>/</span>
                <span><?php echo htmlspecialchars($article['title']); ?></span>
            </div>

            <div class="news-category"><?php echo htmlspecialchars($article['category']); ?></div>

            <h1 class="news-title"><?php echo htmlspecialchars($article['title']); ?></h1>

            <div class="news-meta">
                <div class="news-meta-item">
                    <i class="far fa-calendar"></i>
                    <span><?php echo date('d/m/Y', strtotime($article['date'])); ?></span>
                </div>
                <div class="news-meta-item">
                    <i class="far fa-user"></i>
                    <span><?php echo htmlspecialchars($article['author']); ?></span>
                </div>
                <div class="news-meta-item">
                    <i class="far fa-clock"></i>
                    <span><?php echo date('H:i', strtotime($article['date'])); ?></span>
                </div>
            </div>

            <?php if (!empty($article['description'])): ?>
            <div class="news-description">
                <?php echo htmlspecialchars($article['description']); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Image -->
        <div class="news-image">
            <i class="fas fa-leaf"></i>
        </div>

        <!-- Content -->
        <div class="news-content">
            <div class="news-body">
                <?php echo nl2br($article['content']); ?>
            </div>

            <!-- Tags -->
            <?php if (!empty($article['tags'])): ?>
            <div class="news-tags">
                <span class="news-tags-label">Tags:</span>
                <?php foreach ($article['tags'] as $tag): ?>
                    <a href="/ecoweb/public/news.php?tag=<?php echo urlencode($tag); ?>" class="news-tag">
                        <?php echo htmlspecialchars($tag); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="news-actions">
                <a href="/ecoweb/public/news.php" class="news-action-btn secondary">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại danh sách
                </a>
                <a href="#" class="news-action-btn" onclick="window.print(); return false;">
                    <i class="fas fa-print"></i>
                    In bài viết
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

