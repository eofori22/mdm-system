<?php
require_once 'includes/auth.php';

$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'block_app') {
        $pkg = trim($_POST['package_name'] ?? '');
        if ($pkg !== '' && preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z0-9_]+)+$/', $pkg)) {
            try {
                $pdo->prepare('INSERT INTO blocked_apps (package_name) VALUES (?)')->execute([$pkg]);
                $message = "App <code>" . htmlspecialchars($pkg) . "</code> blocked on all devices.";
            } catch (PDOException $e) {
                $message = "That app is already in the blocklist.";
                $msgType = 'warning';
            }
        }
    } elseif ($_POST['action'] === 'send_command') {
        $device_id = (int)($_POST['device_id'] ?? 0);
        $command   = strtoupper(trim($_POST['command'] ?? ''));
        if ($device_id > 0 && in_array($command, ['LOCK','UNLOCK','WIPE','REBOOT','PUSH_RULES'], true)) {
            $pdo->prepare('INSERT INTO device_commands (device_id, command) VALUES (?, ?)')->execute([$device_id, $command]);
            $message = "Command <strong>{$command}</strong> queued for device #{$device_id}.";
        }
    }
}

$devices       = $pdo->query('SELECT id, device_name, status FROM devices ORDER BY device_name')->fetchAll();
$sel_device_id = (int)($_GET['device_id'] ?? 0);
$sel_device    = null;
$apps          = [];

if ($sel_device_id > 0) {
    $d = $pdo->prepare('SELECT id, device_name, status FROM devices WHERE id = ?');
    $d->execute([$sel_device_id]);
    $sel_device = $d->fetch();
    if ($sel_device) {
        $a = $pdo->prepare('SELECT package_name, app_label, version_name, is_system, last_seen FROM installed_apps WHERE device_id = ? ORDER BY is_system ASC, app_label ASC');
        $a->execute([$sel_device_id]);
        $apps = $a->fetchAll();
    }
}

$summary = $pdo->query(
    'SELECT ia.device_id, d.device_name, d.status,
            SUM(ia.is_system = 0) AS user_apps,
            SUM(ia.is_system = 1) AS system_apps,
            COUNT(*) AS total_apps, MAX(ia.last_seen) AS last_report
     FROM installed_apps ia LEFT JOIN devices d ON ia.device_id = d.id
     GROUP BY ia.device_id, d.device_name, d.status ORDER BY d.device_name'
)->fetchAll();

$pending_count = (int)$pdo->query('SELECT COUNT(*) FROM device_commands WHERE status = "pending"')->fetchColumn();

$pageTitle = 'Installed Apps';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-<?= $msgType==='success'?'check-circle-fill':'exclamation-triangle-fill' ?>"></i>
  <?= $message ?>
</div>
<?php endif; ?>

