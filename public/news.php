<?php include '../includes/header.php'; ?>

<style>
        /* News Page Styles */
        .news-container {
            background-color: var(--light);
            padding: 40px 5%;
            min-height: 80vh;
        }

        .news-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .news-header h1 {
            font-size: 36px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .news-header p {
            color: var(--dark);
            font-size: 16px;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-bottom: 50px;
        }

        .news-card {
            background-color: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .news-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 48px;
        }

        .news-content {
            padding: 20px;
        }

        .news-date {
            color: var(--secondary);
            font-size: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .news-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            line-height: 1.4;
            min-height: 50px;
        }

        .news-excerpt {
            color: var(--dark);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .news-full-content {
            color: var(--dark);
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .news-read-more {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }

        .news-read-more:hover {
            color: var(--primary);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            background-color: var(--white);
            color: var(--dark);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .pagination a:hover {
            background-color: var(--secondary);
            color: var(--white);
            border-color: var(--secondary);
        }

        .pagination .current {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .news-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 992px) {
            .news-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 576px) {
            .news-grid {
                grid-template-columns: 1fr;
            }

            .news-header h1 {
                font-size: 28px;
            }
        }
    </style>

    <!-- News Content -->
    <div class="news-container">
        <div class="news-header">
            <h1>Tin tức</h1>
            <p>Cập nhật những thông tin mới nhất về môi trường và trồng cây gây rừng</p>
        </div>

        <?php
        // Dữ liệu mẫu tin tức
        $all_news = [
            [
                'id' => 1,
                'title' => 'Chương trình trồng 1 triệu cây xanh năm 2024',
                'date' => '2024-01-15',
                'excerpt' => 'Chương trình trồng cây quy mô lớn nhằm phủ xanh các khu vực đô thị và nông thôn, góp phần cải thiện chất lượng không khí và môi trường sống.',
                'content' => 'Chương trình trồng 1 triệu cây xanh năm 2024 đã được khởi động với sự tham gia của hàng nghìn tình nguyện viên trên khắp cả nước. Chương trình tập trung vào việc trồng các loại cây bản địa phù hợp với điều kiện khí hậu và đất đai của từng vùng. Mục tiêu của chương trình không chỉ là trồng cây mà còn đảm bảo tỷ lệ sống sót cao và phát triển bền vững. Các chuyên gia môi trường đã tham gia tư vấn và giám sát quá trình thực hiện để đảm bảo hiệu quả tối đa.'
            ],
            [
                'id' => 2,
                'title' => 'Kỹ thuật trồng cây ăn quả hiệu quả',
                'date' => '2024-01-12',
                'excerpt' => 'Hướng dẫn chi tiết về cách trồng và chăm sóc cây ăn quả để đạt năng suất cao, bao gồm các bước từ chọn giống đến thu hoạch.',
                'content' => 'Trồng cây ăn quả là một trong những phương pháp hiệu quả để vừa tạo ra giá trị kinh tế vừa góp phần bảo vệ môi trường. Để đạt được thành công, người trồng cần nắm vững các kỹ thuật cơ bản như chọn giống phù hợp, chuẩn bị đất trồng, bón phân đúng cách và tưới nước hợp lý. Ngoài ra, việc phòng trừ sâu bệnh và cắt tỉa cây định kỳ cũng rất quan trọng. Với sự hỗ trợ của công nghệ hiện đại và kiến thức truyền thống, người nông dân có thể tăng năng suất và chất lượng sản phẩm một cách đáng kể.'
            ],
            [
                'id' => 3,
                'title' => 'Tác động tích cực của rừng đến biến đổi khí hậu',
                'date' => '2024-01-10',
                'excerpt' => 'Nghiên cứu mới cho thấy rừng đóng vai trò quan trọng trong việc giảm thiểu tác động của biến đổi khí hậu thông qua việc hấp thụ CO2.',
                'content' => 'Rừng được coi là lá phổi xanh của Trái Đất, đóng vai trò cực kỳ quan trọng trong việc điều hòa khí hậu. Thông qua quá trình quang hợp, cây xanh hấp thụ carbon dioxide từ khí quyển và giải phóng oxy, giúp giảm lượng khí nhà kính. Ngoài ra, rừng còn có khả năng điều hòa nhiệt độ, giữ nước và ngăn chặn xói mòn đất. Các nghiên cứu khoa học đã chứng minh rằng việc bảo vệ và mở rộng diện tích rừng là một trong những giải pháp hiệu quả nhất để chống lại biến đổi khí hậu. Do đó, việc trồng cây gây rừng không chỉ là trách nhiệm mà còn là cơ hội để chúng ta góp phần bảo vệ hành tinh.'
            ],
            [
                'id' => 4,
                'title' => 'Hướng dẫn chọn cây giống chất lượng',
                'date' => '2024-01-08',
                'excerpt' => 'Những tiêu chí quan trọng khi chọn cây giống để đảm bảo tỷ lệ sống sót cao và phát triển tốt, giúp tiết kiệm chi phí và thời gian.',
                'content' => 'Việc chọn cây giống chất lượng là bước đầu tiên và quan trọng nhất trong quá trình trồng cây. Một cây giống tốt sẽ có khả năng sinh trưởng nhanh, kháng bệnh tốt và cho năng suất cao. Khi chọn cây giống, bạn cần chú ý đến các yếu tố như: cây phải khỏe mạnh, không có dấu hiệu sâu bệnh, rễ phát triển tốt và không bị tổn thương. Ngoài ra, nên chọn cây giống từ các nhà cung cấp uy tín, có giấy chứng nhận chất lượng. Việc đầu tư vào cây giống chất lượng sẽ giúp bạn tiết kiệm được nhiều chi phí và công sức trong quá trình chăm sóc sau này.'
            ],
            [
                'id' => 5,
                'title' => 'Phương pháp tưới nước tiết kiệm cho cây trồng',
                'date' => '2024-01-05',
                'excerpt' => 'Các kỹ thuật tưới nước thông minh giúp tiết kiệm nước mà vẫn đảm bảo cây phát triển tốt, phù hợp với điều kiện khí hậu khô hạn.',
                'content' => 'Tưới nước là một trong những yếu tố quan trọng nhất trong việc chăm sóc cây trồng. Tuy nhiên, việc tưới nước không đúng cách không chỉ lãng phí tài nguyên mà còn có thể gây hại cho cây. Các phương pháp tưới nước tiết kiệm như tưới nhỏ giọt, tưới phun sương hoặc tưới theo chu kỳ đã được chứng minh là hiệu quả hơn nhiều so với tưới truyền thống. Những phương pháp này giúp cung cấp nước trực tiếp đến rễ cây, giảm thiểu sự bay hơi và đảm bảo cây nhận được đủ lượng nước cần thiết. Ngoài ra, việc sử dụng hệ thống tưới tự động với cảm biến độ ẩm đất cũng là một giải pháp thông minh để tối ưu hóa việc sử dụng nước.'
            ],
            [
                'id' => 6,
                'title' => 'Lợi ích của việc trồng cây trong đô thị',
                'date' => '2024-01-03',
                'excerpt' => 'Cây xanh trong đô thị không chỉ làm đẹp cảnh quan mà còn mang lại nhiều lợi ích về sức khỏe và môi trường cho cư dân thành phố.',
                'content' => 'Trồng cây trong đô thị đang trở thành xu hướng phổ biến trên toàn thế giới nhờ những lợi ích to lớn mà nó mang lại. Cây xanh giúp lọc không khí, giảm ô nhiễm tiếng ồn và điều hòa nhiệt độ, tạo ra môi trường sống trong lành hơn cho cư dân. Ngoài ra, không gian xanh còn có tác dụng tích cực đến sức khỏe tinh thần, giúp giảm căng thẳng và cải thiện chất lượng cuộc sống. Các nghiên cứu đã chỉ ra rằng những khu vực có nhiều cây xanh thường có tỷ lệ mắc bệnh về đường hô hấp thấp hơn và người dân cảm thấy hạnh phúc hơn. Do đó, việc phát triển không gian xanh trong đô thị là một khoản đầu tư đáng giá cho tương lai.'
            ],
            [
                'id' => 7,
                'title' => 'Công nghệ mới trong nông nghiệp bền vững',
                'date' => '2024-01-01',
                'excerpt' => 'Ứng dụng công nghệ hiện đại như IoT, AI và cảm biến thông minh trong nông nghiệp để tối ưu hóa sản xuất và bảo vệ môi trường.',
                'content' => 'Nông nghiệp bền vững đang được cách mạng hóa bởi các công nghệ tiên tiến. Internet of Things (IoT) cho phép nông dân theo dõi điều kiện đất, nước và khí hậu theo thời gian thực thông qua các cảm biến thông minh. Trí tuệ nhân tạo (AI) giúp phân tích dữ liệu và đưa ra các khuyến nghị về thời điểm gieo trồng, tưới tiêu và thu hoạch tối ưu. Các hệ thống tự động hóa giúp giảm thiểu sử dụng thuốc trừ sâu và phân bón hóa học, góp phần bảo vệ môi trường. Những công nghệ này không chỉ tăng năng suất mà còn giúp nông dân làm việc hiệu quả hơn và giảm chi phí sản xuất.'
            ],
            [
                'id' => 8,
                'title' => 'Bảo tồn đa dạng sinh học thông qua trồng cây',
                'date' => '2023-12-28',
                'excerpt' => 'Trồng cây bản địa và tạo môi trường sống tự nhiên giúp bảo tồn các loài động thực vật quý hiếm và duy trì cân bằng sinh thái.',
                'content' => 'Đa dạng sinh học là nền tảng của sự sống trên Trái Đất, và việc bảo tồn nó là trách nhiệm của tất cả chúng ta. Trồng cây bản địa là một trong những cách hiệu quả nhất để bảo tồn đa dạng sinh học vì những loài cây này đã thích nghi với điều kiện địa phương và cung cấp môi trường sống cho nhiều loài động vật. Khi chúng ta trồng các loài cây đa dạng, chúng ta không chỉ tạo ra không gian xanh mà còn tạo ra một hệ sinh thái phong phú với nhiều loài chim, côn trùng và động vật nhỏ. Việc bảo tồn đa dạng sinh học thông qua trồng cây không chỉ có lợi cho môi trường mà còn mang lại giá trị kinh tế và văn hóa cho cộng đồng.'
            ],
            [
                'id' => 9,
                'title' => 'Chương trình giáo dục môi trường cho trẻ em',
                'date' => '2023-12-25',
                'excerpt' => 'Dạy trẻ em về tầm quan trọng của cây xanh và môi trường từ nhỏ để hình thành ý thức bảo vệ thiên nhiên cho thế hệ tương lai.',
                'content' => 'Giáo dục môi trường cho trẻ em là một khoản đầu tư quan trọng cho tương lai của hành tinh. Khi trẻ em được học về tầm quan trọng của cây xanh và môi trường từ nhỏ, chúng sẽ phát triển ý thức bảo vệ thiên nhiên và có trách nhiệm hơn với môi trường sống. Các chương trình giáo dục môi trường thường bao gồm các hoạt động thực tế như trồng cây, chăm sóc vườn trường và tham quan các khu bảo tồn thiên nhiên. Những trải nghiệm này giúp trẻ em hiểu rõ hơn về mối liên hệ giữa con người và thiên nhiên, từ đó hình thành thói quen sống xanh và bền vững. Việc giáo dục môi trường không chỉ giúp bảo vệ hành tinh mà còn phát triển kỹ năng sống và nhận thức xã hội cho trẻ em.'
            ],
            [
                'id' => 10,
                'title' => 'Tác động của rừng ngập mặn đến môi trường biển',
                'date' => '2023-12-22',
                'excerpt' => 'Rừng ngập mặn đóng vai trò quan trọng trong việc bảo vệ bờ biển, lọc nước và là nơi sinh sống của nhiều loài sinh vật biển quý hiếm.',
                'content' => 'Rừng ngập mặn là một trong những hệ sinh thái quan trọng nhất trên Trái Đất, đóng vai trò như một lá chắn tự nhiên bảo vệ bờ biển khỏi sóng biển và bão tố. Hệ thống rễ phức tạp của cây ngập mặn giúp giữ đất, ngăn chặn xói mòn và ổn định bờ biển. Ngoài ra, rừng ngập mặn còn có khả năng lọc nước, loại bỏ các chất ô nhiễm và cung cấp môi trường sống cho nhiều loài cá, tôm và các sinh vật biển khác. Tuy nhiên, rừng ngập mặn đang bị đe dọa nghiêm trọng bởi các hoạt động của con người như phá rừng, nuôi trồng thủy sản và phát triển đô thị. Việc bảo vệ và phục hồi rừng ngập mặn là cực kỳ quan trọng để duy trì sự cân bằng của hệ sinh thái biển.'
            ],
            [
                'id' => 11,
                'title' => 'Kinh nghiệm trồng cây từ các chuyên gia',
                'date' => '2023-12-20',
                'excerpt' => 'Chia sẻ những kinh nghiệm quý báu từ các chuyên gia nông nghiệp và môi trường về cách trồng và chăm sóc cây hiệu quả nhất.',
                'content' => 'Kinh nghiệm từ các chuyên gia là một nguồn tài nguyên vô giá cho những người mới bắt đầu trồng cây. Các chuyên gia đã tích lũy được nhiều kiến thức và kinh nghiệm thực tế qua nhiều năm làm việc, và việc học hỏi từ họ có thể giúp bạn tránh được nhiều sai lầm phổ biến. Một số nguyên tắc quan trọng mà các chuyên gia thường nhấn mạnh bao gồm: hiểu rõ đặc tính của từng loại cây, chuẩn bị đất trồng kỹ lưỡng, tưới nước đúng cách và theo dõi sức khỏe của cây thường xuyên. Ngoài ra, các chuyên gia cũng khuyến khích việc học hỏi từ thực tế và không ngừng cập nhật kiến thức mới. Việc tham gia các khóa học, hội thảo và kết nối với cộng đồng người trồng cây cũng là những cách tuyệt vời để học hỏi và chia sẻ kinh nghiệm.'
            ],
            [
                'id' => 12,
                'title' => 'Tầm quan trọng của việc bảo vệ rừng đầu nguồn',
                'date' => '2023-12-18',
                'excerpt' => 'Rừng đầu nguồn có vai trò quan trọng trong việc điều tiết nước, ngăn chặn lũ lụt và bảo vệ nguồn nước cho các khu vực hạ lưu.',
                'content' => 'Rừng đầu nguồn là những khu rừng nằm ở vùng thượng lưu của các con sông, đóng vai trò cực kỳ quan trọng trong việc điều tiết nước và bảo vệ môi trường. Hệ thống rễ cây giúp giữ đất, ngăn chặn xói mòn và sạt lở đất, trong khi lớp thảm thực vật giúp hấp thụ và giữ nước mưa, làm chậm dòng chảy và giảm nguy cơ lũ lụt. Ngoài ra, rừng đầu nguồn còn có tác dụng lọc nước tự nhiên, loại bỏ các chất ô nhiễm và cung cấp nguồn nước sạch cho các khu vực hạ lưu. Tuy nhiên, rừng đầu nguồn đang bị đe dọa nghiêm trọng bởi nạn phá rừng và khai thác gỗ trái phép. Việc bảo vệ và phục hồi rừng đầu nguồn là một nhiệm vụ cấp thiết để đảm bảo an ninh nguồn nước và giảm thiểu thiệt hại do thiên tai.'
            ]
        ];

        // Phân trang
        $items_per_page = 8;
        $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $total_items = count($all_news);
        $total_pages = ceil($total_items / $items_per_page);
        $current_page = min($current_page, $total_pages);
        $start_index = ($current_page - 1) * $items_per_page;
        $news = array_slice($all_news, $start_index, $items_per_page);
        ?>

        <div class="news-grid">
            <?php foreach ($news as $item): ?>
            <article class="news-card">
                <div class="news-image">
                    <i class="fas fa-leaf"></i>
                </div>
                <div class="news-content">
                    <div class="news-date">
                        <i class="far fa-calendar"></i>
                        <?php echo date('d/m/Y', strtotime($item['date'])); ?>
                    </div>
                    <h2 class="news-title"><?php echo htmlspecialchars($item['title']); ?></h2>
                    <div class="news-excerpt">
                        <?php echo htmlspecialchars($item['excerpt']); ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="disabled">
                    <i class="fas fa-chevron-left"></i>
                </span>
            <?php endif; ?>

            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1): ?>
                <a href="?page=1">1</a>
                <?php if ($start_page > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $current_page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled">
                    <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

<?php include '../includes/footer.php'; ?>

