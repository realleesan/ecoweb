<?php
session_start();
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../includes/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getPDO();

// Determine step
$step = 'partner';
if (!empty($_GET['step'])) $step = $_GET['step'];
if (!empty($_POST['step'])) $step = $_POST['step'];

// Helper to redirect to avoid repost
function redirect_to_step($s) {
    $url = basename(__FILE__) . '?step=' . urlencode($s);
    header('Location: ' . $url);
    exit;
}

// Handle step1 submit (partner info)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_partner') {
    $partner = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'dob' => trim($_POST['dob'] ?? '') ?: null,
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'document_json' => null
    ];
    // simple document field (JSON textarea or file placeholder)
    if (!empty($_POST['document_json'])) {
        $partner['document_json'] = $_POST['document_json'];
    }
    $_SESSION['partner_form'] = $partner;
    // advance to land step
    redirect_to_step('land');
}

// Handle step2 submit (land info)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_land') {
    $land = [
        'name' => trim($_POST['name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'area' => trim($_POST['area'] ?? '') ?: null,
        'description' => trim($_POST['description'] ?? ''),
        'images_json' => null
    ];
    if (!empty($_POST['images_json'])) $land['images_json'] = $_POST['images_json'];
    $_SESSION['land_form'] = $land;
    redirect_to_step('map');
}

// Handle final save (map + persist)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'finalize') {
    $partnerForm = $_SESSION['partner_form'] ?? null;
    $landForm = $_SESSION['land_form'] ?? null;
    $polygonGeojson = !empty($_POST['polygon_geojson']) ? $_POST['polygon_geojson'] : null;
    $gridJson = !empty($_POST['grid_json']) ? $_POST['grid_json'] : null;

    if ($partnerForm && $landForm) {
        try {
            $pdo->beginTransaction();
            $pStmt = $pdo->prepare("INSERT INTO partners (user_id, full_name, dob, phone, address, document_json) VALUES (:uid, :full_name, :dob, :phone, :address, :doc)");
            $pStmt->execute([
                ':uid' => $user['user_id'],
                ':full_name' => $partnerForm['full_name'],
                ':dob' => $partnerForm['dob'],
                ':phone' => $partnerForm['phone'],
                ':address' => $partnerForm['address'],
                ':doc' => $partnerForm['document_json']
            ]);
            $partnerId = $pdo->lastInsertId();

            $lStmt = $pdo->prepare("INSERT INTO lands (partner_id, name, area, description, images_json, polygon_geojson, grid_json, status) VALUES (:pid, :name, :area, :desc, :imgs, :poly, :grid, 'pending')");
            $lStmt->execute([
                ':pid' => $partnerId,
                ':name' => $landForm['name'],
                ':area' => $landForm['area'],
                ':desc' => $landForm['description'],
                ':imgs' => $landForm['images_json'],
                ':poly' => $polygonGeojson,
                ':grid' => $gridJson
            ]);
            $pdo->commit();
            // clear session forms
            unset($_SESSION['partner_form'], $_SESSION['land_form']);
            // redirect to lands listing or success page
            header('Location: ' . BASE_URL . '/auth/lands.php');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = 'Dữ liệu biểu mẫu chưa đầy đủ.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .partners-container { max-width: 980px; margin: 20px auto; padding: 0 16px; }
    .wizard-step { background:#fff; padding:18px; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.05); }
    .form-row { margin-bottom:12px; }
    label { display:block; margin-bottom:6px; font-weight:600; }
    input[type="text"], input[type="date"], textarea { width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; }
    .btn { display:inline-block; padding:10px 14px; background:#2b8a3e; color:#fff; border-radius:8px; text-decoration:none; border:none; cursor:pointer; }
    .btn-secondary { background:#6c757d; }
    #map { width:100%; height:480px; margin-top:8px; }
    .controls { padding:8px 0; }
</style>

<div class="partners-container">
    <div class="wizard-step">
        <h2>Đăng ký đối tác - Bước: <?php echo htmlspecialchars($step); ?></h2>
        <?php if (!empty($error)): ?>
            <div style="background:#ffd6d6;padding:10px;border-radius:6px;margin-bottom:12px;color:#900;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($step === 'partner'): 
            $p = $_SESSION['partner_form'] ?? [];
        ?>
            <form method="post" novalidate>
                <input type="hidden" name="step" value="partner" />
                <input type="hidden" name="action" value="save_partner" />
                <div class="form-row"><label>Họ và tên</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($p['full_name'] ?? $user['full_name'] ?? ''); ?>" required></div>
                <div class="form-row"><label>Ngày sinh</label><input type="date" name="dob" value="<?php echo htmlspecialchars($p['dob'] ?? ''); ?>"></div>
                <div class="form-row"><label>Số điện thoại</label><input type="text" name="phone" value="<?php echo htmlspecialchars($p['phone'] ?? ''); ?>"></div>
                <div class="form-row"><label>Địa chỉ</label><input type="text" name="address" value="<?php echo htmlspecialchars($p['address'] ?? ''); ?>"></div>
                <div class="form-row"><label>Tài liệu (JSON hoặc ghi chú)</label><textarea name="document_json" rows="4"><?php echo htmlspecialchars($p['document_json'] ?? ''); ?></textarea></div>
                <div style="margin-top:12px;">
                    <button class="btn" type="submit">Tiếp tục</button>
                </div>
            </form>
        <?php elseif ($step === 'land'):
            $l = $_SESSION['land_form'] ?? [];
        ?>
            <form method="post" novalidate>
                <input type="hidden" name="step" value="land" />
                <input type="hidden" name="action" value="save_land" />
                <div class="form-row"><label>Tên mẫu đất</label><input type="text" name="name" value="<?php echo htmlspecialchars($l['name'] ?? ''); ?>" required></div>
                <div class="form-row"><label>Địa chỉ</label><input type="text" name="address" value="<?php echo htmlspecialchars($l['address'] ?? ''); ?>"></div>
                <div class="form-row"><label>Diện tích (m² hoặc ha)</label><input type="text" name="area" value="<?php echo htmlspecialchars($l['area'] ?? ''); ?>"></div>
                <div class="form-row"><label>Mô tả</label><textarea name="description" rows="4"><?php echo htmlspecialchars($l['description'] ?? ''); ?></textarea></div>
                <div class="form-row"><label>Hình ảnh (JSON array of URLs)</label><textarea name="images_json" rows="3"><?php echo htmlspecialchars($l['images_json'] ?? ''); ?></textarea></div>
                <div style="margin-top:12px;">
                    <a class="btn btn-secondary" href="<?php echo basename(__FILE__) . '?step=partner'; ?>">Quay lại</a>
                    <button class="btn" type="submit">Tiếp tục đến cấu hình bản đồ</button>
                </div>
            </form>
        <?php else: // map step ?>
            <div>
                <div class="controls" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <button id="undo-point" class="btn btn-secondary" type="button">Hoàn tác</button>
                    <button id="compute-hull" class="btn btn-secondary" type="button">Tạo Hull</button>
                    <button id="create-grid" class="btn btn-secondary" type="button">Tạo Grid 10x10</button>
                    <button id="clear-grid" class="btn btn-secondary" type="button">Xóa Grid</button>
                    <span id="point-count" style="margin-left:8px;font-weight:700;color:#333;">Điểm: 0/10</span>
                </div>
                <div id="map"></div>
                <div style="margin-top:12px;">
                    <form method="post" onsubmit="return submitFinal();" novalidate>
                        <input type="hidden" name="action" value="finalize" />
                        <input type="hidden" id="polygon_geojson" name="polygon_geojson" value="">
                        <input type="hidden" id="grid_json" name="grid_json" value="">
                        <a class="btn btn-secondary" href="<?php echo basename(__FILE__) . '?step=land'; ?>">Quay lại</a>
                        <button class="btn" type="submit">Lưu và gửi</button>
                    </form>
                </div>
                <div style="margin-top:10px;color:#666;font-size:13px;">Lưu ý: Vui lòng vẽ điểm trên bản đồ (click) hoặc dùng "Thêm điểm mẫu", sau đó tạo Hull và Grid rồi Lưu.</div>
            </div>

                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
            <script>
                // Map logic adapted from tests/partners_map_test.html
                var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' });
                var esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and others' });
                const map = L.map('map', { layers: [osm] }).setView([20.99, 105.75], 10);
                var baseMaps = { "Bản đồ": osm, "Vệ tinh": esriSat };
                L.control.layers(baseMaps).addTo(map);
                if (!map.getPane('gridPane')) {
                  map.createPane('gridPane');
                  map.getPane('gridPane').style.zIndex = 650;
                }
                let markers = [];
                let hullLayer = null;
                let gridLayer = null;

                function updatePointCount() {
                  var el = document.getElementById('point-count');
                  if (el) el.textContent = 'Điểm: ' + markers.length + '/10';
                }

                function addPoint(lat,lng) {
                  if (markers.length >= 10) {
                    alert('Đã đạt giới hạn 10 điểm. Hoàn tác hoặc xóa một điểm để thêm.');
                    return;
                  }
                  const m = L.marker([lat,lng]).addTo(map);
                  // clicking a marker will remove it (undo single) with confirmation
                  m.on('click', function(){
                    if (!confirm('Xóa điểm này?')) return;
                    map.removeLayer(m);
                    markers = markers.filter(function(item){ return item.marker !== m; });
                    updatePointCount();
                  });
                  markers.push({lat, lng, marker: m});
                  updatePointCount();
                }

                // undo last added point
                document.getElementById('undo-point').addEventListener('click', function(){
                  if (!markers.length) {
                    alert('Chưa có điểm để hoàn tác.');
                    return;
                  }
                  const last = markers.pop();
                  if (last && last.marker) {
                    map.removeLayer(last.marker);
                  }
                  updatePointCount();
                });

                function computeHull() {
                  if (markers.length !== 10) { alert('Vui lòng đánh đúng 10 điểm trước khi tạo Hull. Hiện tại: ' + markers.length); return null; }
                  const pts = markers.map(p => [p.lng, p.lat]).filter(c => isFinite(c[0]) && isFinite(c[1]));
                  const fc = turf.featureCollection(pts.map(c => turf.point(c)));
                  let hull = null;
                  try {
                    hull = turf.convex(fc);
                    if (!hull) {
                      const closed = pts.slice();
                      if (closed.length > 0 && (closed[0][0] !== closed[closed.length-1][0] || closed[0][1] !== closed[closed.length-1][1])) closed.push(closed[0]);
                      if (closed.length >= 4) hull = turf.polygon([closed]);
                    }
                  } catch (err) {
                    console.error(err);
                    hull = null;
                  }
                  if (!hull || !hull.geometry || !hull.geometry.coordinates || !hull.geometry.coordinates[0] || hull.geometry.coordinates[0].length < 4) {
                    alert('Không tạo được hull hợp lệ.');
                    return null;
                  }
                  if (hullLayer) map.removeLayer(hullLayer);
                  const latlngs = hull.geometry.coordinates[0].map(c => [c[1], c[0]]);
                  hullLayer = L.polygon(latlngs, {color:'#d9534f', weight:3}).addTo(map);
                  map.fitBounds(hullLayer.getBounds(), {padding:[20,20]});
                  return hull;
                }

                function clearGrid() {
                  if (gridLayer) { map.removeLayer(gridLayer); gridLayer = null; }
                }

                function createGridFromHull(hull) {
                  if (!hull) { alert('Hull chưa có'); return; }
                  const coords = hull.geometry.coordinates[0].map(c => [c[1], c[0]]);
                  const lats = coords.map(c => c[0]);
                  const lngs = coords.map(c => c[1]);
                  const baseBBox = {minLat: Math.min.apply(null,lats), maxLat: Math.max.apply(null,lats),
                              minLng: Math.min.apply(null,lngs), maxLng: Math.max.apply(null,lngs)};
                  const rows = 10, cols = 10;
                  const latStep = (baseBBox.maxLat - baseBBox.minLat) / rows;
                  const lngStep = (baseBBox.maxLng - baseBBox.minLng) / cols;
                  if (gridLayer) map.removeLayer(gridLayer);
                  gridLayer = L.layerGroup().addTo(map);
                  const validCells = [];
                  const allCells = [];
                  for (let r=0;r<rows;r++){
                    for (let c=0;c<cols;c++){
                      const cellMinLat = baseBBox.minLat + r*latStep;
                      const cellMaxLat = baseBBox.minLat + (r+1)*latStep;
                      const cellMinLng = baseBBox.minLng + c*lngStep;
                      const cellMaxLng = baseBBox.minLng + (c+1)*lngStep;
                      const center = [(cellMinLat+cellMaxLat)/2, (cellMinLng+cellMaxLng)/2];
                      const pt = turf.point([center[1], center[0]]);
                      const inside = turf.booleanPointInPolygon(pt, hull);
                      const rect = L.rectangle([[cellMinLat, cellMinLng],[cellMaxLat, cellMaxLng]], {color: inside ? '#ff9800' : '#cccccc', weight: inside?1.5:1, pane:'gridPane'}).addTo(gridLayer);
                      const cellObj = {r,c,bbox:{minLat:cellMinLat,minLng:cellMinLng,maxLat:cellMaxLat,maxLng:cellMaxLng},inside};
                      allCells.push(cellObj);
                      if (inside) validCells.push({bbox: cellObj.bbox});
                    }
                  }
                  // standard grid JSON saved to DB and read by admin/index
                  const gridJson = {
                    baseBBox: baseBBox,
                    rows: rows,
                    cols: cols,
                    offset: {lat:0, lng:0},
                    validCells: validCells,
                    allCells: allCells
                  };
                  return gridJson;
                }

                document.getElementById('compute-hull').addEventListener('click', function(){
                  computeHull();
                });
                document.getElementById('create-grid').addEventListener('click', function(){
                  const hull = computeHull();
                  if (!hull) return;
                  createGridFromHull(hull);
                });
                document.getElementById('clear-grid').addEventListener('click', function(){ clearGrid(); });

                map.on('click', function(e){
                  const lat = e.latlng.lat, lng = e.latlng.lng;
                  addPoint(lat,lng);
                });

                function submitFinal() {
                  // compute hull and grid one last time, serialize to hidden inputs
                  const hull = computeHull();
                  if (!hull) { alert('Hull chưa hợp lệ, không thể lưu.'); return false; }
                  const grid = createGridFromHull(hull);
                  document.getElementById('polygon_geojson').value = JSON.stringify(hull);
                  document.getElementById('grid_json').value = JSON.stringify(grid);
                  return true;
                }
            </script>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


