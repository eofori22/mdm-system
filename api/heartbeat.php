<?php
define('MDM_API_CALL', true);
require_once '../includes/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
$body = json_decode(file_get_contents('php://input'), true);
$device_id = isset($body['device_id']) ? (int)$body['device_id'] : 0;
if ($device_id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'device_id required']); exit; }
$pdo->prepare('UPDATE devices SET status="online", last_seen=NOW() WHERE id=:id')->execute([':id'=>$device_id]);

// Return any pending commands so the device can act on them immediately
$stmt = $pdo->prepare(
    'SELECT id, command, payload FROM device_commands
     WHERE device_id = :id AND status = "pending"
     ORDER BY created_at ASC LIMIT 10'
);
$stmt->execute([':id' => $device_id]);
$commands = $stmt->fetchAll();
$cmd_ids = [];
foreach ($commands as &$c) {
    $c['payload'] = $c['payload'] !== null ? json_decode($c['payload'], true) : null;
    $cmd_ids[] = (int)$c['id'];
}
unset($c);
if ($cmd_ids) {
    $in = implode(',', array_fill(0, count($cmd_ids), '?'));
    $pdo->prepare("UPDATE device_commands SET status='delivered', delivered_at=NOW() WHERE id IN ($in)")
        ->execute($cmd_ids);
}
echo json_encode(['success' => true, 'commands' => $commands]);
