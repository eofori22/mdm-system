<?php
require_once 'includes/auth.php';

$message  = '';
$msgType  = 'success';
// pushRulesToAllDevices() is defined in includes/db.php

// ── Permanent block ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_block') {
        $pkg = trim($_POST['package_name'] ?? '');
        if ($pkg !== '' && preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z0-9_]+)+$/', $pkg)) {
            try {
                $pdo->prepare('INSERT INTO blocked_apps (package_name) VALUES (?)')->execute([$pkg]);
                pushRulesToAllDevices($pdo);
                $message = "App <code>" . htmlspecialchars($pkg) . "</code> blocked permanently.";
            } catch (PDOException) {
                $message = "That package is already in the permanent blocklist.";
                $msgType  = 'warning';
            }
        } else {
            $message = "Invalid package name."; $msgType = 'warning';
        }

    } elseif ($action === 'add_schedule') {
        $pkg   = trim($_POST['s_package'] ?? '');
        $label = trim($_POST['s_label']   ?? '');
        $from  = $_POST['s_from'] ?? '00:00';
        $to    = $_POST['s_to']   ?? '23:59';
        $days  = implode('', array_filter(array_map('intval', (array)($_POST['s_days'] ?? [])),
                    fn($d) => $d >= 1 && $d <= 7));
        if ($days === '') $days = '1234567';

        if ($pkg !== '' && preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z0-9_]+)+$/', $pkg)) {
            $pdo->prepare(
                'INSERT INTO timed_app_blocks (package_name, label, block_from, block_to, days_of_week, enabled)
                 VALUES (?, ?, ?, ?, ?, 1)'
            )->execute([$pkg, $label ?: $pkg, $from . ':00', $to . ':00', $days]);
            pushRulesToAllDevices($pdo);
            $message = "Schedule added for <code>" . htmlspecialchars($pkg) . "</code>.";
        } else {
            $message = "Invalid package name."; $msgType = 'warning';
        }

    } elseif ($action === 'toggle_schedule') {
        $sid     = (int)($_POST['sid'] ?? 0);
        $enabled = (int)($_POST['enabled'] ?? 0);
        if ($sid > 0) {
            $pdo->prepare('UPDATE timed_app_blocks SET enabled = ? WHERE id = ?')->execute([$enabled, $sid]);
            pushRulesToAllDevices($pdo);
            $message = "Schedule " . ($enabled ? "enabled" : "disabled") . ".";
        }

    } elseif ($action === 'delete_schedule') {
        $sid = (int)($_POST['sid'] ?? 0);
        if ($sid > 0) {
            $pdo->prepare('DELETE FROM timed_app_blocks WHERE id = ?')->execute([$sid]);
            pushRulesToAllDevices($pdo);
            $message = "Schedule removed.";
        }
    }
}

// ── Permanent block: delete via GET ───────────────────────────────────────────
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM blocked_apps WHERE id = ?')->execute([(int)$_GET['delete']]);
    pushRulesToAllDevices($pdo);
    header('Location: block_apps.php'); exit;
}

$permanentApps = $pdo->query('SELECT * FROM blocked_apps ORDER BY id DESC')->fetchAll();
$schedules     = $pdo->query('SELECT * FROM timed_app_blocks ORDER BY enabled DESC, label ASC')->fetchAll();

// Day map for display
$dayNames = ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun'];

$pageTitle = 'Block Apps';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-<?= $msgType==='success'?'check-circle-fill':'exclamation-triangle-fill' ?>"></i>
  <?= $message ?>
</div>
<?php endif; ?>