<!-- Remote Commands Panel -->
<div class="form-card mb-4">
  <h6><i class="bi bi-terminal-fill me-1"></i> Remote Device Control</h6>
  <p class="text-muted mb-3" style="font-size:0.85rem">
    Queue a command — the device executes it on its next poll (within ~30 s).
    <?php if ($pending_count > 0): ?>
      <span class="badge bg-warning text-dark ms-2"><?= $pending_count ?> pending</span>
    <?php endif; ?>
  </p>
  <form method="POST" class="row g-3 align-items-end">
    <input type="hidden" name="action" value="send_command">
    <div class="col-md-5">
      <label class="form-label fw-semibold">Device</label>
      <select name="device_id" class="form-select" required>
        <option value="">— select device —</option>
        <?php foreach ($devices as $dev): ?>
        <option value="<?= (int)$dev['id'] ?>"><?= htmlspecialchars($dev['device_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Command</label>
      <select name="command" class="form-select" required>
        <option value="LOCK">Lock Screen</option>
        <option value="UNLOCK">Unlock Screen</option>
        <option value="PUSH_RULES">Push Updated Rules</option>
        <option value="REBOOT">Reboot Device</option>
        <option value="WIPE">Factory Wipe</option>
      </select>
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-primary w-100"
        onclick="return this.form.command.value==='WIPE'?confirm('WIPE will erase ALL device data. Continue?'):true">
        <i class="bi bi-send me-1"></i> Send Command
      </button>
    </div>
  </form>
</div>

<!-- Device Selector -->
<div class="form-card mb-4">
  <h6><i class="bi bi-phone me-1"></i> View Installed Apps per Device</h6>
  <form method="GET" class="row g-3 align-items-end">
    <div class="col-md-8">
      <select name="device_id" class="form-select">
        <option value="">— select a device —</option>
        <?php foreach ($devices as $dev): ?>
        <option value="<?= (int)$dev['id'] ?>" <?= $sel_device_id===(int)$dev['id']?'selected':'' ?>>
          <?= htmlspecialchars($dev['device_name']) ?> (<?= htmlspecialchars($dev['status']) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> View Apps</button>
    </div>
  </form>
</div>

<?php if ($sel_device): ?>
<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-grid-3x3-gap-fill me-1"></i> Apps on <strong><?= htmlspecialchars($sel_device['device_name']) ?></strong></h6>
    <span class="badge bg-primary"><?= count($apps) ?> apps</span>
  </div>
  <div class="p-3 border-bottom" style="background:#f8f9fa">
    <form method="POST" class="row g-2 align-items-center">
      <input type="hidden" name="action" value="block_app">
      <div class="col-auto"><span class="text-muted" style="font-size:0.85rem"><i class="bi bi-ban me-1"></i>Quick-block:</span></div>
      <div class="col-md-6"><input type="text" name="package_name" class="form-control form-control-sm" placeholder="com.example.app" required></div>
      <div class="col-auto"><button type="submit" class="btn btn-sm btn-danger">Block Globally</button></div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
      <thead><tr><th>App</th><th>Package</th><th>Version</th><th>Type</th><th>Last Seen</th><th>Action</th></tr></thead>
      <tbody>
        <?php if (empty($apps)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i> No app data yet — device hasn't reported its app list.</td></tr>
        <?php else: foreach ($apps as $app): ?>
        <tr>
          <td><?= htmlspecialchars($app['app_label'] ?: $app['package_name']) ?></td>
          <td><code style="font-size:0.78rem"><?= htmlspecialchars($app['package_name']) ?></code></td>
          <td class="text-muted"><?= htmlspecialchars($app['version_name'] ?: '—') ?></td>
          <td><?= $app['is_system'] ? '<span class="badge bg-secondary">System</span>' : '<span class="badge bg-info text-dark">User</span>' ?></td>
          <td class="text-muted" style="font-size:0.82rem"><?= htmlspecialchars($app['last_seen']) ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="block_app">
              <input type="hidden" name="package_name" value="<?= htmlspecialchars($app['package_name']) ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"><i class="bi bi-ban"></i> Block</button>
            </form>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- All-Devices Overview -->
<div class="data-card">
  <div class="card-header">
    <h6><i class="bi bi-collection me-1"></i> App Inventory Overview</h6>
    <span class="badge bg-secondary"><?= count($summary) ?> devices reporting</span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>Device</th><th>Status</th><th>User Apps</th><th>System Apps</th><th>Total</th><th>Last Report</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($summary)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No devices have reported installed apps yet.</td></tr>
        <?php else: foreach ($summary as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['device_name'] ?? 'Unknown') ?></td>
          <td><span class="badge-status badge-<?= htmlspecialchars($row['status'] ?? 'offline') ?>"><i class="bi bi-circle-fill" style="font-size:0.4rem"></i> <?= htmlspecialchars($row['status'] ?? 'offline') ?></span></td>
          <td><?= (int)$row['user_apps'] ?></td>
          <td><?= (int)$row['system_apps'] ?></td>
          <td><strong><?= (int)$row['total_apps'] ?></strong></td>
          <td class="text-muted" style="font-size:0.82rem"><?= htmlspecialchars($row['last_report']) ?></td>
          <td><a href="installed_apps.php?device_id=<?= (int)$row['device_id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-eye"></i> View</a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
