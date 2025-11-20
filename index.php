<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$latestNews = [];
$featuredProducts = [];
$latestGallery = [];

try {
    $pdo = getPDO();
} catch (RuntimeException $e) {
    $pdo = null;
}

if ($pdo) {
    try {
        $newsStmt = $pdo->query('SELECT news_id, title, publish_date, excerpt FROM news ORDER BY publish_date DESC, news_id DESC LIMIT 4');
        $latestNews = $newsStmt->fetchAll();

        $productStmt = $pdo->query('SELECT product_id, name, price, short_description FROM products ORDER BY is_bestseller DESC, created_at DESC LIMIT 8');
        $featuredProducts = $productStmt->fetchAll();

        $galleryStmt = $pdo->query('SELECT image_url, alt_text FROM gallery_images ORDER BY created_at DESC, image_id DESC LIMIT 8');
        $latestGallery = $galleryStmt->fetchAll();
    } catch (PDOException $e) {
        $latestNews = [];
        $featuredProducts = [];
        $latestGallery = [];
    }
}

include 'includes/header.php';
?>

<style>
    body {
        background-color: var(--light);
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
    }

    /* Hero Section */
    .hero-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: <?php echo GRID_GAP; ?>;
        margin: 30px auto;
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
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
        height: 500px;
    }

    .news-header {
        background-color: var(--primary);
        color: var(--white);
        padding: 15px 20px;
        border-radius: 10px 10px 0 0;
        font-weight: 700;
        font-size: 20px;
        flex-shrink: 0;
    }

    .news-list {
        background-color: var(--white);
        border-radius: 0 0 10px 10px;
        padding: 12px 15px 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
        overflow-y: auto;
    }

    .news-item {
        padding-bottom: 8px;
        border-bottom: 1px solid #e0e0e0;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .news-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
        margin-bottom: 0;
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
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 5px;
        color: var(--primary);
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-item p {
        font-size: 12px;
        color: var(--dark);
        line-height: 1.4;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
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
        max-width: <?php echo CONTAINER_MAX_WIDTH; ?>;
        padding: 0 <?php echo CONTAINER_PADDING; ?>;
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
        max-width: <?php echo CONTAINER_MAX_WIDTH_XSMALL; ?>;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    /* Introduction Section */
    .intro-content {
        background-color: var(--white);
        padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        line-height: 1.8;
        font-size: 16px;
        color: var(--dark);
    }

    .intro-content p {
        margin-bottom: 20px;
    }

    /* Map Section - synced with partners.php style */
    .map-container {
        background: transparent;
        border-radius: 12px;
        padding: 0;
        box-shadow: none;
        height: auto;
        display: block;
        border: none;
    }

    /* map full size */
    #map {
        width: 100%;
        height: 480px;
        margin-top: 8px;
        border-radius: 12px;
    }

    /* Products Section */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: <?php echo GRID_GAP_SMALL; ?>;
        margin-bottom: 40px;
    }

    .product-card {
        background-color: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .product-image {
        width: 100%;
        height: 160px;
        background: linear-gradient(135deg, var(--bg-green) 0%, var(--primary) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 40px;
    }

    .product-info {
        padding: 16px;
    }

    .product-info h3 {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 50px;
    }

    .product-info p {
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 12px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-price {
        font-size: 20px;
        font-weight: 600;
        color: var(--secondary);
    }

    /* View All Button */
    .view-all-btn {
        display: inline-block;
        background-color: var(--secondary);
        color: var(--white);
        padding: 12px 30px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        text-align: center;
    }

    .view-all-btn:hover {
        background-color: var(--dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(210, 100, 38, 0.3);
    }

    .view-all-container {
        text-align: center;
        margin-top: 15px;
    }

    /* Gallery Section */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: <?php echo GRID_GAP_SMALL; ?>;
        margin-bottom: 40px;
    }

    .gallery-item {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        height: 160px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--bg-green) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 18px;
        font-weight: 600;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .gallery-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }


    /* Responsive */
    @media (max-width: <?php echo BREAKPOINT_XL; ?>) {
        .products-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .gallery-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: <?php echo BREAKPOINT_LG; ?>) {
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
            grid-template-columns: repeat(2, 1fr);
        }

        .gallery-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
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


        .products-grid {
            grid-template-columns: 1fr;
        }

        .gallery-grid {
            grid-template-columns: 1fr;
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
            <?php if (empty($latestNews)): ?>
                <div class="news-item" style="border-bottom: none; text-align: center;">
                    <p>Chưa có tin tức để hiển thị.</p>
                </div>
            <?php else: ?>
                <?php foreach ($latestNews as $news): ?>
                <div class="news-item">
                    <a href="views/news-detail.php?id=<?php echo $news['news_id']; ?>">
                        <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                        <?php if (!empty($news['excerpt'])): ?>
                            <p><?php echo htmlspecialchars($news['excerpt']); ?></p>
                        <?php endif; ?>
                        <div class="news-date">
                            <i class="far fa-calendar"></i>
                            <span><?php echo date('d/m/Y', strtotime($news['publish_date'])); ?></span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
        <div id="map" style="width:100%; height:100%; min-height:380px; border-radius:12px;"></div>
    </div>

    <style>
    /* small custom tooltip style for map */
    .leaflet-tooltip.my-tooltip {
      background: #fff;
      color: #333;
      border: 1px solid #eee;
      box-shadow: 0 6px 18px rgba(0,0,0,0.12);
      padding: 8px 10px;
      border-radius: 6px;
      font-weight: 700;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize map centered on Vietnam with base layers (OSM + ESRI satellite)
      var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      });

      var esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and others'
      });

      var map = L.map('map', { layers: [esriSat] }).setView([14.0583, 108.2772], 5);

      var baseMaps = {
        "Bản đồ": osm,
        "Vệ tinh": esriSat
      };
      L.control.layers(baseMaps).addTo(map);
      
      // ensure a pane for grid so grid rectangles appear above tiles
      if (!map.getPane('gridPane')) {
        map.createPane('gridPane');
        map.getPane('gridPane').style.zIndex = 650;
      }

      // Use FontAwesome icons as divIcons (no image files needed)
      var siteIcon = L.divIcon({
        className: 'site-divicon',
        html: '<i class="fas fa-map-marker-alt" style="color:#d9534f;font-size:28px"></i>',
        iconSize: [28, 28],
        iconAnchor: [14, 28]
      });

      var treeIcon = L.divIcon({
        className: 'tree-divicon',
        html: '<i class="fas fa-seedling" style="color:#2e8b57;font-size:20px"></i>',
        iconSize: [22, 22],
        iconAnchor: [11, 22]
      });

      var currentSiteLayer = null;
      var plantingLayer = L.layerGroup().addTo(map);
      var selectedPlantingLayer = null;
      var gridLayer = null;
      var gridToggleControl = null;
      var currentGridJson = null;

      function createGridLayerFromGridJson(gjson) {
        console.log('createGridLayerFromGridJson input', gjson);
        if (!gjson) return null;
        var rows = gjson.rows || 10;
        var cols = gjson.cols || 10;
        var base = gjson.baseBBox;
        // try to derive baseBBox from cells/validCells/allCells if missing
        var sourceCells = null;
        if (!base) {
          if (gjson.validCells && gjson.validCells.length) sourceCells = gjson.validCells;
          else if (gjson.allCells && gjson.allCells.length) sourceCells = gjson.allCells;
          else if (gjson.cells && gjson.cells.length) sourceCells = gjson.cells;
          if (sourceCells) {
            var minLat = Infinity, minLng = Infinity, maxLat = -Infinity, maxLng = -Infinity;
            sourceCells.forEach(function(cell){
              var b = cell.bbox || cell;
              if (b && typeof b.minLat === 'number') {
                if (b.minLat < minLat) minLat = b.minLat;
                if (b.minLng < minLng) minLng = b.minLng;
                if (b.maxLat > maxLat) maxLat = b.maxLat;
                if (b.maxLng > maxLng) maxLng = b.maxLng;
              } else if (typeof cell.minLat === 'number') {
                if (cell.minLat < minLat) minLat = cell.minLat;
                if (cell.minLng < minLng) minLng = cell.minLng;
                if (cell.maxLat > maxLat) maxLat = cell.maxLat;
                if (cell.maxLng > maxLng) maxLng = cell.maxLng;
              }
            });
            if (isFinite(minLat)) base = {minLat: minLat, minLng: minLng, maxLat: maxLat, maxLng: maxLng};
          }
        }
        if (!base) {
          console.warn('createGridLayerFromGridJson: no baseBBox and no cells to derive from');
          return null;
        }
        var latStep = (base.maxLat - base.minLat) / rows;
        var lngStep = (base.maxLng - base.minLng) / cols;
        var layer = L.layerGroup();
        // Prefer explicit validCells/allCells/cells in that order
        var used = 'none';
        if (gjson.validCells && gjson.validCells.length) {
          used = 'validCells';
          gjson.validCells.forEach(function(vc){
            var b = vc.bbox || vc;
            if (b) L.rectangle([[b.minLat,b.minLng],[b.maxLat,b.maxLng]], {color:'#2e8b57', weight:1, pane:'gridPane'}).addTo(layer);
          });
        } else if (gjson.allCells && gjson.allCells.length) {
          used = 'allCells';
          gjson.allCells.forEach(function(cell){
            var b = cell.bbox || cell;
            var color = (cell.inside || (b && b.inside)) ? '#ff9800' : '#cccccc';
            var weight = (cell.inside || (b && b.inside)) ? 1.5 : 1;
            if (b) L.rectangle([[b.minLat,b.minLng],[b.maxLat,b.maxLng]], {color: color, weight: weight, pane:'gridPane'}).addTo(layer);
          });
        } else if (gjson.cells && gjson.cells.length) {
          used = 'cells';
          gjson.cells.forEach(function(cell){
            var b = cell.bbox || (cell.cell && cell.cell.bbox) || cell;
            var inside = cell.inside || (b && b.inside) || false;
            if (b && typeof b.minLat === 'number') {
              L.rectangle([[b.minLat,b.minLng],[b.maxLat,b.maxLng]], {color: inside ? '#ff9800' : '#cccccc', weight: inside ? 1.5 : 1, pane:'gridPane'}).addTo(layer);
            } else if (typeof cell.r === 'number' && typeof cell.c === 'number') {
              // compute bbox from r,c using base and steps
              var rr = cell.r, cc = cell.c;
              var cellMinLat = base.minLat + rr*latStep;
              var cellMaxLat = base.minLat + (rr+1)*latStep;
              var cellMinLng = base.minLng + cc*lngStep;
              var cellMaxLng = base.minLng + (cc+1)*lngStep;
              L.rectangle([[cellMinLat,cellMinLng],[cellMaxLat,cellMaxLng]], {color: inside ? '#ff9800' : '#cccccc', weight: inside ? 1.5 : 1, pane:'gridPane'}).addTo(layer);
            }
          });
        } else {
          used = 'computed';
          for (var r=0;r<rows;r++){
            for (var c=0;c<cols;c++){
              var cellMinLat = base.minLat + r*latStep;
              var cellMaxLat = base.minLat + (r+1)*latStep;
              var cellMinLng = base.minLng + c*lngStep;
              var cellMaxLng = base.minLng + (c+1)*lngStep;
              L.rectangle([[cellMinLat,cellMinLng],[cellMaxLat,cellMaxLng]], {color:'#cccccc', weight:1, pane:'gridPane'}).addTo(layer);
            }
          }
        }
        console.log('createGridLayerFromGridJson created layer using', used, 'rows=', rows, 'cols=', cols);
        return layer;
      }

      function addGridToggleControl() {
        removeGridToggleControl();
        var GridControl = L.Control.extend({
          options: { position: 'topright' },
          onAdd: function () {
            var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control grid-toggle-control');
            container.style.background = '#fff';
            container.style.padding = '4px';
            container.style.borderRadius = '6px';
            container.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
            container.style.zIndex = 1000;
            var btn = L.DomUtil.create('a', '', container);
            btn.href = '#';
            btn.innerHTML = 'Hiện ô lưới';
            btn.style.display = 'inline-block';
            btn.style.padding = '6px 8px';
            btn.style.textDecoration = 'none';
            btn.style.color = '#2e8b57';
            btn.style.fontWeight = '700';
            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.on(btn, 'click', function (e) {
              L.DomEvent.stop(e);
              console.log('Grid toggle clicked, currentGridJson=', currentGridJson, 'gridLayer exists=', gridLayer!==null);
              var showing = gridLayer !== null;
              if (showing) {
                if (gridLayer) map.removeLayer(gridLayer);
                gridLayer = null;
                btn.innerHTML = 'Hiện ô lưới';
              } else {
                if (currentGridJson) {
                  gridLayer = createGridLayerFromGridJson(currentGridJson);
                  if (gridLayer) gridLayer.addTo(map);
                  btn.innerHTML = 'Ẩn ô lưới';
                }
              }
            });
            console.log('Grid toggle control added');
            return container;
          }
        });
        gridToggleControl = new GridControl();
        map.addControl(gridToggleControl);
      }

      function removeGridToggleControl() {
        try {
          if (gridToggleControl) { map.removeControl(gridToggleControl); gridToggleControl = null; }
        } catch (e) {}
        if (gridLayer) { map.removeLayer(gridLayer); gridLayer = null; }
      }

      // helper functions
      function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
      function formatPrice(p){ if(!p) return ''; return Number(p).toLocaleString('vi-VN') + '₫'; }

      // Load tất cả cây đã trồng (hiển thị ngay khi load trang)
      var allPlantedTrees = [];
      fetch('<?php echo BASE_URL; ?>/api/all-planted-trees.php')
        .then(function(res){ return res.json(); })
        .then(function(json){
          if(!json.success) return;
          allPlantedTrees = json.data || [];
          console.log('Loaded ' + allPlantedTrees.length + ' planted trees');
          
          // Hiển thị tất cả cây đã trồng trên map
          allPlantedTrees.forEach(function(tree){
            var treeMarker = L.marker([tree.center_lat, tree.center_lng], {icon: treeIcon})
              .addTo(plantingLayer)
              .bindPopup(
                '<div style="min-width: 200px;">' +
                '<h4 style="margin: 0 0 10px 0; color: #4caf50;">🌳 ' + escapeHtml(tree.product_name) + '</h4>' +
                '<div style="font-size: 13px;">' +
                '<p style="margin: 5px 0;"><strong>Mẫu đất:</strong> ' + escapeHtml(tree.land_name) + '</p>' +
                '<p style="margin: 5px 0;"><strong>Vị trí:</strong> Ô [' + tree.grid_row + ',' + tree.grid_col + ']</p>' +
                '<p style="margin: 5px 0;"><strong>Người trồng:</strong> ' + escapeHtml(tree.user_name || tree.username || 'Ẩn danh') + '</p>' +
                '<p style="margin: 5px 0;"><strong>Mã đơn:</strong> ' + escapeHtml(tree.order_code) + '</p>' +
                '<p style="margin: 5px 0;"><strong>Ngày trồng:</strong> ' + new Date(tree.planted_at).toLocaleDateString('vi-VN') + '</p>' +
                '<p style="margin: 5px 0;"><strong>Tình trạng:</strong> <span style="color: #4caf50;">🌱 Khỏe mạnh</span></p>' +
                '</div>' +
                '</div>'
              );
          });
        })
        .catch(function(err){ console.error('Lỗi load planted trees:', err); });

      // load sites (mẫu đất)
      fetch('<?php echo BASE_URL; ?>/api/sites.php')
        .then(function(res){ return res.json(); })
        .then(function(json){
          if(!json.success) return;
          json.data.forEach(function(site){
            var lat = site.center_lat || 0;
            var lng = site.center_lng || 0;
            var m = L.marker([lat, lng], {icon: siteIcon})
              .addTo(map)
              .bindTooltip(site.name, {className: 'my-tooltip', direction: 'top', offset:[0,-8]})
              .on('mouseover', function(){ this.openTooltip(); })
              .on('mouseout', function(){ this.closeTooltip(); })
              .on('click', function(){
                // reset any existing grid control/state
                currentGridJson = null;
                removeGridToggleControl();
                // prefer drawing polygon if available (lands), otherwise fall back to bbox rectangle or center zoom
                if (site.polygon_geojson) {
                  try {
                    if (currentSiteLayer) map.removeLayer(currentSiteLayer);
                    var coords = site.polygon_geojson.geometry.coordinates[0].map(function(c){ return [c[1], c[0]]; });
                    currentSiteLayer = L.polygon(coords, {color:'#d9534f', weight:3, fill:false}).addTo(map);
                    map.fitBounds(currentSiteLayer.getBounds(), {padding:[40,40]});
                  } catch (err) {
                    console.error('Lỗi vẽ polygon:', err);
                  }
                  if (site.grid_json) {
                    try {
                      currentGridJson = (typeof site.grid_json === 'string') ? JSON.parse(site.grid_json) : site.grid_json;
                    } catch (e) { currentGridJson = site.grid_json; }
                    addGridToggleControl();
                  }
                } else if (site.bbox_lat1 && site.bbox_lng1 && site.bbox_lat2 && site.bbox_lng2) {
                  var bounds = L.latLngBounds([site.bbox_lat1, site.bbox_lng1], [site.bbox_lat2, site.bbox_lng2]);
                  map.fitBounds(bounds, {padding: [40,40]});
                  if (currentSiteLayer) map.removeLayer(currentSiteLayer);
                  currentSiteLayer = L.rectangle(bounds, {color:'#d9534f', weight:4, fill:false}).addTo(map);
                  if (site.grid_json) {
                    try {
                      currentGridJson = (typeof site.grid_json === 'string') ? JSON.parse(site.grid_json) : site.grid_json;
                    } catch (e) { currentGridJson = site.grid_json; }
                    addGridToggleControl();
                  }
                } else {
                  map.setView([lat, lng], 15);
                }

                // Không xóa plantingLayer nữa vì đã có tất cả cây trồng
                // Chỉ highlight các cây thuộc land này nếu cần
                // (Giữ nguyên tất cả cây đã trồng trên map)
              });
          });
        })
        .catch(function(err){ console.error('Lỗi load sites:', err); });

    });
    </script>
