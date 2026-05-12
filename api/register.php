<?php
/**
 * POST /api/register.php
 * Body: { "device_name": "Samsung Galaxy Tab", "serial_number": "abc123", "student_name": "John Smith" }
 * Response: { "success": true, "device_id": 5 }
 */
define('MDM_API_CALL', true);
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body          = json_decode(file_get_contents('php://input'), true);
$device_name   = isset($body['device_name'])   ? trim($body['device_name'])   : '';
$serial_number = isset($body['serial_number']) ? trim($body['serial_number']) : '';
$imei          = isset($body['imei'])          ? substr(trim($body['imei']), 0, 20) : '';
$student_name  = isset($body['student_name'])  ? substr(trim($body['student_name']), 0, 120) : '';

if ($device_name === '' || $serial_number === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'device_name and serial_number are required']);
    exit;
}

$device_name   = substr($device_name,   0, 100);
$serial_number = substr($serial_number, 0, 100);

$stmt = $pdo->prepare(
    'INSERT INTO devices (device_name, student_name, serial_number, imei, status, last_seen)
     VALUES (:name, :sname, :serial, :imei, "online", NOW())
     ON DUPLICATE KEY UPDATE device_name = :name, student_name = :sname, imei = :imei, status = "online", last_seen = NOW()'
);
$stmt->execute([':name' => $device_name, ':sname' => $student_name, ':serial' => $serial_number, ':imei' => $imei]);

$device_id = (int)$pdo->lastInsertId();
if ($device_id === 0) {
    $s = $pdo->prepare('SELECT id FROM devices WHERE serial_number = :serial');
    $s->execute([':serial' => $serial_number]);
    $device_id = (int)$s->fetchColumn();
}

echo json_encode(['success' => true, 'device_id' => $device_id]);
