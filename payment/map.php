<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../auth/auth.php';

requireLogin();

$orderCode = $_GET['order_code'] ?? '';
$order = null;

if ($orderCode) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = :code AND user_id = :uid LIMIT 1');
        $stmt->execute([':code' => $orderCode, ':uid' => $_SESSION['user_id']]);
        $order = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$order) {
    header('Location: ' . BASE_URL . '/auth/orders.php');
    exit;
}

// Nếu đơn hàng chưa thanh toán, chuyển về trang thanh toán
if ($order['status'] === 'pending') {
    header('Location: ' . BASE_URL . '/payment/payment.php?order_code=' . urlencode($orderCode));
    exit;
}

// Nếu đơn hàng đã trồng cây rồi, chuyển về trang "Cây của tôi"
if ($order['status'] === 'planted') {
    header('Location: ' . BASE_URL . '/auth/my-trees.php');
    exit;
}

// Chỉ cho phép truy cập nếu status = 'paid'
if ($order['status'] !== 'paid') {
    header('Location: ' . BASE_URL . '/auth/orders.php');
    exit;
}

// Lấy danh sách sản phẩm trong đơn hàng
$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :oid');
$stmt->execute([':oid' => $order['order_id']]);
$orderItems = $stmt->fetchAll();

// Tính tổng số cây cần trồng
$totalTrees = 0;
foreach ($orderItems as $item) {
    $totalTrees += $item['quantity'];
}

// Lấy danh sách lands đã được duyệt
$stmt = $pdo->query("SELECT * FROM lands WHERE status = 'approved' ORDER BY created_at DESC");
$lands = $stmt->fetchAll();