</section>

<!-- Trending Seeds Section -->
<section class="section">
    <div class="section-header">
        <h2>Cây trồng thịnh hành</h2>
        <p>Những loại cây trồng được yêu thích nhất hiện nay</p>
    </div>
    <div class="products-grid">
        <?php if (empty($featuredProducts)): ?>
            <p style="grid-column: 1 / -1; text-align: center; color: var(--dark);">Chưa có sản phẩm nổi bật.</p>
        <?php else: ?>
            <?php
            $icons = ['fas fa-seedling', 'fas fa-tree', 'fas fa-leaf'];
            $idx = 0;
            ?>
            <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card" onclick="window.location.href='views/products-detail.php?id=<?php echo $product['product_id']; ?>'">
                <div class="product-image">
                    <i class="<?php echo $icons[$idx % count($icons)]; ?>"></i>
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <?php if (!empty($product['short_description'])): ?>
                        <p><?php echo htmlspecialchars($product['short_description']); ?></p>
                    <?php endif; ?>
                    <div class="product-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</div>
                </div>
            </div>
            <?php $idx++; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="view-all-container">
        <a href="public/products.php" class="view-all-btn">Xem tất cả</a>
    </div>
</section>

<!-- Gallery Section -->
<section class="section">
    <div class="section-header">
        <h2>Hình ảnh dự án</h2>
        <p>Những khoảnh khắc đẹp từ các dự án trồng rừng đã hoàn thành</p>
    </div>
    <div class="gallery-grid">
        <?php if (empty($latestGallery)): ?>
            <div class="gallery-item" style="height: auto; padding: 40px; display: block; text-align: center;">
                <p>Thư viện hình ảnh đang được cập nhật.</p>
            </div>
        <?php else: ?>
            <?php foreach ($latestGallery as $image): ?>
            <div class="gallery-item" style="padding: 0;">
                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['alt_text']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.4); padding: 12px; text-align: center;">
                    <p style="margin: 0; color: #fff; font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($image['alt_text']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="view-all-container">
        <a href="public/galleries.php" class="view-all-btn">Xem tất cả</a>
    </div>
</section>

<?php
$cta_heading = 'Hãy cùng gieo thêm một mầm xanh cho Trái Đất hôm nay';
$cta_description = 'Mỗi cây xanh bạn trồng là một đóng góp ý nghĩa cho tương lai của hành tinh. Hãy tham gia cùng chúng tôi trong hành trình phủ xanh Trái Đất!';
$cta_button_text = 'Tham gia phủ xanh';
$cta_button_link = BASE_URL . '/public/products.php';
include 'includes/components/cta-section.php';
?>

<?php include 'includes/footer.php'; ?>


