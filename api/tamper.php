<?php
define('MDM_API_CALL', true);
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$device_id  = isset($body['device_id'])  ? (int)$body['device_id']  : 0;
$event_type = isset($body['event_type']) ? trim($body['event_type']) : '';
$allowed = ['deactivate_admin', 'uninstall_attempt'];

if ($device_id <= 0 || !in_array($event_type, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'device_id and valid event_type required']);
    exit;
}

$chk = $pdo->prepare('SELECT id FROM devices WHERE id = :id');
$chk->execute([':id' => $device_id]);
if (!$chk->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Device not found']);
    exit;
}

$msg = $event_type === 'deactivate_admin'
    ? 'TAMPER ALERT: User attempted to deactivate MDM device admin -- lockdown activated.'
    : 'TAMPER ALERT: User attempted to uninstall MDM agent -- lockdown activated.';

$pdo->prepare('INSERT INTO logs (device_id, message) VALUES (?, ?)')->execute([$device_id, $msg]);

$cfg = $pdo->query(
    'SELECT uninstall_protection_enabled, tamper_lockdown FROM curfew_settings WHERE id=1'
)->fetch();

$lockdown_activated = false;

if (!empty($cfg['uninstall_protection_enabled']) && !empty($cfg['tamper_lockdown'])) {
    $pdo->prepare('UPDATE curfew_settings SET whitelist_mode = 1 WHERE id = 1')->execute();
    $pdo->prepare(
        'INSERT INTO device_commands (device_id, command, payload) VALUES (:dev, "LOCK", :pay)'
    )->execute([
        ':dev' => $device_id,
        ':pay' => json_encode(['reason' => 'tamper_detected', 'event' => $event_type]),
    ]);
    $lockdown_activated = true;
}

echo json_encode(['success' => true, 'lockdown_activated' => $lockdown_activated]);
