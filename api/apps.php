<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body      = json_decode(file_get_contents('php://input'), true);
    $device_id = isset($body['device_id']) ? (int)$body['device_id'] : 0;
    $apps      = isset($body['apps']) && is_array($body['apps']) ? $body['apps'] : [];

    if ($device_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'device_id required']);
        exit;
    }
    $chk = $pdo->prepare('SELECT id FROM devices WHERE id = :id');
    $chk->execute([':id' => $device_id]);
    if ( ! $chk->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found']);
        exit;
    }

    $upsert = $pdo->prepare(
        'INSERT INTO installed_apps
             (device_id, package_name, app_label, version_name, version_code, is_system, first_seen, last_seen)
         VALUES (:dev, :pkg, :lbl, :ver, :code, :sys, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
             app_label    = VALUES(app_label),
             version_name = VALUES(version_name),
             version_code = VALUES(version_code),
             is_system    = VALUES(is_system),
             last_seen    = NOW()'
    );

    $received = [];
    $pdo->beginTransaction();
    foreach ($apps as $app) {
        $pkg = isset($app['package_name']) ? substr(trim($app['package_name']), 0, 200) : '';
        if ($pkg === '' || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z0-9_]+)+$/', $pkg)) continue;
        $lbl  = isset($app['app_label'])    ? substr(trim($app['app_label']),    0, 200) : $pkg;
        $ver  = isset($app['version_name']) ? substr(trim($app['version_name']), 0, 50)  : '';
        $code = isset($app['version_code']) ? (int)$app['version_code']                  : 0;
        $sys  = isset($app['is_system'])    ? ($app['is_system'] ? 1 : 0)                : 0;
        $upsert->execute([':dev'=>$device_id,':pkg'=>$pkg,':lbl'=>$lbl,':ver'=>$ver,':code'=>$code,':sys'=>$sys]);
        $received[] = $pkg;
    }

    if ( ! empty($received)) {
        $ph  = implode(',', array_fill(0, count($received), '?'));
        $pdo->prepare("DELETE FROM installed_apps WHERE device_id = ? AND package_name NOT IN ($ph)")
            ->execute(array_merge([$device_id], $received));
    } else {
        $pdo->prepare('DELETE FROM installed_apps WHERE device_id = ?')->execute([$device_id]);
    }
    $pdo->commit();

    $pdo->prepare('UPDATE devices SET status = "online", last_seen = NOW() WHERE id = :id')
        ->execute([':id' => $device_id]);
    echo json_encode(['success' => true, 'received' => count($received)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;
    if ($device_id > 0) {
        $stmt = $pdo->prepare(
            'SELECT package_name, app_label, version_name, version_code, is_system, first_seen, last_seen
             FROM installed_apps WHERE device_id = :id
             ORDER BY is_system ASC, app_label ASC'
        );
        $stmt->execute([':id' => $device_id]);
        echo json_encode(['success' => true, 'apps' => $stmt->fetchAll()]);
    } else {
        $rows = $pdo->query(
            'SELECT ia.device_id, d.device_name,
                    SUM(ia.is_system = 0) AS user_apps,
                    SUM(ia.is_system = 1) AS system_apps,
                    COUNT(*) AS total_apps, MAX(ia.last_seen) AS last_report
             FROM installed_apps ia LEFT JOIN devices d ON ia.device_id = d.id
             GROUP BY ia.device_id, d.device_name ORDER BY d.device_name'
        )->fetchAll();
        echo json_encode(['success' => true, 'summary' => $rows]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
