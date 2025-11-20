<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
    $sites = [];
    // existing sites table rows (site listings + bbox)
    $stmt = $pdo->query("SELECT id, name, center_lat, center_lng, bbox_lat1, bbox_lng1, bbox_lat2, bbox_lng2 FROM sites");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $r['type'] = 'site';
        $sites[] = $r;
    }

    // also include approved lands that have polygon_geojson (so frontend can draw accurate polygons)
    $lstmt = $pdo->prepare("SELECT id, name, polygon_geojson, grid_json FROM lands WHERE status = 'approved' AND polygon_geojson IS NOT NULL AND polygon_geojson != ''");
    $lstmt->execute();
    $lands = $lstmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($lands as $l) {
        $entry = [
            'id' => (int) $l['id'],
            'name' => $l['name'],
            'type' => 'land',
            'polygon_geojson' => null,
            'grid_json' => null,
            'center_lat' => null,
            'center_lng' => null,
            'bbox_lat1' => null,
            'bbox_lng1' => null,
            'bbox_lat2' => null,
            'bbox_lng2' => null
        ];
        // decode polygon and compute center/bbox if possible
        $poly = json_decode($l['polygon_geojson'], true);
        if ($poly && isset($poly['geometry']['coordinates'][0])) {
            $coords = $poly['geometry']['coordinates'][0];
            $sumLat = 0; $sumLng = 0; $n = 0;
            $minLat = 999; $minLng = 999; $maxLat = -999; $maxLng = -999;
            foreach ($coords as $c) {
                $lng = (float) $c[0];
                $lat = (float) $c[1];
                $sumLat += $lat; $sumLng += $lng; $n++;
                if ($lat < $minLat) $minLat = $lat;
                if ($lat > $maxLat) $maxLat = $lat;
                if ($lng < $minLng) $minLng = $lng;
                if ($lng > $maxLng) $maxLng = $lng;
            }
            if ($n) {
                $entry['center_lat'] = $sumLat / $n;
                $entry['center_lng'] = $sumLng / $n;
                $entry['bbox_lat1'] = $minLat;
                $entry['bbox_lng1'] = $minLng;
                $entry['bbox_lat2'] = $maxLat;
                $entry['bbox_lng2'] = $maxLng;
            }
            $entry['polygon_geojson'] = $poly;
        }
        if (!empty($l['grid_json'])) {
            $g = json_decode($l['grid_json'], true);
            if ($g) $entry['grid_json'] = $g;
        }
        $sites[] = $entry;
    }

    echo json_encode(['success' => true, 'data' => $sites]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


