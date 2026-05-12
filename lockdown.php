<?php
require_once 'includes/auth.php';

$message = '';
$msgType = 'success';
// pushRulesToAllDevices() is defined in includes/db.php


// ── Tamper protection settings ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'save_tamper_settings') {
        $uninstall_prot = isset($_POST['uninstall_protection_enabled']) ? 1 : 0;
        $tamper_lock    = isset($_POST['tamper_lockdown'])              ? 1 : 0;
        $pdo->prepare(
            'UPDATE curfew_settings
             SET uninstall_protection_enabled=:up, tamper_lockdown=:tl
             WHERE id=1'
        )->execute([':up' => $uninstall_prot, ':tl' => $tamper_lock]);
        pushRulesToAllDevices($pdo);
        $message = 'Tamper protection settings saved. All devices notified.';

    } elseif ($_POST['action'] === 'set_uninstall_password') {
        $pw  = $_POST['uninstall_password']         ?? '';
        $pw2 = $_POST['uninstall_password_confirm'] ?? '';
        if ($pw === '') {
            // Clear the password (allow uninstall without password)
            $pdo->prepare('UPDATE curfew_settings SET uninstall_password_hash="" WHERE id=1')->execute();
            pushRulesToAllDevices($pdo);
            $message = 'Uninstall bypass password cleared.';
            $msgType = 'warning';
        } elseif ($pw !== $pw2) {
            $message = 'Passwords do not match. Please try again.';
            $msgType = 'danger';
        } else {
            $hash = hash('sha256', $pw);
            $pdo->prepare('UPDATE curfew_settings SET uninstall_password_hash=:h WHERE id=1')
                ->execute([':h' => $hash]);
            pushRulesToAllDevices($pdo);
            $message = 'Uninstall bypass password updated.';
        }
    }
}

// ── Original lockdown mode handlers ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_whitelist') {
        $val = isset($_POST['whitelist_mode']) ? 1 : 0;
        $pdo->prepare('UPDATE curfew_settings SET whitelist_mode=:v WHERE id=1')
            ->execute([':v' => $val]);
        pushRulesToAllDevices($pdo);
        $message = $val ? 'Lockdown mode ENABLED — only whitelisted apps are accessible.' : 'Lockdown mode DISABLED — blacklist rules apply.';
    } elseif ($_POST['action'] === 'add_app') {
        $pkg   = trim($_POST['package_name'] ?? '');
        $label = trim($_POST['app_label'] ?? '');
        if ($pkg === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z][a-zA-Z0-9_]*)+$/', $pkg)) {
            $message = 'Invalid package name. Use format: com.example.app';
            $msgType = 'danger';
        } else {
            try {
                $pdo->prepare('INSERT INTO allowed_apps (package_name, app_label) VALUES (?, ?)')
                    ->execute([$pkg, $label !== '' ? $label : $pkg]);
                pushRulesToAllDevices($pdo);
                $message = "App <code>" . htmlspecialchars($pkg) . "</code> added to whitelist.";
            } catch (PDOException $e) {
                $message = 'That package is already in the whitelist.';
                $msgType = 'warning';
            }
        }
    }
}

// Delete allowed app
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM allowed_apps WHERE id=?')->execute([(int)$_GET['delete']]);
    pushRulesToAllDevices($pdo);
    header('Location: lockdown.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) {
    $message = 'App removed from whitelist.';
    $msgType = 'warning';
}

$cfg     = $pdo->query('SELECT whitelist_mode, uninstall_protection_enabled, tamper_lockdown, uninstall_password_hash FROM curfew_settings WHERE id=1')->fetch();
$wmOn        = (bool)($cfg['whitelist_mode']               ?? false);
$uninstProt  = (bool)($cfg['uninstall_protection_enabled'] ?? true);
$tamperLock  = (bool)($cfg['tamper_lockdown']              ?? true);
$hasUninstPw = !empty($cfg['uninstall_password_hash']);
$allowed = $pdo->query('SELECT * FROM allowed_apps ORDER BY app_label ASC')->fetchAll();

// Recent tamper events
$tamperLogs = $pdo->query(
    'SELECT l.id, l.message, l.created_at, d.device_name
     FROM logs l
     LEFT JOIN devices d ON d.id = l.device_id
     WHERE l.message LIKE "TAMPER ALERT%"
     ORDER BY l.created_at DESC
     LIMIT 10'
)->fetchAll();

$pageTitle = 'Lockdown Mode';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $msgType; ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-<?php echo $msgType==='success' ? 'check-circle-fill' : ($msgType==='warning' ? 'exclamation-triangle-fill' : 'x-circle-fill'); ?>"></i>
  <?php echo $message; ?>
</div>
<?php endif; ?>

