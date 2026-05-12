<?php
require_once 'includes/auth.php';

$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_policy') {
        $vpn_enabled        = isset($_POST['vpn_enabled'])        ? 1 : 0;
        $dns_primary        = filter_var(trim($_POST['dns_primary']   ?? '1.1.1.1'), FILTER_VALIDATE_IP) ?: '1.1.1.1';
        $dns_secondary      = filter_var(trim($_POST['dns_secondary'] ?? '1.0.0.1'), FILTER_VALIDATE_IP) ?: '1.0.0.1';
        $disable_installs   = isset($_POST['disable_installs'])   ? 1 : 0;
        $lock_settings      = isset($_POST['lock_settings'])      ? 1 : 0;
        $kiosk_mode         = isset($_POST['kiosk_mode'])         ? 1 : 0;
        $kiosk_app          = substr(trim($_POST['kiosk_app'] ?? ''), 0, 200) ?: null;
        $disable_status_bar = isset($_POST['disable_status_bar']) ? 1 : 0;
        $disable_screenshot = isset($_POST['disable_screenshot']) ? 1 : 0;
        $disable_camera     = isset($_POST['disable_camera'])     ? 1 : 0;
        $disable_usb             = isset($_POST['disable_usb'])             ? 1 : 0;
        $block_settings_access   = isset($_POST['block_settings_access'])   ? 1 : 0;
        $mdm_package_name   = trim($_POST['mdm_package_name'] ?? 'com.mdm.agent');
        // Validate package name format
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z0-9_]+)+$/', $mdm_package_name)) {
            $mdm_package_name = 'com.mdm.agent';
        }
        $mdm_package_name = substr($mdm_package_name, 0, 200);

        $pdo->prepare(
            'UPDATE admin_policy SET
                vpn_enabled=:vpn, dns_primary=:dp, dns_secondary=:ds,
                disable_installs=:di, lock_settings=:ls, kiosk_mode=:km,
                kiosk_app=:ka, disable_status_bar=:dsb, disable_screenshot=:dss,
                disable_camera=:dc, disable_usb=:du, mdm_package_name=:mpn,
                block_settings_access=:bsa
             WHERE id=1'
        )->execute([
            ':vpn'=>$vpn_enabled, ':dp'=>$dns_primary, ':ds'=>$dns_secondary,
            ':di'=>$disable_installs, ':ls'=>$lock_settings, ':km'=>$kiosk_mode,
            ':ka'=>$kiosk_app, ':dsb'=>$disable_status_bar, ':dss'=>$disable_screenshot,
            ':dc'=>$disable_camera, ':du'=>$disable_usb, ':mpn'=>$mdm_package_name,
            ':bsa'=>$block_settings_access,
        ]);
        // Sync Settings & App Info block into the actual DB tables.
        // Permanently inserting into blocked_apps + disabled_apps means the
        // agent enforces the block through its normal (already-working)
        // foreground-monitor and setApplicationHidden() paths — no new agent
        // code required. auto_added=1 marks them as system-managed so they
        // are removed cleanly when the toggle is turned off.
        $settings_pkgs = [
            ['com.android.settings',               'System Settings'],
            ['com.android.settings.intelligence',  'Settings Intelligence / Suggestions'],
            ['com.samsung.android.settings',       'Samsung Settings'],
            ['com.samsung.android.sm',             'Samsung Device Care'],
            ['com.huawei.systemmanager',            'Huawei Settings'],
            ['com.motorola.settings',               'Motorola Settings'],
            ['com.lge.settings',                    'LG Settings'],
            ['com.sonyericsson.settings',           'Sony/Xperia Settings'],
            ['com.htc.preference',                  'HTC Settings'],
            ['com.google.android.packageinstaller', 'Package Installer / App Info'],
        ];
        $pkg_names = array_column($settings_pkgs, 0);
        $in_ph     = implode(',', array_fill(0, count($pkg_names), '?'));

        if ($block_settings_access) {
            $insB = $pdo->prepare('INSERT IGNORE INTO blocked_apps  (package_name, auto_added) VALUES (?, 1)');
            $insD = $pdo->prepare('INSERT IGNORE INTO disabled_apps (package_name, app_label, auto_added) VALUES (?, ?, 1)');
            foreach ($settings_pkgs as [$pkg, $label]) {
                $insB->execute([$pkg]);
                $insD->execute([$pkg, $label]);
            }
        } else {
            $pdo->prepare("DELETE FROM blocked_apps  WHERE package_name IN ($in_ph) AND auto_added=1")->execute($pkg_names);
            $pdo->prepare("DELETE FROM disabled_apps WHERE package_name IN ($in_ph) AND auto_added=1")->execute($pkg_names);
        }

        // Push PUSH_RULES to every enrolled device so the new policy takes
        // effect immediately rather than waiting for the next natural poll.
        $enrolled = $pdo->query('SELECT id FROM devices WHERE status != "removed"')->fetchAll(PDO::FETCH_COLUMN);
        $ins = $pdo->prepare('INSERT INTO device_commands (device_id, command) VALUES (?, "PUSH_RULES")');
        foreach ($enrolled as $did) { $ins->execute([(int)$did]); }

        $message = 'Policy saved and pushed to all devices.';
    } elseif ($_POST['action'] === 'disable_app') {
        $pkg   = trim($_POST['package_name'] ?? '');
        $label = trim($_POST['app_label']    ?? '');
        if ($pkg !== '' && preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\\.[a-zA-Z0-9_]+)+$/', $pkg)) {
            try {
                $pdo->prepare('INSERT INTO disabled_apps (package_name, app_label) VALUES (?, ?)')
                    ->execute([$pkg, $label ?: $pkg]);
                pushRulesToAllDevices($pdo);
                $message = "App <code>" . htmlspecialchars($pkg) . "</code> will be disabled on all devices.";
            } catch (PDOException $e) {
                $message = 'That app is already in the disabled list.';
                $msgType = 'warning';
            }
        }
    }
}

