<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getPDO();

// Lấy danh sách cây đã trồng của user
$stmt = $pdo->prepare("
    SELECT 
        tp.*,
        l.name AS land_name,
        l.polygon_geojson,
        l.grid_json,
        o.order_code,
        o.created_at AS order_date,
        p.name AS product_name
    FROM tree_plantings tp
    INNER JOIN lands l ON tp.land_id = l.id
    INNER JOIN orders o ON tp.order_id = o.order_id
    LEFT JOIN products p ON tp.product_id = p.product_id
    WHERE tp.user_id = :uid
    ORDER BY tp.planted_at DESC
");
$stmt->execute([':uid' => $user['user_id']]);
$trees = $stmt->fetchAll();

// Nhóm cây theo land
$treesByLand = [];
foreach ($trees as $tree) {
    $landId = $tree['land_id'];
    if (!isset($treesByLand[$landId])) {
        $treesByLand[$landId] = [
            'land_name' => $tree['land_name'],
            'polygon_geojson' => $tree['polygon_geojson'],
            'grid_json' => $tree['grid_json'],
            'trees' => []
        ];
    }
    $treesByLand[$landId]['trees'][] = $tree;
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .account-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .account-layout { grid-template-columns: 1fr; } }
    .account-content { background-color: var(--white); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.06); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; }
    .trees-empty { text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #e8f5e9, #c8e6c9); border-radius: 12px; }
    .trees-empty svg { width: 80px; height: 80px; margin-bottom: 20px; color: #4caf50; }
    .trees-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; padding: 20px; border-radius: 12px; text-align: center; }
    .stat-number { font-size: 36px; font-weight: 700; margin-bottom: 5px; }
    .stat-label { font-size: 14px; opacity: 0.9; }
    .land-section { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .land-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .land-title { font-size: 20px; font-weight: 700; color: var(--primary); }
    .btn-view-map { background: #4caf50; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; }
    .trees-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
    .tree-card { background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .tree-image { width: 100%; height: 150px; object-fit: cover; border-radius: 6px; margin-bottom: 10px; background: #e0e0e0; }
    .tree-info { font-size: 14px; }
    .tree-info-row { display: flex; justify-content: space-between; margin: 8px 0; padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
    .tree-info-row:last-child { border-bottom: none; }
    .tree-label { color: #666; }
    .tree-value { font-weight: 600; color: #333; }
    .tree-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-healthy { background: #d4edda; color: #155724; }
    #map { width: 100%; height: 500px; border-radius: 8px; margin-top: 20px; }
</style>

<div class="account-layout">
    <?php include __DIR__ . '/sidebar-account.php'; ?>
    
    <main class="account-content">
        <h2 style="margin-bottom: 20px;">🌳 Cây của tôi</h2>
        
        <?php if (empty($trees)): ?>
            <div class="trees-empty">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
                <h3>Bạn chưa trồng cây nào</h3>
                <p style="color: #666; margin: 15px 0;">Hãy mua cây và trồng chúng trên bản đồ để đóng góp vào việc bảo vệ môi trường!</p>
                <a href="<?php echo BASE_URL; ?>/public/products.php" style="display: inline-block; background: #4caf50; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-top: 10px;">
                    Mua cây ngay
                </a>
            </div>
        <?php else: ?>
            <!-- Thống kê -->
            <div class="trees-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($trees); ?></div>
                    <div class="stat-label">Tổng số cây đã trồng</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #2196f3, #42a5f5);">
                    <div class="stat-number"><?php echo count($treesByLand); ?></div>
                    <div class="stat-label">Mẫu đất đã tham gia</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #ff9800, #ffa726);">
                    <div class="stat-number"><?php echo date('d/m/Y', strtotime($trees[0]['planted_at'])); ?></div>
                    <div class="stat-label">Lần trồng gần nhất</div>
                </div>
            </div>
            
            <!-- Xem tất cả trên bản đồ -->
            <div style="margin-bottom: 20px;">
                <button onclick="toggleMap()" class="btn-view-map" style="border: none; cursor: pointer;">
                    🗺️ Xem tất cả trên bản đồ
                </button>
            </div>
            
            <div id="map-container" style="display: none;">
                <div id="map"></div>
            </div>
            
            <!-- Danh sách cây theo mẫu đất -->
            <?php foreach ($treesByLand as $landId => $landData): ?>
                <div class="land-section">
                    <div class="land-header">
                        <div class="land-title">📍 <?php echo htmlspecialchars($landData['land_name']); ?></div>
                        <span style="color: #666; font-size: 14px;"><?php echo count($landData['trees']); ?> cây</span>
                    </div>
                    
                    <div class="trees-grid">
                        <?php foreach ($landData['trees'] as $tree): ?>
                            <div class="tree-card">
                                <div class="tree-image" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #4caf50;">
                                    🌳
                                </div>
                                
                                <div class="tree-info">
                                    <div class="tree-info-row">
                                        <span class="tree-label">Tên cây:</span>
                                        <span class="tree-value"><?php echo htmlspecialchars($tree['product_name']); ?></span>
                                    </div>
                                    <div class="tree-info-row">
                                        <span class="tree-label">Vị trí:</span>
                                        <span class="tree-value">Ô [<?php echo $tree['grid_row']; ?>,<?php echo $tree['grid_col']; ?>]</span>
                                    </div>
                                    <div class="tree-info-row">
                                        <span class="tree-label">Mã đơn:</span>
                                        <span class="tree-value"><?php echo htmlspecialchars($tree['order_code']); ?></span>
                                    </div>
                                    <div class="tree-info-row">
                                        <span class="tree-label">Ngày trồng:</span>
                                        <span class="tree-value"><?php echo date('d/m/Y', strtotime($tree['planted_at'])); ?></span>
                                    </div>
                                    <div class="tree-info-row">
                                        <span class="tree-label">Tình trạng:</span>
                                        <span class="tree-status status-healthy">🌱 Khỏe mạnh</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<?php if (!empty($trees)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const TREES_DATA = <?php echo json_encode($trees); ?>;
const LANDS_DATA = <?php echo json_encode(array_values($treesByLand)); ?>;

let map = null;
let mapVisible = false;

function toggleMap() {
    const container = document.getElementById('map-container');
    mapVisible = !mapVisible;
    
    if (mapVisible) {
        container.style.display = 'block';
        if (!map) {
            initMap();
        }
    } else {
        container.style.display = 'none';
    }
}

function initMap() {
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        attribution: '&copy; OpenStreetMap' 
    });
    const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { 
        attribution: 'Tiles &copy; Esri' 
    });
    
    map = L.map('map', { layers: [osm] }).setView([20.99, 105.75], 6);
    L.control.layers({ "Bản đồ": osm, "Vệ tinh": esriSat }).addTo(map);
    
    // Icon cây xanh
    const treeIcon = L.icon({
        iconUrl: 'data:image/svg+xml;base64,' + btoa(`
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#4caf50" width="32" height="32">
                <path d="M17,8C17,10.76 14.76,13 12,13C9.24,13 7,10.76 7,8C7,5.24 9.24,3 12,3C14.76,3 17,5.24 17,8M12,15C15.87,15 19,16.79 19,19V21H5V19C5,16.79 8.13,15 12,15Z"/>
            </svg>
        `),
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32]
    });
    
    const bounds = [];
    
    // Vẽ các mẫu đất và cây
    LANDS_DATA.forEach(landData => {
        if (!landData.polygon_geojson) return;
        
        const polygon = JSON.parse(landData.polygon_geojson);
        const coords = polygon.geometry.coordinates[0].map(c => [c[1], c[0]]);
        
        // Vẽ polygon
        const poly = L.polygon(coords, {
            color: '#4caf50',
            weight: 2,
            fillOpacity: 0.1
        }).addTo(map);
        
        bounds.push(...coords);
        
        // Vẽ các cây
        landData.trees.forEach(tree => {
            const marker = L.marker([tree.center_lat, tree.center_lng], { icon: treeIcon }).addTo(map);
            
            const popupContent = `
                <div style="min-width: 200px;">
                    <h4 style="margin: 0 0 10px 0; color: #4caf50;">🌳 ${tree.product_name}</h4>
                    <div style="font-size: 13px;">
                        <p style="margin: 5px 0;"><strong>Mã đơn:</strong> ${tree.order_code}</p>
                        <p style="margin: 5px 0;"><strong>Vị trí:</strong> Ô [${tree.grid_row},${tree.grid_col}]</p>
                        <p style="margin: 5px 0;"><strong>Người trồng:</strong> ${tree.user_id === <?php echo $_SESSION['user_id']; ?> ? 'Bạn' : 'User #' + tree.user_id}</p>
                        <p style="margin: 5px 0;"><strong>Ngày trồng:</strong> ${new Date(tree.planted_at).toLocaleDateString('vi-VN')}</p>
                        <p style="margin: 5px 0;"><strong>Tình trạng:</strong> <span style="color: #4caf50;">🌱 Khỏe mạnh</span></p>
                    </div>
                </div>
            `;
            
            marker.bindPopup(popupContent);
        });
    });
    
    // Fit bounds
    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