<!-- ── Play Store quick toggle ─────────────────────────────────────────────── -->
<?php
$psRow = null;
foreach ($schedules as $s) {
    if ($s['package_name'] === 'com.android.vending') { $psRow = $s; break; }
}
$psEnabled = $psRow && $psRow['enabled'];
?>
<div class="form-card mb-4">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
      <div style="width:42px;height:42px;background:#e8f0fe;border-radius:10px;display:flex;align-items:center;justify-content:center">
        <i class="bi bi-shop" style="font-size:1.3rem;color:#4285f4"></i>
      </div>
      <div>
        <div class="fw-bold" style="font-size:0.95rem">Google Play Store</div>
        <div class="text-muted" style="font-size:0.8rem">com.android.vending — block access on all devices</div>
      </div>
    </div>
    <?php if ($psRow): ?>
    <form method="POST" class="d-flex align-items-center gap-2">
      <input type="hidden" name="action"  value="toggle_schedule">
      <input type="hidden" name="sid"     value="<?= (int)$psRow['id'] ?>">
      <input type="hidden" name="enabled" value="<?= $psEnabled ? 0 : 1 ?>">
      <button type="submit" class="btn <?= $psEnabled ? 'btn-danger' : 'btn-success' ?>">
        <i class="bi bi-<?= $psEnabled ? 'unlock-fill' : 'lock-fill' ?> me-1"></i>
        <?= $psEnabled ? 'Unblock Play Store' : 'Block Play Store Now' ?>
      </button>
    </form>
    <?php else: ?>
    <form method="POST">
      <input type="hidden" name="action"    value="add_schedule">
      <input type="hidden" name="s_package" value="com.android.vending">
      <input type="hidden" name="s_label"   value="Google Play Store">
      <input type="hidden" name="s_from"    value="00:00">
      <input type="hidden" name="s_to"      value="23:59">
      <button type="submit" class="btn btn-danger">
        <i class="bi bi-lock-fill me-1"></i> Block Play Store Now
      </button>
    </form>
    <?php endif; ?>
  </div>
  <?php if ($psRow && $psEnabled): ?>
  <div class="alert alert-danger mt-3 mb-0 py-2" style="font-size:0.83rem">
    <i class="bi bi-shield-fill-exclamation me-1"></i>
    Play Store is currently <strong>blocked</strong> on all devices.
    Schedule: <?= htmlspecialchars(substr($psRow['block_from'],0,5)) ?> – <?= htmlspecialchars(substr($psRow['block_to'],0,5)) ?>
  </div>
  <?php endif; ?>
</div>

