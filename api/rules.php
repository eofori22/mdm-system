<?php
/**
 * GET /api/rules.php?device_id=5
 * Returns full policy for a device: blocked apps/sites, whitelist, curfew,
 * VPN/DNS policy, admin controls, and any pending commands.
 */
define('MDM_API_CALL', true);
require_once '../includes/db.php';
header('Content-Type: application/json');

$device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;

$sites   = $pdo->query('SELECT domain FROM blocked_sites')->fetchAll(PDO::FETCH_COLUMN);
$apps    = $pdo->query('SELECT package_name FROM blocked_apps')->fetchAll(PDO::FETCH_COLUMN);
$allowed = $pdo->query('SELECT package_name FROM allowed_apps')->fetchAll(PDO::FETCH_COLUMN);
$disabled_apps = $pdo->query('SELECT package_name FROM disabled_apps')->fetchAll(PDO::FETCH_COLUMN);
$curfew  = $pdo->query('SELECT enabled, curfew_time, unlock_password_hash, whitelist_mode, uninstall_password_hash, uninstall_protection_enabled, tamper_lockdown FROM curfew_settings WHERE id=1')->fetch();
$policy  = $pdo->query('SELECT *, mdm_package_name FROM admin_policy WHERE id=1')->fetch();

// Add any currently active timed blocks to the blocked_apps list.
// days_of_week is a string of ISO day numbers (1=Mon … 7=Sun).
$now_time = date('H:i:s');
$now_dow  = date('N'); // 1=Monday … 7=Sunday
$timed = $pdo->query(
    'SELECT package_name, block_from, block_to, days_of_week FROM timed_app_blocks WHERE enabled = 1'
)->fetchAll();
foreach ($timed as $t) {
    // Check day-of-week
    if (strpos($t['days_of_week'], (string)$now_dow) === false) continue;
    // Handle overnight schedules (e.g. 22:00 → 06:00)
    $from = $t['block_from'];
    $to   = $t['block_to'];
    $active = ($from <= $to)
        ? ($now_time >= $from && $now_time <= $to)
        : ($now_time >= $from || $now_time <= $to);
    if ($active && !in_array($t['package_name'], $apps)) {
        $apps[] = $t['package_name'];
    }
}

// Safety-net: if block_settings_access is on but the DB rows were somehow
// removed, inject the settings packages into the response here as a fallback
// so the agent still enforces the block. Normally the packages are already
// present in blocked_apps/disabled_apps via the admin_policy save handler.
$blocked_activities = [];
if (!empty($policy['block_settings_access'])) {
    $settings_packages = [
        'com.android.settings',
        'com.android.settings.intelligence',
        'com.samsung.android.settings',
        'com.samsung.android.sm',
        'com.huawei.systemmanager',
        'com.motorola.settings',
        'com.lge.settings',
        'com.sonyericsson.settings',
        'com.htc.preference',
        'com.google.android.packageinstaller',
    ];
    foreach ($settings_packages as $sp) {
        if (!in_array($sp, $apps, true))          $apps[]          = $sp;
        if (!in_array($sp, $disabled_apps, true)) $disabled_apps[] = $sp;
    }

    // Explicit activity class names for App Info on AOSP, Samsung and common OEMs.
    // The agent should monitor UsageStatsManager topActivity.className and
    // immediately bring itself to the foreground if any of these are detected.
    // This covers cases where setApplicationHidden() is bypassed by direct intents.
    $blocked_activities = [
        // AOSP / stock Android
        'com.android.settings.applications.InstalledAppDetailsTop',
        'com.android.settings.applications.ApplicationDetailsActivity',
        'com.android.settings.applications.AppInfoWithHeader',
        'com.android.settings.applications.ManageApplications',
        'com.android.settings.Settings$AppInfoSettingsActivity',
        'com.android.settings.Settings',
        // Samsung One UI
        'com.samsung.android.settings.application.ApplicationsDetailsActivity',
        'com.samsung.android.settings.applications.InstalledAppDetailsTop',
        'com.samsung.android.settings.applications.ApplicationsDetails',
        'com.samsung.android.sm.ui.appmanagement.AppInfoActivity',
        // Huawei / Honor
        'com.huawei.systemmanager.appcontrol.activity.StartupNativeActivity',
        'com.huawei.systemmanager.optimize.process.ProtectActivity',
        // General permissions screen (all OEMs)
        'com.android.packageinstaller.permission.ui.AppPermissionsActivity',
        'com.android.permissioncontroller.permission.ui.AppPermissionsActivity',
    ];
}

// Pending commands for this device
$pending_commands = [];
if ($device_id > 0) {
    $cmd_stmt = $pdo->prepare(
        'SELECT id, command, payload FROM device_commands
         WHERE device_id = :id AND status = "pending"
         ORDER BY created_at ASC LIMIT 10'
    );
    $cmd_stmt->execute([':id' => $device_id]);
    $pending_commands = $cmd_stmt->fetchAll();
    foreach ($pending_commands as &$c) {
        $c['payload'] = $c['payload'] !== null ? json_decode($c['payload'], true) : null;
    }
    unset($c);
}

echo json_encode([
    // App control
    'blocked_apps'              => $apps,
    'disabled_apps'             => $disabled_apps,
    'allowed_apps'              => $allowed,
    'whitelist_mode'            => (bool)($curfew['whitelist_mode'] ?? false),
    'disable_installs'          => (bool)($policy['disable_installs'] ?? true),
    // Site / VPN / DNS
    'blocked_sites'             => $sites,
    'vpn_enabled'               => (bool)($policy['vpn_enabled'] ?? false),
    'dns_primary'               => $policy['dns_primary'] ?? '1.1.1.1',
    'dns_secondary'             => $policy['dns_secondary'] ?? '1.0.0.1',
    // Device admin controls
    'lock_settings'             => (bool)($policy['lock_settings'] ?? false),
    'kiosk_mode'                => (bool)($policy['kiosk_mode'] ?? false),
    'kiosk_app'                 => $policy['kiosk_app'] ?? null,
    'disable_status_bar'        => (bool)($policy['disable_status_bar'] ?? false),
    'disable_screenshot'        => (bool)($policy['disable_screenshot'] ?? false),
    'disable_camera'            => (bool)($policy['disable_camera'] ?? false),
    'disable_usb'               => (bool)($policy['disable_usb'] ?? false),
    'block_settings_access'     => (bool)($policy['block_settings_access'] ?? false),
    // Curfew
    'curfew_enabled'            => (bool)($curfew['enabled'] ?? false),
    'curfew_time'               => substr($curfew['curfew_time'] ?? '22:00:00', 0, 5),
    'curfew_password_hash'      => $curfew['unlock_password_hash'] ?? '',
    'uninstall_password_hash'        => $curfew['uninstall_password_hash'] ?? '',
    'uninstall_protection_enabled'   => (bool)($curfew['uninstall_protection_enabled'] ?? true),
    'tamper_lockdown'                => (bool)($curfew['tamper_lockdown'] ?? true),
    // Instructs the agent to call setUninstallBlocked() on itself so the
    // Uninstall button in Android App Info is completely non-interactive.
    'block_self_uninstall'           => (bool)($curfew['uninstall_protection_enabled'] ?? true),
    'mdm_package_name'               => $policy['mdm_package_name'] ?? 'com.mdm.agent',
    // Explicit activity class names the agent should intercept.
    // Monitor via UsageStatsManager and return to foreground immediately
    // if any of these appear as the top activity.
    'blocked_activities'             => $blocked_activities,
    // Commands
    'pending_commands'               => $pending_commands,
]);