<!-- ── Tamper Protection ────────────────────────────────────────── -->
<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-shield-fill-exclamation me-1" style="color:#dc3545"></i> APK Tamper Protection</h6>
    <span class="badge <?php echo $uninstProt ? 'bg-success' : 'bg-secondary'; ?>">
      <?php echo $uninstProt ? 'PROTECTED' : 'OFF'; ?>
    </span>
  </div>
  <div class="p-3">
    <?php
    $policy_pkg = $pdo->query('SELECT mdm_package_name FROM admin_policy WHERE id=1')->fetchColumn();
    ?>
    <p class="text-muted mb-3" style="font-size:0.875rem">
      When enabled, the MDM agent registers as a <strong>Device Administrator / Device Owner</strong>
      and calls <code>setUninstallBlocked()</code> on itself — making the
      <strong>Uninstall button in Android App Info completely non-interactive</strong>.
      Any deactivation or uninstall attempt is blocked and, if <em>Lock on Tamper</em> is on,
      immediately locks the device and reports here.
    </p>
    <div class="alert <?php echo $policy_pkg && $policy_pkg !== 'com.mdm.agent' ? 'alert-success' : 'alert-warning'; ?> d-flex align-items-center gap-2 mb-4 py-2" style="font-size:0.82rem">
      <i class="bi bi-android2 flex-shrink-0"></i>
      <span>
        MDM agent package: <code><?php echo htmlspecialchars($policy_pkg ?: 'com.mdm.agent'); ?></code>
        &mdash;
        <?php if ($policy_pkg && $policy_pkg !== 'com.mdm.agent'): ?>
          <span class="text-success fw-semibold">Package name configured.</span>
        <?php else: ?>
          <a href="admin_policy.php" class="fw-semibold">Set the correct package name in Admin Policy</a>
          to enable <code>setUninstallBlocked()</code>.
        <?php endif; ?>
      </span>
    </div>

    <!-- Protection toggles -->
    <form method="POST" class="mb-4">
      <input type="hidden" name="action" value="save_tamper_settings">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-check form-switch d-flex align-items-center gap-3" style="padding-left:0">
            <input class="form-check-input" type="checkbox" id="uninstProtToggle"
                   name="uninstall_protection_enabled" role="switch"
                   style="width:3rem;height:1.5rem;cursor:pointer"
                   <?php echo $uninstProt ? 'checked' : ''; ?>>
            <label class="form-check-label" for="uninstProtToggle">
              <strong>Enable Uninstall Protection</strong>
              <span class="d-block text-muted" style="font-size:0.82rem">
                Blocks uninstall &amp; deactivation attempts via Device Admin API
              </span>
            </label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-check form-switch d-flex align-items-center gap-3" style="padding-left:0">
            <input class="form-check-input" type="checkbox" id="tamperLockToggle"
                   name="tamper_lockdown" role="switch"
                   style="width:3rem;height:1.5rem;cursor:pointer"
                   <?php echo $tamperLock ? 'checked' : ''; ?>>
            <label class="form-check-label" for="tamperLockToggle">
              <strong>Lock Device on Tamper</strong>
              <span class="d-block text-muted" style="font-size:0.82rem">
                Auto-activates lockdown &amp; sends LOCK command when tampering is detected
              </span>
            </label>
          </div>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-sm btn-primary">
          <i class="bi bi-save me-1"></i> Save Protection Settings
        </button>
      </div>
    </form>

    <!-- Uninstall bypass password -->
    <hr class="my-3">
    <h6 class="mb-1" style="font-size:0.9rem">
      <i class="bi bi-key me-1"></i> Uninstall Bypass Password
    </h6>
    <p class="text-muted mb-3" style="font-size:0.82rem">
      Set a password an authorised person must enter on the device to legitimately uninstall
      the MDM agent. Leave blank to clear the password requirement.
    </p>
    <form method="POST" class="row g-2 align-items-end">
      <input type="hidden" name="action" value="set_uninstall_password">
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:0.85rem">New Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="uninstall_password" class="form-control"
                 placeholder="Leave blank to clear">
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:0.85rem">Confirm Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="uninstall_password_confirm" class="form-control"
                 placeholder="Repeat password">
        </div>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-outline-secondary w-100">
          <i class="bi bi-key-fill me-1"></i>
          <?php echo $hasUninstPw ? 'Change Password' : 'Set Password'; ?>
        </button>
      </div>
    </form>
    <?php if ($hasUninstPw): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mt-2 mb-0 py-2" style="font-size:0.82rem">
      <i class="bi bi-check-circle-fill"></i> Bypass password is set — device will prompt for it before uninstalling.
    </div>
    <?php else: ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mt-2 mb-0 py-2" style="font-size:0.82rem">
      <i class="bi bi-exclamation-triangle-fill"></i> No bypass password set — uninstall will only be blocked at the OS level.
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Tamper Events Log ──────────────────────────────────────────── -->
<?php if (!empty($tamperLogs)): ?>
<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-exclamation-octagon-fill me-1" style="color:#dc3545"></i> Recent Tamper Alerts</h6>
    <span class="badge bg-danger"><?php echo count($tamperLogs); ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead>
        <tr><th>Time</th><th>Device</th><th>Event</th></tr>
      </thead>
      <tbody>
        <?php foreach ($tamperLogs as $tl): ?>
        <tr>
          <td class="text-muted" style="font-size:0.8rem;white-space:nowrap"><?php echo htmlspecialchars($tl['created_at']); ?></td>
          <td><?php echo htmlspecialchars($tl['device_name'] ?? 'ID #' . $tl['id']); ?></td>
          <td>
            <span class="badge bg-danger">
              <i class="bi bi-shield-x me-1"></i><?php echo htmlspecialchars($tl['message']); ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── Status Banner ─────────────────────────────────────────── -->
