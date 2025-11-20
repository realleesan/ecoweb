<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
requireAdminLogin();

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/admin/lands/index.php');
    exit;
}

// Handle approve/reject POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'approve') {
        // set status approve and create site
        $stmt = $pdo->prepare("SELECT * FROM lands WHERE id = :id");
        $stmt->execute([':id'=>$id]);
        $land = $stmt->fetch();
        if ($land) {
            // compute center and bbox from polygon_geojson if available
            $polygon = null;
            if (!empty($land['polygon_geojson'])) {
                $polygon = json_decode($land['polygon_geojson'], true);
            }
            $centerLat = null; $centerLng = null;
            $bbox = [];
            if ($polygon && isset($polygon['geometry']['coordinates'][0])) {
                $coords = $polygon['geometry']['coordinates'][0];
                $sumLat = 0; $sumLng = 0; $n = 0;
                $minLat = 999; $minLng = 999; $maxLat = -999; $maxLng = -999;
                foreach ($coords as $c) {
                    $lng = $c[0]; $lat = $c[1];
                    $sumLat += $lat; $sumLng += $lng; $n++;
                    if ($lat < $minLat) $minLat = $lat;
                    if ($lat > $maxLat) $maxLat = $lat;
                    if ($lng < $minLng) $minLng = $lng;
                    if ($lng > $maxLng) $maxLng = $lng;
                }
                if ($n) {
                    $centerLat = $sumLat / $n;
                    $centerLng = $sumLng / $n;
                    $bbox = [$minLat, $minLng, $maxLat, $maxLng];
                }
            }

            // insert into sites
            $ins = $pdo->prepare("INSERT INTO sites (name, center_lat, center_lng, bbox_lat1, bbox_lng1, bbox_lat2, bbox_lng2, description) VALUES (:name, :clat, :clng, :b1lat, :b1lng, :b2lat, :b2lng, :desc)");
            $ins->execute([
                ':name' => $land['name'],
                ':clat' => $centerLat ?? 0,
                ':clng' => $centerLng ?? 0,
                ':b1lat' => $bbox[0] ?? null,
                ':b1lng' => $bbox[1] ?? null,
                ':b2lat' => $bbox[2] ?? null,
                ':b2lng' => $bbox[3] ?? null,
                ':desc' => $land['description']
            ]);
            // update land status
            $upd = $pdo->prepare("UPDATE lands SET status = 'approved' WHERE id = :id");
            $upd->execute([':id'=>$id]);
        }
    } elseif ($action === 'reject') {
        $upd = $pdo->prepare("UPDATE lands SET status = 'rejected' WHERE id = :id");
        $upd->execute([':id'=>$id]);
    } elseif ($action === 'delete') {
        // delete land record
        $del = $pdo->prepare("DELETE FROM lands WHERE id = :id");
        $del->execute([':id' => $id]);
        // redirect back to list
        header('Location: ' . BASE_URL . '/admin/lands/index.php');
        exit;
    }
    header('Location: ' . BASE_URL . '/admin/lands/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT l.*, p.full_name AS partner_name FROM lands l JOIN partners p ON p.id = l.partner_id WHERE l.id = :id");
$stmt->execute([':id'=>$id]);
$land = $stmt->fetch();
if (!$land) {
    header('Location: ' . BASE_URL . '/admin/lands/index.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .admin-layout { grid-template-columns: 1fr; } }
    .admin-content { background-color: var(--white); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); padding: <?php echo CONTAINER_PADDING_MEDIUM; ?>; }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-content">
        <h1>Chi tiết Mẫu đất #<?php echo $land['id']; ?></h1>
        <p><strong>Tên:</strong> <?php echo htmlspecialchars($land['name']); ?> — <strong>Chủ đất:</strong> <?php echo htmlspecialchars($land['partner_name']); ?></p>
        <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($land['status']); ?> — <strong>Ngày tạo:</strong> <?php echo $land['created_at']; ?></p>

        <div style="display:flex; gap:20px; margin-top:12px;">
            <div style="flex:1;">
                <div id="admin-land-map" style="height:420px; border:1px solid #eee; border-radius:8px;"></div>
            </div>
            <div style="width:360px;">
                <h4>Thông tin</h4>
                <p><?php echo nl2br(htmlspecialchars($land['description'])); ?></p>
                <h4>Polygon (GeoJSON)</h4>
                <textarea style="width:100%; height:160px;"><?php echo htmlspecialchars($land['polygon_geojson']); ?></textarea>
                <h4>Grid</h4>
                <pre style="background:#fff; padding:8px; max-height:200px; overflow:auto;"><?php echo htmlspecialchars($land['grid_json']); ?></pre>

                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo $land['id']; ?>">
                    <?php if ($land['status'] === 'pending'): ?>
                    <button type="submit" name="action" value="approve" class="btn-primary" style="margin-right:8px;">Duyệt</button>
                    <button type="submit" name="action" value="reject" class="btn-link" style="color:#c33;">Từ chối</button>
                    <?php else: ?>
                    <div style="margin-top:10px;">Hành động đã thực hiện: <?php echo htmlspecialchars($land['status']); ?></div>
                    <?php endif; ?>
                    <button type="submit" name="action" value="delete" class="btn-link" style="color:#c33; margin-top:8px;" onclick="return confirm('Bạn có chắc muốn xóa mẫu đất này? Hành động không thể hoàn tác.');">Xóa</button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- leaflet to render polygon/grid -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var map = L.map('admin-land-map').setView([14.0583, 108.2772], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    var polygonJson = <?php echo $land['polygon_geojson'] ? $land['polygon_geojson'] : 'null'; ?>;
    if (polygonJson) {
        var coords = polygonJson.geometry.coordinates[0].map(function(c){ return [c[1], c[0]]; });
        var poly = L.polygon(coords, {color:'#d9534f', weight:3}).addTo(map);
        map.fitBounds(poly.getBounds(), {padding:[20,20]});
    }
    var grid = <?php echo $land['grid_json'] ? $land['grid_json'] : 'null'; ?>;
    if (grid && grid.baseBBox) {
        // draw grid cells (only validCells)
        var base = grid.baseBBox;
        var rows = grid.rows || 10;
        var cols = grid.cols || 10;
        var off = grid.offset || {lat:0,lng:0};
        var minLat = base.minLat + off.lat, maxLat = base.maxLat + off.lat;
        var minLng = base.minLng + off.lng, maxLng = base.maxLng + off.lng;
        var latStep = (maxLat - minLat) / rows;
        var lngStep = (maxLng - minLng) / cols;
        if (grid.validCells && grid.validCells.length) {
            grid.validCells.forEach(function(cell){
                var b = cell.bbox;
                L.rectangle([[b.minLat,b.minLng],[b.maxLat,b.maxLng]], {color:'#2e8b57', weight:1}).addTo(map);
            });
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


