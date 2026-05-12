<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body      = json_decode(file_get_contents('php://input'), true);
    $device_id = isset($body['device_id'])     ? (int)$body['device_id']     : 0;
    $latitude  = isset($body['latitude'])      ? (float)$body['latitude']    : null;
    $longitude = isset($body['longitude'])     ? (float)$body['longitude']   : null;
    $accuracy  = isset($body['accuracy'])      ? (float)$body['accuracy']    : null;
    $battery   = isset($body['battery_level']) ? (int)$body['battery_level'] : null;

    if ($device_id <= 0 || $latitude === null || $longitude === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'device_id, latitude, longitude are required']);
        exit;
    }
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
        exit;
    }
    $chk = $pdo->prepare('SELECT id FROM devices WHERE id = :id');
    $chk->execute([':id' => $device_id]);
    if (!$chk->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found']);
        exit;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO device_locations (device_id, latitude, longitude, accuracy, battery_level)
         VALUES (:dev, :lat, :lng, :acc, :bat)'
    );
    $stmt->execute([':dev' => $device_id, ':lat' => $latitude, ':lng' => $longitude,
                    ':acc' => $accuracy, ':bat' => $battery]);
    $pdo->prepare('UPDATE devices SET last_seen = NOW(), status = "online" WHERE id = :id')
        ->execute([':id' => $device_id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;
    if ($device_id > 0) {
        $stmt = $pdo->prepare(
            'SELECT latitude, longitude, accuracy, battery_level, created_at
             FROM device_locations WHERE device_id = :id
             ORDER BY created_at DESC LIMIT 50'
        );
        $stmt->execute([':id' => $device_id]);
        echo json_encode(['success' => true, 'locations' => $stmt->fetchAll()]);
    } else {
        $rows = $pdo->query(
            'SELECT dl.device_id, d.device_name, d.status,
                    dl.latitude, dl.longitude, dl.accuracy, dl.battery_level, dl.created_at
             FROM device_locations dl
             INNER JOIN (
                 SELECT device_id, MAX(created_at) AS latest
                 FROM device_locations GROUP BY device_id
             ) latest_loc ON dl.device_id = latest_loc.device_id
                          AND dl.created_at = latest_loc.latest
             LEFT JOIN devices d ON dl.device_id = d.id
             ORDER BY d.device_name'
        )->fetchAll();
        echo json_encode(['success' => true, 'devices' => $rows]);
    }
    exit;
}
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