// Lấy tất cả cây đã trồng (để hiển thị icon xanh)
$stmt = $pdo->query("
    SELECT 
        tp.*,
        p.name AS product_name,
        u.full_name AS user_name,
        o.order_code
    FROM tree_plantings tp
    LEFT JOIN products p ON tp.product_id = p.product_id
    LEFT JOIN users u ON tp.user_id = u.user_id
    LEFT JOIN orders o ON tp.order_id = o.order_id
    ORDER BY tp.planted_at DESC
");
$plantedTrees = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<style>
    body { background: #f5f5f5; }
    .map-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
    .map-header { background: white; border-radius: 12px; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .map-title { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 15px; }
    .map-subtitle { color: #666; font-size: 16px; margin-bottom: 20px; }
    .order-summary { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-top: 20px; }
    .summary-row { display: flex; justify-content: space-between; margin: 8px 0; }
    .map-content { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    #map { width: 100%; height: 600px; border-radius: 8px; }
    .selection-panel { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-top: 20px; }
    .tree-counter { font-size: 18px; font-weight: 700; color: var(--primary); margin-bottom: 15px; }
    .selected-cells { display: flex; flex-wrap: wrap; gap: 8px; margin: 15px 0; }
    .cell-badge { background: #28a745; color: white; padding: 6px 12px; border-radius: 20px; font-size: 14px; }
    .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px; border-radius: 8px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>

<div class="map-container">
    <div class="map-header">
        <h1 class="map-title">🌳 Bản đồ trồng cây của bạn</h1>
        <p class="map-subtitle">Cảm ơn bạn đã đóng góp vào việc bảo vệ môi trường!</p>
        
        <div class="order-summary">
            <div class="summary-row">
                <span>Mã đơn hàng:</span>
                <strong><?php echo htmlspecialchars($order['order_code']); ?></strong>
            </div>
            <div class="summary-row">
                <span>Số tiền đóng góp:</span>
                <strong><?php echo number_format($order['final_amount'] ?? $order['total_amount'], 0, ',', '.'); ?> đ</strong>
            </div>
            <div class="summary-row">
                <span>Trạng thái:</span>
                <strong style="color: #28a745;">✓ Đã thanh toán</strong>
            </div>
            <div class="summary-row">
                <span>Ngày đặt:</span>
                <strong><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></strong>
            </div>
        </div>
    </div>

    <div class="map-content">
        <div id="map"></div>
        
        <div class="selection-panel">
            <div class="tree-counter">
                🌳 Đã chọn: <span id="selected-count">0</span> / <?php echo $totalTrees; ?> cây
            </div>
            
            <div id="selected-cells-container" style="display: none;">
                <strong>Các ô đã chọn:</strong>
                <div class="selected-cells" id="selected-cells"></div>
            </div>
            
            <div style="margin-top: 20px;">
                <button id="btn-plant-trees" class="btn btn-success" disabled onclick="plantTrees()">
                    🌱 TRỒNG CÂY NGAY
                </button>
                <button id="btn-clear-selection" class="btn btn-secondary" onclick="clearSelection()">
                    🔄 Xóa lựa chọn
                </button>
            </div>
            
            <div style="margin-top: 15px; color: #666; font-size: 14px;">
                <strong>Hướng dẫn:</strong>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li>Chọn mẫu đất (pin đỏ) trên bản đồ</li>
                    <li>Click vào các ô màu cam để chọn vị trí trồng cây</li>
                    <li>Chọn đủ <?php echo $totalTrees; ?> ô theo số cây đã mua</li>
                    <li>Bấm "TRỒNG CÂY NGAY" để hoàn tất</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

<script>
const ORDER_CODE = '<?php echo htmlspecialchars($orderCode); ?>';
const TOTAL_TREES = <?php echo $totalTrees; ?>;
const LANDS_DATA = <?php echo json_encode($lands); ?>;
const PLANTED_TREES = <?php echo json_encode($plantedTrees); ?>;

// Khởi tạo bản đồ
const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
    attribution: '&copy; OpenStreetMap' 
});
const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { 
    attribution: 'Tiles &copy; Esri' 
});

const map = L.map('map', { layers: [osm] }).setView([20.99, 105.75], 6);
L.control.layers({ "Bản đồ": osm, "Vệ tinh": esriSat }).addTo(map);

// Tạo pane cho grid
if (!map.getPane('gridPane')) {
    map.createPane('gridPane');
    map.getPane('gridPane').style.zIndex = 650;
}

let selectedLand = null;
let selectedCells = [];
let gridLayer = null;
let gridCells = [];
let treeMarkers = [];

// Icon cây xanh cho cây đã trồng
const treeIcon = L.icon({
    iconUrl: 'data:image/svg+xml;base64,' + btoa(`
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#4caf50" width="28" height="28">
            <path d="M17,8C17,10.76 14.76,13 12,13C9.24,13 7,10.76 7,8C7,5.24 9.24,3 12,3C14.76,3 17,5.24 17,8M12,15C15.87,15 19,16.79 19,19V21H5V19C5,16.79 8.13,15 12,15Z"/>
        </svg>
    `),
    iconSize: [28, 28],
    iconAnchor: [14, 28],
    popupAnchor: [0, -28]
});

// Hiển thị các mẫu đất
LANDS_DATA.forEach(land => {
    if (!land.polygon_geojson) return;
    
    const polygon = JSON.parse(land.polygon_geojson);
    const coords = polygon.geometry.coordinates[0].map(c => [c[1], c[0]]);
    
    // Vẽ polygon
    const poly = L.polygon(coords, {
        color: '#d9534f',
        weight: 2,
        fillOpacity: 0.1
    }).addTo(map);
    
    // Tính center để đặt marker
    const bounds = poly.getBounds();
    const center = bounds.getCenter();
    
    // Đặt marker
    const marker = L.marker([center.lat, center.lng], {
        title: land.name
    }).addTo(map);
    
    marker.bindPopup(`<strong>${land.name}</strong><br>${land.description || ''}`);
    
    // Click vào marker để chọn land và hiển thị grid
    marker.on('click', () => {
        selectLand(land, polygon);
    });
});

function selectLand(land, polygon) {
    selectedLand = land;
    clearSelection();
    
    // Parse grid_json
    const gridData = JSON.parse(land.grid_json);
    if (!gridData || !gridData.validCells) {
        alert('Mẫu đất này chưa có lưới. Vui lòng chọn mẫu đất khác.');
        return;
    }
    
    // Zoom đến land
    const coords = polygon.geometry.coordinates[0].map(c => [c[1], c[0]]);
    const bounds = L.latLngBounds(coords);
    map.fitBounds(bounds, { padding: [50, 50] });
    
    // Vẽ grid
    drawGrid(gridData, land.id);
}

function drawGrid(gridData, landId) {
    if (gridLayer) map.removeLayer(gridLayer);
    gridLayer = L.layerGroup().addTo(map);
    gridCells = [];
    
    // Lấy danh sách ô đã có cây trên land này
    const occupiedCells = PLANTED_TREES
        .filter(t => t.land_id === landId)
        .map(t => `${t.grid_row},${t.grid_col}`);
    
    gridData.validCells.forEach((cell, index) => {
        const bbox = cell.bbox;
        const row = Math.floor(index / 10);
        const col = index % 10;
        const cellKey = `${row},${col}`;
        const isOccupied = occupiedCells.includes(cellKey);
        
        const rect = L.rectangle(
            [[bbox.minLat, bbox.minLng], [bbox.maxLat, bbox.maxLng]],
            {
                color: isOccupied ? '#cccccc' : '#ff9800',
                weight: 1.5,
                fillOpacity: isOccupied ? 0.1 : 0.3,
                pane: 'gridPane',
                className: 'grid-cell'
            }
        ).addTo(gridLayer);
        
        const cellData = {
            index,
            bbox,
            rect,
            landId,
            row,
            col,
            centerLat: (bbox.minLat + bbox.maxLat) / 2,
            centerLng: (bbox.minLng + bbox.maxLng) / 2,
            isOccupied
        };
        
        gridCells.push(cellData);
        
        // Click để chọn/bỏ chọn ô (chỉ nếu chưa có cây)
        if (!isOccupied) {
            rect.on('click', () => {
                toggleCell(cellData);
            });
        }
    });
    
    // Hiển thị cây đã trồng trên land này
    showPlantedTreesOnLand(landId);
}

function showPlantedTreesOnLand(landId) {
    // Xóa markers cũ
    treeMarkers.forEach(m => map.removeLayer(m));
    treeMarkers = [];
    
    // Vẽ markers cho cây đã trồng
    PLANTED_TREES.filter(t => t.land_id === landId).forEach(tree => {
        const marker = L.marker([tree.center_lat, tree.center_lng], { icon: treeIcon }).addTo(map);
        
        const popupContent = `
            <div style="min-width: 220px;">
                <h4 style="margin: 0 0 10px 0; color: #4caf50;">🌳 ${tree.product_name || 'Cây'}</h4>
                <div style="font-size: 13px;">
                    <p style="margin: 5px 0;"><strong>Mã đơn:</strong> ${tree.order_code}</p>
                    <p style="margin: 5px 0;"><strong>Vị trí:</strong> Ô [${tree.grid_row},${tree.grid_col}]</p>
                    <p style="margin: 5px 0;"><strong>Người trồng:</strong> ${tree.user_name || 'User #' + tree.user_id}</p>
                    <p style="margin: 5px 0;"><strong>Ngày trồng:</strong> ${new Date(tree.planted_at).toLocaleDateString('vi-VN')}</p>
                    <p style="margin: 5px 0;"><strong>Tình trạng:</strong> <span style="color: #4caf50;">🌱 Khỏe mạnh</span></p>
                </div>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        treeMarkers.push(marker);
    });
}

function toggleCell(cellData) {
    if (cellData.isOccupied) {
        alert('Ô này đã có cây. Vui lòng chọn ô khác.');
        return;
    }
    
    const existingIndex = selectedCells.findIndex(c => 
        c.landId === cellData.landId && c.row === cellData.row && c.col === cellData.col
    );
    
    if (existingIndex >= 0) {
        // Bỏ chọn
        selectedCells.splice(existingIndex, 1);
        cellData.rect.setStyle({ color: '#ff9800', fillOpacity: 0.3 });
    } else {
        // Chọn
        if (selectedCells.length >= TOTAL_TREES) {
            alert(`Bạn chỉ được chọn tối đa ${TOTAL_TREES} ô (theo số cây đã mua).`);
            return;
        }
        selectedCells.push(cellData);
        cellData.rect.setStyle({ color: '#28a745', fillOpacity: 0.6 });
    }
    
    updateUI();
}

function updateUI() {
    document.getElementById('selected-count').textContent = selectedCells.length;
    
    const container = document.getElementById('selected-cells-container');
    const cellsDiv = document.getElementById('selected-cells');
    
    if (selectedCells.length > 0) {
        container.style.display = 'block';
        cellsDiv.innerHTML = selectedCells.map(c => 
            `<span class="cell-badge">Ô [${c.row},${c.col}]</span>`
        ).join('');
    } else {
        container.style.display = 'none';
    }
    
    const btnPlant = document.getElementById('btn-plant-trees');
    btnPlant.disabled = selectedCells.length !== TOTAL_TREES;
}

function clearSelection() {
    selectedCells.forEach(cell => {
        cell.rect.setStyle({ color: '#ff9800', fillOpacity: 0.3 });
    });
    selectedCells = [];
    updateUI();
}

function plantTrees() {
    if (selectedCells.length !== TOTAL_TREES) {
        alert(`Vui lòng chọn đủ ${TOTAL_TREES} ô.`);
        return;
    }
    
    if (!confirm(`Xác nhận trồng ${TOTAL_TREES} cây tại các vị trí đã chọn?`)) {
        return;
    }
    
    const btnPlant = document.getElementById('btn-plant-trees');
    btnPlant.disabled = true;
    btnPlant.textContent = '⏳ Đang xử lý...';
    
    fetch('<?php echo BASE_URL; ?>/api/plant-trees.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_code: ORDER_CODE,
            cells: selectedCells.map(c => ({
                land_id: c.landId,
                row: c.row,
                col: c.col,
                center_lat: c.centerLat,
                center_lng: c.centerLng
            }))
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res && res.success) {
            alert('✅ Trồng cây thành công! Cảm ơn bạn đã đóng góp vào việc bảo vệ môi trường.');
            window.location.href = '<?php echo BASE_URL; ?>/auth/orders.php';
        } else {
            alert('❌ ' + (res.message || 'Có lỗi xảy ra'));
            btnPlant.disabled = false;
            btnPlant.textContent = '🌱 TRỒNG CÂY NGAY';
        }
    })
    .catch(err => {
        console.error(err);
        alert('❌ Có lỗi xảy ra. Vui lòng thử lại.');
        btnPlant.disabled = false;
        btnPlant.textContent = '🌱 TRỒNG CÂY NGAY';
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