<!-- ── Timed / scheduled blocks ───────────────────────────────────────────── -->
<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-clock-fill me-1"></i> Scheduled App Blocks</h6>
    <span class="badge bg-info text-dark"><?= count($schedules) ?> rules</span>
  </div>

  <!-- Add schedule form -->
  <div class="p-3 border-bottom" style="background:#f8f9fa">
    <form method="POST" class="row g-3 align-items-end">
      <input type="hidden" name="action" value="add_schedule">
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:0.82rem">Package Name</label>
        <input type="text" name="s_package" class="form-control form-control-sm" placeholder="com.android.vending" required>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold" style="font-size:0.82rem">Label (optional)</label>
        <input type="text" name="s_label" class="form-control form-control-sm" placeholder="Play Store">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold" style="font-size:0.82rem">Block From</label>
        <input type="time" name="s_from" class="form-control form-control-sm" value="08:00" required>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold" style="font-size:0.82rem">Block Until</label>
        <input type="time" name="s_to" class="form-control form-control-sm" value="18:00" required>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold" style="font-size:0.82rem">Days</label>
        <div class="d-flex gap-1 flex-wrap">
          <?php foreach ($dayNames as $num => $name): ?>
          <div class="form-check form-check-inline me-0">
            <input class="form-check-input" type="checkbox" name="s_days[]" value="<?= $num ?>"
                   id="day<?= $num ?>" <?= $num < 6 ? 'checked' : '' ?>>
            <label class="form-check-label" for="day<?= $num ?>" style="font-size:0.75rem"><?= $name ?></label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-sm btn-primary w-100">
          <i class="bi bi-plus-lg"></i> Add
        </button>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr><th>App</th><th>Package</th><th>Block Window</th><th>Days</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($schedules)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No schedules yet.</td></tr>
        <?php else: foreach ($schedules as $s):
          // Is this rule currently active?
          $now     = date('H:i:s');
          $dow     = date('N');
          $inDay   = strpos($s['days_of_week'], (string)$dow) !== false;
          $f = $s['block_from']; $t = $s['block_to'];
          $inTime  = ($f <= $t) ? ($now >= $f && $now <= $t) : ($now >= $f || $now <= $t);
          $active  = $s['enabled'] && $inDay && $inTime;
        ?>
        <tr>
          <td>
            <span class="fw-semibold" style="font-size:0.88rem"><?= htmlspecialchars($s['label']) ?></span>
            <?php if ($active): ?>
            <span class="badge bg-danger ms-1" style="font-size:0.65rem">ACTIVE NOW</span>
            <?php endif; ?>
          </td>
          <td><code style="font-size:0.76rem"><?= htmlspecialchars($s['package_name']) ?></code></td>
          <td class="text-muted" style="font-size:0.85rem">
            <?= htmlspecialchars(substr($s['block_from'],0,5)) ?> – <?= htmlspecialchars(substr($s['block_to'],0,5)) ?>
          </td>
          <td style="font-size:0.8rem">
            <?php foreach (str_split($s['days_of_week']) as $d):
                echo '<span class="badge bg-secondary me-1">' . ($dayNames[$d] ?? $d) . '</span>';
            endforeach; ?>
          </td>
          <td>
            <span class="badge <?= $s['enabled'] ? 'bg-success' : 'bg-secondary' ?>">
              <?= $s['enabled'] ? 'Enabled' : 'Disabled' ?>
            </span>
          </td>
          <td class="d-flex gap-1">
            <form method="POST" style="display:inline">
              <input type="hidden" name="action"  value="toggle_schedule">
              <input type="hidden" name="sid"     value="<?= (int)$s['id'] ?>">
              <input type="hidden" name="enabled" value="<?= $s['enabled'] ? 0 : 1 ?>">
              <button type="submit" class="btn btn-sm <?= $s['enabled'] ? 'btn-outline-warning' : 'btn-outline-success' ?> py-0 px-2">
                <i class="bi bi-<?= $s['enabled'] ? 'pause-fill' : 'play-fill' ?>"></i>
                <?= $s['enabled'] ? 'Disable' : 'Enable' ?>
              </button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete_schedule">
              <input type="hidden" name="sid"    value="<?= (int)$s['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"
                      onclick="return confirm('Delete this schedule?')">
                <i class="bi bi-trash3"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="px-3 py-2 text-muted" style="font-size:0.75rem;border-top:1px solid #f1f4f9">
    <i class="bi bi-info-circle me-1"></i>
    Devices check rules every 30 seconds. A blocked app is automatically unblocked when its time window ends.
    Overnight windows (e.g. 22:00 – 06:00) are supported.
  </div>
</div>

<!-- ── Permanent blocklist ─────────────────────────────────────────────────── -->
<div class="data-card">
  <div class="card-header">
    <h6><i class="bi bi-shield-x me-1"></i> Permanent Blocklist</h6>
    <span class="badge bg-danger"><?= count($permanentApps) ?></span>
  </div>

  <div class="p-3 border-bottom" style="background:#f8f9fa">
    <form method="POST" class="row g-2 align-items-end">
      <input type="hidden" name="action" value="add_block">
      <div class="col-md-9">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="bi bi-android2"></i></span>
          <input type="text" name="package_name" class="form-control" placeholder="com.example.app" required>
        </div>
      </div>
      <div class="col-md-3">
        <button class="btn btn-sm btn-danger w-100" type="submit">
          <i class="bi bi-ban me-1"></i> Block Permanently
        </button>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>#</th><th>Package Name</th><th>Date Added</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($permanentApps as $app): ?>
        <tr>
          <td><?= (int)$app['id'] ?></td>
          <td><code><?= htmlspecialchars($app['package_name']) ?></code></td>
          <td class="text-muted"><?= htmlspecialchars($app['created_at']) ?></td>
          <td>
            <a href="block_apps.php?delete=<?= (int)$app['id'] ?>"
               class="btn btn-sm btn-outline-danger py-0 px-2"
               onclick="return confirm('Remove this app from blocklist?')">
              <i class="bi bi-trash3"></i> Remove
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($permanentApps)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No apps permanently blocked.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