if (isset($_GET['enable_app'])) {
    $pdo->prepare('DELETE FROM disabled_apps WHERE id=?')->execute([(int)$_GET['enable_app']]);
    pushRulesToAllDevices($pdo);
    header('Location: admin_policy.php?saved=1');
    exit;
}
if (isset($_GET['saved'])) { $message = 'Change applied.'; }

$policy        = $pdo->query('SELECT * FROM admin_policy WHERE id=1')->fetch();
$disabled_apps = $pdo->query('SELECT * FROM disabled_apps ORDER BY app_label ASC')->fetchAll();

$pageTitle = 'Admin Policy';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-<?= $msgType==='success'?'check-circle-fill':'exclamation-triangle-fill'?>"></i>
  <?= $message ?>
</div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="action" value="save_policy">

<div class="form-card mb-4">
  <h6><i class="bi bi-shield-shaded me-1"></i> Local VPN &amp; DNS Filtering</h6>
  <p class="text-muted mb-3" style="font-size:0.85rem">
    When enabled, the app starts a <strong>local VPN tunnel</strong> on the device that intercepts
    all DNS queries and blocks domains listed in <a href="block_sites.php">Blocked Sites</a>.
    No external server needed — all filtering happens on-device using Android <code>VpnService</code>.
  </p>
  <div class="row g-3">
    <div class="col-12">
      <div class="form-check form-switch d-flex align-items-center gap-3" style="padding-left:0">
        <input class="form-check-input" type="checkbox" id="vpnToggle" name="vpn_enabled"
               role="switch" style="width:3rem;height:1.5rem;cursor:pointer"
               <?= ($policy['vpn_enabled'] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label fw-semibold" for="vpnToggle">
          Enable Local VPN (DNS-level site blocking)
        </label>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Primary DNS</label>
      <input type="text" name="dns_primary" class="form-control"
             value="<?= htmlspecialchars($policy['dns_primary'] ?? '1.1.1.1') ?>"
             placeholder="1.1.1.1" required>
      <div class="form-text">Used by the Android VpnService for all DNS lookups.</div>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Secondary DNS</label>
      <input type="text" name="dns_secondary" class="form-control"
             value="<?= htmlspecialchars($policy['dns_secondary'] ?? '1.0.0.1') ?>"
             placeholder="1.0.0.1" required>
    </div>
    <div class="col-12">
      <div class="p-3 rounded" style="background:#f0f7ff;border:1px solid #bfdbfe;font-size:0.82rem">
        <strong>How it works on Android:</strong> The MDM app creates a virtual TUN interface,
        intercepts every DNS packet (UDP port 53), checks the queried domain against the blocked list
        fetched from this server, and returns <code>NXDOMAIN</code> for blocked domains.
        All other traffic passes through unchanged. No root required.
      </div>
    </div>
  </div>
</div>

<div class="form-card mb-4">
  <h6><i class="bi bi-box-seam me-1"></i> App Install Restrictions</h6>
  <div class="form-check form-switch d-flex align-items-center gap-3 mb-0" style="padding-left:0">
    <input class="form-check-input" type="checkbox" id="diToggle" name="disable_installs"
           role="switch" style="width:3rem;height:1.5rem;cursor:pointer"
           <?= ($policy['disable_installs'] ?? 1) ? 'checked' : '' ?>>
    <label class="form-check-label" for="diToggle">
      <strong>Block new app installations</strong>
      <span class="d-block text-muted" style="font-size:0.82rem">
        Intercepts <code>ACTION_INSTALL_PACKAGE</code> and blocks sideloading via Device Owner API.
      </span>
    </label>
  </div>
</div>

<div class="form-card mb-4">
  <h6><i class="bi bi-sliders me-1"></i> Settings &amp; UI Restrictions</h6>
  <div class="row g-3">
    <?php
    $toggles = [
      ['lock_settings',         'Lock Settings App',              'Hides Settings icon via DPM setApplicationHidden()'],
      ['block_settings_access', 'Block Settings &amp; App Info',  'Agent actively closes Settings/App Info if user navigates there (monitors foreground app)'],
      ['disable_status_bar',    'Disable Status Bar',             'Blocks pull-down and quick settings panel'],
      ['disable_screenshot',    'Disable Screenshots',            'Sets WindowManager FLAG_SECURE on all windows'],
      ['disable_camera',        'Disable Camera',                 'DPM setCameraDisabled()'],
      ['disable_usb',           'Disable USB Data',               'DPM setUsbDataSignalingEnabled(false)'],
    ];
    foreach ($toggles as [$name, $label, $desc]):
    ?>
    <div class="col-md-6">
      <div class="form-check form-switch d-flex align-items-center gap-3" style="padding-left:0">
        <input class="form-check-input" type="checkbox" id="<?= $name ?>Toggle" name="<?= $name ?>"
               role="switch" style="width:2.5rem;height:1.3rem"
               <?= ($policy[$name] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label" for="<?= $name ?>Toggle">
          <strong><?= $label ?></strong>
          <span class="d-block text-muted" style="font-size:0.82rem"><?= $desc ?></span>
        </label>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="form-card mb-4">
  <h6><i class="bi bi-android2 me-1"></i> MDM Agent Package Name</h6>
  <p class="text-muted mb-3" style="font-size:0.85rem">
    The package name of the MDM agent APK installed on devices. Used by
    <strong>Tamper Protection</strong> to call
    <code>setUninstallBlocked()</code> — which makes the
    <strong>Uninstall button in Android App Info completely non-clickable</strong>.
    Requires the agent to be set as <em>Device Owner</em>.
  </p>
  <div class="row g-3 align-items-end">
    <div class="col-md-8">
      <label class="form-label fw-semibold">MDM Agent Package Name</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-android2"></i></span>
        <input type="text" name="mdm_package_name" class="form-control"
               value="<?= htmlspecialchars($policy['mdm_package_name'] ?? 'com.mdm.agent') ?>"
               placeholder="e.g. com.yourcompany.mdmagent" required>
      </div>
      <div class="form-text">Must match the <code>applicationId</code> in the APK's build.gradle exactly.</div>
    </div>
    <div class="col-md-4">
      <div class="p-2 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0;font-size:0.8rem">
        <i class="bi bi-info-circle text-success me-1"></i>
        The agent reads this via <code>rules.php</code> and applies
        <code>setUninstallBlocked(this, true)</code> automatically.
      </div>
    </div>
  </div>
</div>

<div class="form-card mb-4">
  <h6><i class="bi bi-fullscreen me-1"></i> Kiosk Mode (Single-App Lock)</h6>
  <p class="text-muted mb-3" style="font-size:0.85rem">
    Pins one app full-screen using Android <code>startLockTask()</code>.
    Users cannot leave without the admin password.
  </p>
  <div class="row g-3 align-items-center">
    <div class="col-md-4">
      <div class="form-check form-switch d-flex align-items-center gap-3" style="padding-left:0">
        <input class="form-check-input" type="checkbox" id="kmToggle" name="kiosk_mode"
               role="switch" style="width:2.5rem;height:1.3rem"
               <?= ($policy['kiosk_mode'] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label fw-semibold" for="kmToggle">Enable Kiosk Mode</label>
      </div>
    </div>
    <div class="col-md-8">
      <label class="form-label fw-semibold">Kiosk App Package</label>
      <input type="text" name="kiosk_app" class="form-control"
             value="<?= htmlspecialchars($policy['kiosk_app'] ?? '') ?>"
             placeholder="e.g. com.example.kiosk">
      <div class="form-text">Leave blank to use the MDM agent app itself as the kiosk.</div>
    </div>
  </div>
</div>

<div class="mb-4">
  <button type="submit" class="btn btn-primary px-4">
    <i class="bi bi-save me-1"></i> Save Policy
  </button>
  <span class="text-muted ms-3" style="font-size:0.82rem">
    Devices receive changes within ~30 seconds on next rules poll.
  </span>
</div>
</form>

<div class="form-card mb-4">
  <h6><i class="bi bi-slash-circle me-1"></i> Force-Disabled Apps</h6>
  <p class="text-muted mb-2" style="font-size:0.85rem">
    Apps below are hidden via <code>DevicePolicyManager.setApplicationHidden()</code> on all devices.
    Unlike the blocklist, disabling completely hides the app icon and prevents background activity.
  </p>
  <form method="POST" class="row g-2 mb-3">
    <input type="hidden" name="action" value="disable_app">
    <div class="col-md-5">
      <input type="text" name="package_name" class="form-control" placeholder="com.example.app" required>
    </div>
    <div class="col-md-4">
      <input type="text" name="app_label" class="form-control" placeholder="Display name (optional)">
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-danger w-100">
        <i class="bi bi-slash-circle me-1"></i> Disable App
      </button>
    </div>
  </form>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead><tr><th>#</th><th>App</th><th>Package</th><th>Added</th><th>Action</th></tr></thead>
      <tbody>
        <?php if (empty($disabled_apps)): ?>
        <tr><td colspan="5" class="text-center text-muted py-3">No apps force-disabled yet.</td></tr>
        <?php else: foreach ($disabled_apps as $a): ?>
        <tr>
          <td><?= (int)$a['id'] ?></td>
          <td><?= htmlspecialchars($a['app_label'] ?: $a['package_name']) ?></td>
          <td><code style="font-size:0.78rem"><?= htmlspecialchars($a['package_name']) ?></code></td>
          <td class="text-muted" style="font-size:0.82rem"><?= htmlspecialchars($a['created_at']) ?></td>
          <td>
            <a href="admin_policy.php?enable_app=<?= (int)$a['id'] ?>"
               class="btn btn-sm btn-outline-success py-0 px-2"
               onclick="return confirm('Re-enable this app on all devices?')">
              <i class="bi bi-check2"></i> Re-enable
            </a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
