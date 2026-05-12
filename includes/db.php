<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database credentials
$host = 'localhost';
$dbname = 'tablet_control';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Show errors during development
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Stop app if connection fails
    die('Database connection failed: ' . $e->getMessage());
}

/**
 * Ghost-device detection — runs on every page load.
 *
 * Rules (evaluated lazily so no cron job is needed):
 *  • last_seen older than 5 min AND status='online'  → mark 'offline'
 *  • last_seen older than 30 min AND status='offline' → mark 'removed',
 *    log a "TAMPER ALERT: Agent has gone silent" entry so it shows up
 *    in the Tamper Alerts table on the Lockdown page.
 */
function mdm_check_ghost_devices(PDO $pdo): void {
    // 1. Flip online → offline after 5 minutes of silence
    $pdo->exec(
        "UPDATE devices
         SET status = 'offline'
         WHERE status = 'online'
           AND last_seen < NOW() - INTERVAL 5 MINUTE"
    );

    // 2. Flip offline → removed after 30 minutes of continued silence
    //    and write one log entry per device (avoid duplicate logs)
    $gone = $pdo->query(
        "SELECT id, device_name FROM devices
         WHERE status = 'offline'
           AND last_seen < NOW() - INTERVAL 30 MINUTE"
    )->fetchAll();

    foreach ($gone as $d) {
        // Check we haven't already logged this removal
        $already = $pdo->prepare(
            "SELECT id FROM logs
             WHERE device_id = :id
               AND message LIKE 'TAMPER ALERT: MDM agent has gone silent%'
               AND created_at > NOW() - INTERVAL 2 HOUR"
        );
        $already->execute([':id' => $d['id']]);
        if (!$already->fetch()) {
            $pdo->prepare(
                "INSERT INTO logs (device_id, message) VALUES (?, ?)"
            )->execute([
                $d['id'],
                'TAMPER ALERT: MDM agent has gone silent (possible uninstall) — device marked removed.',
            ]);
        }
        $pdo->prepare(
            "UPDATE devices SET status = 'removed', removed_at = NOW() WHERE id = ? AND status = 'offline'"
        )->execute([$d['id']]);
    }
}

/**
 * Push a PUSH_RULES command to every enrolled (non-removed) device.
 * Call this after any change that should take effect immediately on tablets.
 * Defined here once so every page can use it without duplication.
 */
function pushRulesToAllDevices(PDO $pdo): void {
    $enrolled = $pdo->query('SELECT id FROM devices WHERE status != "removed"')->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare('INSERT INTO device_commands (device_id, command) VALUES (?, "PUSH_RULES")');
    foreach ($enrolled as $did) {
        $ins->execute([(int)$did]);
    }
}

// Run ghost detection on every web-facing page (not on API calls)
if (PHP_SAPI !== 'cli' && !defined('MDM_API_CALL')) {
    mdm_check_ghost_devices($pdo);
}