<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-shield-lock-fill me-1" style="color:#1A2E4A"></i> Lockdown Mode</h6>
    <span class="badge <?php echo $wmOn ? 'bg-danger' : 'bg-secondary'; ?>">
      <?php echo $wmOn ? 'ACTIVE' : 'OFF'; ?>
    </span>
  </div>
  <div class="p-3">
    <p class="text-muted mb-4" style="font-size:0.875rem">
      When <strong>Lockdown Mode</strong> is enabled, tablets are restricted to
      <strong>only the apps listed below</strong>. All other apps become inaccessible.
      Disable to revert to the standard blocklist rules.
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="toggle_whitelist">
      <div class="d-flex align-items-center gap-4 flex-wrap">
        <div class="form-check form-switch d-flex align-items-center gap-3" style="padding-left:0">
          <input class="form-check-input" type="checkbox" id="wmToggle" name="whitelist_mode"
                 role="switch" style="width:3rem;height:1.5rem;cursor:pointer"
                 <?php echo $wmOn ? 'checked' : ''; ?>>
          <label class="form-check-label fw-semibold" for="wmToggle" style="font-size:0.95rem">
            <?php echo $wmOn ? '<span class="text-danger">Lockdown ON</span>' : '<span class="text-secondary">Lockdown OFF</span>'; ?>
          </label>
        </div>
        <button type="submit" class="btn btn-sm <?php echo $wmOn ? 'btn-outline-danger' : 'btn-outline-primary'; ?>">
          <i class="bi bi-arrow-repeat me-1"></i> Apply
        </button>
      </div>
    </form>

    <?php if ($wmOn): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 mt-4 mb-0" style="font-size:0.85rem">
      <i class="bi bi-shield-exclamation flex-shrink-0 mt-1"></i>
      <div>
        <strong>Lockdown is active.</strong> Tablets can only open apps in the whitelist below.
        Make sure essential apps (Phone, MDM Agent) are included before enabling.
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Add App ────────────────────────────────────────────────── -->
<div class="form-card mb-4">
  <h6><i class="bi bi-plus-circle me-1"></i> Add App to Whitelist</h6>
  <form method="POST" class="row g-3">
    <input type="hidden" name="action" value="add_app">
    <div class="col-md-5">
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-android2"></i></span>
        <input type="text" name="package_name" class="form-control"
               placeholder="Package  e.g. com.google.chrome" required>
      </div>
    </div>
    <div class="col-md-4">
      <input type="text" name="app_label" class="form-control" placeholder="Label  e.g. Chrome (optional)">
    </div>
    <div class="col-md-3">
      <button class="btn btn-success w-100" type="submit">
        <i class="bi bi-shield-check me-1"></i> Allow App
      </button>
    </div>
  </form>
</div>

<!-- ── Whitelist Table ─────────────────────────────────────────── -->
<div class="data-card">
  <div class="card-header">
    <h6><i class="bi bi-list-check me-1"></i> Whitelisted Apps</h6>
    <span class="badge bg-success"><?php echo count($allowed); ?></span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>App Label</th>
          <th>Package Name</th>
          <th>Added</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allowed as $i => $app): ?>
        <tr>
          <td><?php echo $i + 1; ?></td>
          <td>
            <span class="badge bg-light text-dark border">
              <i class="bi bi-app me-1"></i><?php echo htmlspecialchars($app['app_label']); ?>
            </span>
          </td>
          <td><code><?php echo htmlspecialchars($app['package_name']); ?></code></td>
          <td class="text-muted" style="font-size:0.8rem"><?php echo htmlspecialchars($app['created_at']); ?></td>
          <td>
            <a href="lockdown.php?delete=<?php echo (int)$app['id']; ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Remove <?php echo htmlspecialchars(addslashes($app['app_label'])); ?> from whitelist?')">
              <i class="bi bi-trash3"></i> Remove
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($allowed)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted py-4">
            <i class="bi bi-inbox me-1"></i> No apps in the whitelist yet.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
