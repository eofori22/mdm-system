<?php
define('MDM_API_CALL', true);
require_once '../includes/db.php';
header('Content-Type: application/json');

$ALLOWED_COMMANDS = ['LOCK', 'UNLOCK', 'WIPE', 'REBOOT', 'PUSH_RULES', 'ENABLE_KIOSK', 'DISABLE_KIOSK'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    if (isset($body['command_id'])) {
        $cmd_id = (int)$body['command_id'];
        $status = ($body['status'] ?? '') === 'failed' ? 'failed' : 'delivered';
        $pdo->prepare('UPDATE device_commands SET status = :s, delivered_at = NOW() WHERE id = :id')
            ->execute([':s' => $status, ':id' => $cmd_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    $device_id = isset($body['device_id']) ? (int)$body['device_id'] : 0;
    $command   = strtoupper(trim($body['command'] ?? ''));
    $payload   = isset($body['payload']) ? json_encode($body['payload']) : null;

    if ($device_id <= 0 || ! in_array($command, $ALLOWED_COMMANDS, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'device_id and valid command required']);
        exit;
    }
    $chk = $pdo->prepare('SELECT id FROM devices WHERE id = :id');
    $chk->execute([':id' => $device_id]);
    if ( ! $chk->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found']);
        exit;
    }
    $pdo->prepare('INSERT INTO device_commands (device_id, command, payload) VALUES (:dev, :cmd, :pay)')
        ->execute([':dev' => $device_id, ':cmd' => $command, ':pay' => $payload]);
    echo json_encode(['success' => true, 'command_id' => (int)$pdo->lastInsertId()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;
    if ($device_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'device_id required']);
        exit;
    }
    $stmt = $pdo->prepare(
        'SELECT id, command, payload FROM device_commands
         WHERE device_id = :id AND status = "pending"
         ORDER BY created_at ASC LIMIT 10'
    );
    $stmt->execute([':id' => $device_id]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['payload'] = $row['payload'] !== null ? json_decode($row['payload'], true) : null;
    }
    echo json_encode(['success' => true, 'commands' => $rows]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
