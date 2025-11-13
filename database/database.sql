-- Database schema for GrowHope (ECOWEB)
-- Drop existing tables if re-importing (optional)
DROP TABLE IF EXISTS news_tags;
DROP TABLE IF EXISTS product_tags;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS gallery_images;

-- Categories table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    short_description TEXT,
    full_description LONGTEXT,
    stock INT NOT NULL DEFAULT 0,
    rating DECIMAL(3,1) NOT NULL DEFAULT 0,
    reviews_count INT NOT NULL DEFAULT 0,
    is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- News table
CREATE TABLE news (
    news_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    publish_date DATE NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL,
    excerpt TEXT,
    description TEXT,
    content LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product tags table
CREATE TABLE product_tags (
    product_id INT NOT NULL,
    tag VARCHAR(100) NOT NULL,
    PRIMARY KEY (product_id, tag),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- News tags table
CREATE TABLE news_tags (
    news_id INT NOT NULL,
    tag VARCHAR(100) NOT NULL,
    PRIMARY KEY (news_id, tag),
    FOREIGN KEY (news_id) REFERENCES news(news_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gallery images table
CREATE TABLE gallery_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    category VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed categories
INSERT INTO categories (category_name, slug, description, image) VALUES
('Cây Ăn Quả', 'cay-an-qua', 'Các loại cây ăn quả phù hợp với khí hậu Việt Nam, mang lại giá trị kinh tế cao và góp phần bảo vệ môi trường.', 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=C%C3%A2y+%C4%82n+Qu%E1%BA%A3'),
('Cây Lấy Gỗ', 'cay-lay-go', 'Những loại cây lấy gỗ có giá trị kinh tế, sinh trưởng nhanh và thân thiện với môi trường.', 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=C%C3%A2y+L%E1%BA%A5y+G%E1%BB%97'),
('Cây Cảnh Quan', 'cay-canh-quan', 'Các loại cây cảnh quan đẹp mắt, tạo không gian xanh mát và trong lành cho môi trường sống.', 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=C%C3%A2y+C%E1%BA%A3nh+Quan'),
('Cây Thuốc Nam', 'cay-thuoc-nam', 'Những loại cây thuốc nam quý giá, có tác dụng chữa bệnh và bồi bổ sức khỏe.', 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=C%C3%A2y+Thu%E1%BB%91c+Nam'),
('Cây Công Nghiệp', 'cay-cong-nghiep', 'Các loại cây công nghiệp phục vụ sản xuất, mang lại hiệu quả kinh tế cao.', 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=C%C3%A2y+C%C3%B4ng+Nghi%E1%BB%87p'),
('Cây Phong Thủy', 'cay-phong-thuy', 'Những loại cây phong thủy mang lại may mắn, tài lộc và năng lượng tích cực cho không gian.', 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=C%C3%A2y+Phong+Th%E1%BB%A7y'),
('Cây Bóng Mát', 'cay-bong-mat', 'Các loại cây bóng mát lớn, tạo bóng râm và làm mát không gian sống.', 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=C%C3%A2y+B%C3%B3ng+M%C3%A1t'),
('Cây Rừng', 'cay-rung', 'Những loại cây rừng bản địa, góp phần phục hồi và bảo tồn hệ sinh thái rừng.', 'https://via.placeholder.com/300x200/3C603C/FFFFFF?text=C%C3%A2y+R%E1%BB%ABng');

-- Seed products
INSERT INTO products (category_id, code, name, price, short_description, full_description, stock, rating, reviews_count, is_bestseller) VALUES
(1, 'A01', 'Cây Kèn Hồng', 100000, 'Cây Kèn Hồng có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt', 'Cây Kèn Hồng là loại cây cảnh đẹp, có hoa màu hồng rực rỡ. Cây có khả năng tạo bóng mát tốt, giúp thanh lọc không khí và tạo không gian xanh mát cho ngôi nhà của bạn. Cây dễ trồng, phù hợp với nhiều loại đất và khí hậu khác nhau. Ngoài ra, cây còn có tác dụng tốt cho sức khỏe, giúp giảm căng thẳng và tạo không gian thư giãn.', 50, 4.5, 12, 1),
(1, 'A02', 'Cây Hoàng Nam', 200000, 'Cây Hoàng Nam có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt', 'Cây Hoàng Nam là loại cây cảnh quý, có hình dáng đẹp và tán lá xanh mướt. Cây có khả năng tạo bóng mát rất tốt, phù hợp trồng trong sân vườn hoặc công viên. Cây có tuổi thọ cao, dễ chăm sóc và phát triển nhanh.', 30, 4.8, 8, 1),
(1, 'A03', 'Cây Táo', 300000, 'Cây Táo có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt', 'Cây Táo là loại cây ăn quả phổ biến, cho trái ngon và bổ dưỡng. Cây có khả năng tạo bóng mát tốt, phù hợp trồng trong vườn nhà. Trái táo chứa nhiều vitamin và chất xơ, rất tốt cho sức khỏe. Cây dễ trồng, chịu được nhiều loại đất và khí hậu.', 25, 4.7, 15, 0),
(1, 'A04', 'Cây Bưởi', 400000, 'Cây Bưởi có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt', 'Cây Bưởi là loại cây ăn quả có giá trị kinh tế cao. Cây cho trái to, ngon và nhiều nước. Bưởi chứa nhiều vitamin C, rất tốt cho sức khỏe. Cây có tán rộng, tạo bóng mát tốt cho sân vườn.', 20, 4.6, 10, 0),
(1, 'A05', 'Cây Chanh Leo', 500000, 'Cây Chanh Dây có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt', 'Cây Chanh Leo là loại cây leo, cho trái chanh leo thơm ngon và bổ dưỡng. Trái chanh leo chứa nhiều vitamin và chất chống oxy hóa. Cây có thể leo giàn, tạo bóng mát và cho trái quanh năm.', 15, 4.9, 20, 1),
(1, 'A06', 'Cây Xoài', 600000, 'Cây Xoài có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt', 'Cây Xoài là loại cây ăn quả nhiệt đới, cho trái xoài thơm ngon. Cây có tán rộng, tạo bóng mát tốt. Xoài chứa nhiều vitamin A và C, rất tốt cho sức khỏe. Cây phù hợp trồng trong vườn nhà.', 18, 4.5, 14, 0),
(2, 'A07', 'Tổ Ong', 700000, 'Tổ ong có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt', 'Tổ Ong là sản phẩm tự nhiên từ ong mật, chứa nhiều dưỡng chất quý giá. Mật ong từ tổ ong có vị ngọt tự nhiên, chứa nhiều vitamin và khoáng chất. Tổ ong có thể sử dụng để làm thuốc và thực phẩm bổ dưỡng.', 12, 5.0, 25, 0),
(1, 'A08', 'Cây Sung', 800000, 'Cây Sung có rất nhiều tác dụng cho sức khỏe và tạo bóng mát tốt', 'Cây Sung là loại cây cảnh đẹp, có lá xanh mướt và tạo bóng mát tốt. Cây có tuổi thọ cao, dễ chăm sóc. Sung còn có thể cho trái, trái sung có vị ngọt và bổ dưỡng. Cây phù hợp trồng trong sân vườn hoặc công viên.', 22, 4.4, 11, 0);

-- Seed product tags
INSERT INTO product_tags (product_id, tag) VALUES
(1, 'Tuổi đời dài'), (1, 'Cây cảnh'), (1, 'Tạo bóng mát'),
(2, 'Tuổi đời dài'), (2, 'Cây cảnh'), (2, 'Tạo bóng mát'),
(3, 'Cây ăn quả'), (3, 'Tạo bóng mát'), (3, 'Dễ trồng'),
(4, 'Cây ăn quả'), (4, 'Giá trị cao'), (4, 'Tạo bóng mát'),
(5, 'Cây leo'), (5, 'Cây ăn quả'), (5, 'Dễ trồng'),
(6, 'Cây ăn quả'), (6, 'Nhiệt đới'), (6, 'Tạo bóng mát'),
(7, 'Tổ ong'), (7, 'Tự nhiên'), (7, 'Bổ dưỡng'),
(8, 'Tuổi đời dài'), (8, 'Cây cảnh'), (8, 'Cây ăn quả');

-- Seed news articles
INSERT INTO news (title, slug, publish_date, author, category, excerpt, description, content) VALUES
('Chương trình trồng 1 triệu cây xanh năm 2024', 'chuong-trinh-trong-1-trieu-cay-xanh-nam-2024', '2024-01-15', 'Nguyễn Văn A', 'Hoạt động', 'Chương trình trồng cây quy mô lớn nhằm phủ xanh các khu vực đô thị và nông thôn, góp phần cải thiện chất lượng không khí và môi trường sống.', 'Chương trình trồng 1 triệu cây xanh năm 2024 là một sáng kiến lớn nhằm phủ xanh các khu vực đô thị và nông thôn trên toàn quốc, góp phần cải thiện chất lượng không khí và môi trường sống cho người dân.', 'Chương trình trồng 1 triệu cây xanh năm 2024 đã được khởi động với sự tham gia của hàng nghìn tình nguyện viên trên khắp cả nước. Chương trình tập trung vào việc trồng các loại cây bản địa phù hợp với điều kiện khí hậu và đất đai của từng vùng. Mục tiêu của chương trình không chỉ là trồng cây mà còn đảm bảo tỷ lệ sống sót cao và phát triển bền vững. Các chuyên gia môi trường đã tham gia tư vấn và giám sát quá trình thực hiện để đảm bảo hiệu quả tối đa.

Chương trình được chia thành nhiều giai đoạn, mỗi giai đoạn tập trung vào một khu vực cụ thể. Các tình nguyện viên được đào tạo về kỹ thuật trồng cây và chăm sóc cây non để đảm bảo tỷ lệ sống sót cao nhất. Ngoài ra, chương trình còn có sự hỗ trợ từ các tổ chức môi trường và chính quyền địa phương.'),
('Kỹ thuật trồng cây ăn quả hiệu quả', 'ky-thuat-trong-cay-an-qua-hieu-qua', '2024-01-12', 'Trần Thị B', 'Kỹ thuật', 'Hướng dẫn chi tiết về cách trồng và chăm sóc cây ăn quả để đạt năng suất cao, bao gồm các bước từ chọn giống đến thu hoạch.', 'Bài viết này sẽ hướng dẫn bạn các kỹ thuật cơ bản và nâng cao để trồng cây ăn quả hiệu quả, từ việc chọn giống phù hợp đến các phương pháp chăm sóc và thu hoạch.', 'Trồng cây ăn quả là một trong những phương pháp hiệu quả để vừa tạo ra giá trị kinh tế vừa góp phần bảo vệ môi trường. Để đạt được thành công, người trồng cần nắm vững các kỹ thuật cơ bản như chọn giống phù hợp, chuẩn bị đất trồng, bón phân đúng cách và tưới nước hợp lý.

Ngoài ra, việc phòng trừ sâu bệnh và cắt tỉa cây định kỳ cũng rất quan trọng. Với sự hỗ trợ của công nghệ hiện đại và kiến thức truyền thống, người nông dân có thể tăng năng suất và chất lượng sản phẩm một cách đáng kể.'),
('Tác động tích cực của rừng đến biến đổi khí hậu', 'tac-dong-tich-cuc-cua-rung-den-bien-doi-khi-hau', '2024-01-10', 'Lê Văn C', 'Nghiên cứu', 'Nghiên cứu mới cho thấy rừng đóng vai trò quan trọng trong việc giảm thiểu tác động của biến đổi khí hậu thông qua việc hấp thụ CO2.', 'Nghiên cứu khoa học mới nhất cho thấy rừng không chỉ là lá phổi xanh của Trái Đất mà còn là giải pháp quan trọng trong cuộc chiến chống biến đổi khí hậu.', 'Rừng được coi là lá phổi xanh của Trái Đất, đóng vai trò cực kỳ quan trọng trong việc điều hòa khí hậu. Thông qua quá trình quang hợp, cây xanh hấp thụ carbon dioxide từ khí quyển và giải phóng oxy, giúp giảm lượng khí nhà kính.

Ngoài ra, rừng còn có khả năng điều hòa nhiệt độ, giữ nước và ngăn chặn xói mòn đất. Các nghiên cứu khoa học đã chứng minh rằng việc bảo vệ và mở rộng diện tích rừng là một trong những giải pháp hiệu quả nhất để chống lại biến đổi khí hậu.'),
('Hướng dẫn chọn cây giống chất lượng', 'huong-dan-chon-cay-giong-chat-luong', '2024-01-08', 'Phạm Thị D', 'Hướng dẫn', 'Những tiêu chí quan trọng khi chọn cây giống để đảm bảo tỷ lệ sống sót cao và phát triển tốt, giúp tiết kiệm chi phí và thời gian.', 'Việc chọn đúng cây giống chất lượng là yếu tố quyết định thành công của việc trồng cây. Bài viết này sẽ giúp bạn hiểu rõ các tiêu chí quan trọng.', 'Việc chọn cây giống chất lượng là bước đầu tiên và quan trọng nhất trong quá trình trồng cây. Một cây giống tốt sẽ có khả năng sinh trưởng nhanh, kháng bệnh tốt và cho năng suất cao.

Khi chọn cây giống, bạn cần chú ý đến các yếu tố như: cây phải khỏe mạnh, không có dấu hiệu sâu bệnh, rễ phát triển tốt và không bị tổn thương. Ngoài ra, nên chọn cây giống từ các nhà cung cấp uy tín, có giấy chứng nhận chất lượng.'),
('Phương pháp tưới nước tiết kiệm cho cây trồng', 'phuong-phap-tuoi-nuoc-tiet-kiem-cho-cay-trong', '2024-01-05', 'Hoàng Văn E', 'Kỹ thuật', 'Các kỹ thuật tưới nước thông minh giúp tiết kiệm nước mà vẫn đảm bảo cây phát triển tốt, phù hợp với điều kiện khí hậu khô hạn.', 'Tưới nước đúng cách không chỉ giúp cây phát triển tốt mà còn tiết kiệm tài nguyên nước. Tìm hiểu các phương pháp tưới nước hiện đại và hiệu quả.', 'Tưới nước là một trong những yếu tố quan trọng nhất trong việc chăm sóc cây trồng. Tuy nhiên, việc tưới nước không đúng cách không chỉ lãng phí tài nguyên mà còn có thể gây hại cho cây.

Các phương pháp tưới nước tiết kiệm như tưới nhỏ giọt, tưới phun sương hoặc tưới theo chu kỳ đã được chứng minh là hiệu quả hơn nhiều so với tưới truyền thống.'),
('Lợi ích của việc trồng cây trong đô thị', 'loi-ich-cua-viec-trong-cay-trong-do-thi', '2024-01-03', 'Võ Thị F', 'Đô thị', 'Cây xanh trong đô thị không chỉ làm đẹp cảnh quan mà còn mang lại nhiều lợi ích về sức khỏe và môi trường cho cư dân thành phố.', 'Không gian xanh trong đô thị đang trở thành xu hướng phổ biến trên toàn thế giới nhờ những lợi ích to lớn mà nó mang lại cho sức khỏe và môi trường.', 'Trồng cây trong đô thị đang trở thành xu hướng phổ biến trên toàn thế giới nhờ những lợi ích to lớn mà nó mang lại. Cây xanh giúp lọc không khí, giảm ô nhiễm tiếng ồn và điều hòa nhiệt độ.

Ngoài ra, không gian xanh còn có tác dụng tích cực đến sức khỏe tinh thần, giúp giảm căng thẳng và cải thiện chất lượng cuộc sống.'),
('Công nghệ mới trong nông nghiệp bền vững', 'cong-nghe-moi-trong-nong-nghiep-ben-vung', '2024-01-01', 'Đặng Văn G', 'Công nghệ', 'Ứng dụng công nghệ hiện đại như IoT, AI và cảm biến thông minh trong nông nghiệp để tối ưu hóa sản xuất và bảo vệ môi trường.', 'Nông nghiệp bền vững đang được cách mạng hóa bởi các công nghệ tiên tiến như Internet of Things và Trí tuệ nhân tạo.', 'Nông nghiệp bền vững đang được cách mạng hóa bởi các công nghệ tiên tiến. Internet of Things (IoT) cho phép nông dân theo dõi điều kiện đất, nước và khí hậu theo thời gian thực.

Trí tuệ nhân tạo (AI) giúp phân tích dữ liệu và đưa ra các khuyến nghị về thời điểm gieo trồng, tưới tiêu và thu hoạch tối ưu.'),
('Bảo tồn đa dạng sinh học thông qua trồng cây', 'bao-ton-da-dang-sinh-hoc-thong-qua-trong-cay', '2023-12-28', 'Bùi Thị H', 'Bảo tồn', 'Trồng cây bản địa và tạo môi trường sống tự nhiên giúp bảo tồn các loài động thực vật quý hiếm và duy trì cân bằng sinh thái.', 'Đa dạng sinh học là nền tảng của sự sống trên Trái Đất. Trồng cây bản địa là cách hiệu quả để bảo tồn hệ sinh thái tự nhiên.', 'Đa dạng sinh học là nền tảng của sự sống trên Trái Đất, và việc bảo tồn nó là trách nhiệm của tất cả chúng ta. Trồng cây bản địa là một trong những cách hiệu quả nhất để bảo tồn đa dạng sinh học.

Khi chúng ta trồng các loài cây đa dạng, chúng ta không chỉ tạo ra không gian xanh mà còn tạo ra một hệ sinh thái phong phú với nhiều loài chim, côn trùng và động vật nhỏ.'),
('Chương trình giáo dục môi trường cho trẻ em', 'chuong-trinh-giao-duc-moi-truong-cho-tre-em', '2023-12-25', 'Ngô Văn I', 'Giáo dục', 'Dạy trẻ em về tầm quan trọng của cây xanh và môi trường từ nhỏ để hình thành ý thức bảo vệ thiên nhiên cho thế hệ tương lai.', 'Giáo dục môi trường cho trẻ em là khoản đầu tư quan trọng cho tương lai của hành tinh, giúp hình thành thói quen sống xanh từ nhỏ.', 'Giáo dục môi trường cho trẻ em là một khoản đầu tư quan trọng cho tương lai của hành tinh. Khi trẻ em được học về tầm quan trọng của cây xanh và môi trường từ nhỏ, chúng sẽ phát triển ý thức bảo vệ thiên nhiên.

Các chương trình giáo dục môi trường thường bao gồm các hoạt động thực tế như trồng cây, chăm sóc vườn trường và tham quan các khu bảo tồn thiên nhiên.'),
('Tác động của rừng ngập mặn đến môi trường biển', 'tac-dong-cua-rung-ngap-man-den-moi-truong-bien', '2023-12-22', 'Lý Thị K', 'Nghiên cứu', 'Rừng ngập mặn đóng vai trò quan trọng trong việc bảo vệ bờ biển, lọc nước và là nơi sinh sống của nhiều loài sinh vật biển quý hiếm.', 'Rừng ngập mặn là một trong những hệ sinh thái quan trọng nhất trên Trái Đất, đóng vai trò như lá chắn tự nhiên bảo vệ bờ biển.', 'Rừng ngập mặn là một trong những hệ sinh thái quan trọng nhất trên Trái Đất, đóng vai trò như một lá chắn tự nhiên bảo vệ bờ biển khỏi sóng biển và bão tố.

Hệ thống rễ phức tạp của cây ngập mặn giúp giữ đất, ngăn chặn xói mòn và ổn định bờ biển. Ngoài ra, rừng ngập mặn còn có khả năng lọc nước, loại bỏ các chất ô nhiễm.'),
('Kinh nghiệm trồng cây từ các chuyên gia', 'kinh-nghiem-trong-cay-tu-cac-chuyen-gia', '2023-12-20', 'Trương Văn L', 'Kinh nghiệm', 'Chia sẻ những kinh nghiệm quý báu từ các chuyên gia nông nghiệp và môi trường về cách trồng và chăm sóc cây hiệu quả nhất.', 'Học hỏi từ các chuyên gia là cách tốt nhất để tránh sai lầm và đạt được thành công trong việc trồng cây.', 'Kinh nghiệm từ các chuyên gia là một nguồn tài nguyên vô giá cho những người mới bắt đầu trồng cây. Các chuyên gia đã tích lũy được nhiều kiến thức và kinh nghiệm thực tế qua nhiều năm làm việc.

Một số nguyên tắc quan trọng mà các chuyên gia thường nhấn mạnh bao gồm: hiểu rõ đặc tính của từng loại cây, chuẩn bị đất trồng kỹ lưỡng, tưới nước đúng cách và theo dõi sức khỏe của cây thường xuyên.'),
('Tầm quan trọng của việc bảo vệ rừng đầu nguồn', 'tam-quan-trong-cua-viec-bao-ve-rung-dau-nguon', '2023-12-18', 'Phan Thị M', 'Bảo vệ', 'Rừng đầu nguồn có vai trò quan trọng trong việc điều tiết nước, ngăn chặn lũ lụt và bảo vệ nguồn nước cho các khu vực hạ lưu.', 'Rừng đầu nguồn đóng vai trò cực kỳ quan trọng trong việc điều tiết nước và bảo vệ môi trường, là nguồn sống của các khu vực hạ lưu.', 'Rừng đầu nguồn là những khu rừng nằm ở vùng thượng lưu của các con sông, đóng vai trò cực kỳ quan trọng trong việc điều tiết nước và bảo vệ môi trường.

Hệ thống rễ cây giúp giữ đất, ngăn chặn xói mòn và sạt lở đất, trong khi lớp thảm thực vật giúp hấp thụ và giữ nước mưa, làm chậm dòng chảy và giảm nguy cơ lũ lụt.');

-- Seed news tags
INSERT INTO news_tags (news_id, tag) VALUES
(1, 'Môi trường'), (1, 'Trồng cây'), (1, 'Cộng đồng'),
(2, 'Nông nghiệp'), (2, 'Cây ăn quả'), (2, 'Kỹ thuật'),
(3, 'Biến đổi khí hậu'), (3, 'Rừng'), (3, 'Môi trường'),
(4, 'Cây giống'), (4, 'Nông nghiệp'), (4, 'Chất lượng'),
(5, 'Tưới nước'), (5, 'Tiết kiệm'), (5, 'Nông nghiệp'),
(6, 'Đô thị'), (6, 'Cây xanh'), (6, 'Sức khỏe'),
(7, 'Công nghệ'), (7, 'IoT'), (7, 'AI'),
(8, 'Đa dạng sinh học'), (8, 'Bảo tồn'), (8, 'Môi trường'),
(9, 'Giáo dục'), (9, 'Trẻ em'), (9, 'Môi trường'),
(10, 'Rừng ngập mặn'), (10, 'Biển'), (10, 'Bảo vệ'),
(11, 'Kinh nghiệm'), (11, 'Chuyên gia'), (11, 'Nông nghiệp'),
(12, 'Rừng đầu nguồn'), (12, 'Bảo vệ'), (12, 'Nguồn nước');

-- Seed gallery images
INSERT INTO gallery_images (image_url, alt_text, category) VALUES
('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 1', 'Cây trồng'),
('https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 2', 'Vườn ươm'),
('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 3', 'Thiên nhiên'),
('https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 4', 'Cây trồng'),
('https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 5', 'Rừng xanh'),
('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 6', 'Thiên nhiên'),
('https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 7', 'Vườn ươm'),
('https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 8', 'Cây trồng'),
('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 9', 'Thiên nhiên'),
('https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 10', 'Rừng xanh'),
('https://images.unsplash.com/photo-1486723312829-27b0f5554b48?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 11', 'Cây trồng'),
('https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 12', 'Vườn ươm'),
('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 13', 'Thiên nhiên'),
('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 14', 'Cây trồng'),
('https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 15', 'Vườn ươm'),
('https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 16', 'Thiên nhiên'),
('https://images.unsplash.com/photo-1486723312829-27b0f5554b48?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 17', 'Cây trồng'),
('https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 18', 'Vườn ươm'),
('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 19', 'Thiên nhiên'),
('https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=800&q=80', 'Thư viện ảnh 20', 'Cây trồng');
