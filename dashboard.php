<?php
require_once 'includes/auth.php';

$deviceCount   = $pdo->query("SELECT COUNT(*) AS total FROM devices")->fetch()['total'];
$removedCount  = $pdo->query("SELECT COUNT(*) AS total FROM devices WHERE status='removed'")->fetch()['total'];
$siteCount     = $pdo->query("SELECT COUNT(*) AS total FROM blocked_sites")->fetch()['total'];
$appCount      = $pdo->query("SELECT COUNT(*) AS total FROM blocked_apps")->fetch()['total'];

// Devices that went silent in the last 24 hours (just marked removed)
$recentlyLost = $pdo->query(
    "SELECT device_name, serial_number, last_seen, removed_at
     FROM devices
     WHERE status = 'removed'
       AND removed_at > NOW() - INTERVAL 24 HOUR
     ORDER BY removed_at DESC"
)->fetchAll();

// Devices currently offline (heartbeat stopped but not yet removed)
$offlineDevices = $pdo->query(
    "SELECT COUNT(*) AS total FROM devices WHERE status='offline'"
)->fetch()['total'];

$recentDevices = $pdo->query(
    "SELECT device_name, student_name, status, last_seen FROM devices ORDER BY id DESC LIMIT 5"
)->fetchAll();

$recentLogs = $pdo->query(
    "SELECT l.message, l.created_at, d.device_name
     FROM logs l LEFT JOIN devices d ON l.device_id = d.id
     ORDER BY l.created_at DESC LIMIT 5"
)->fetchAll();

$pageTitle = 'Dashboard';
require_once 'includes/header.php';
?>

<?php $apkExists = file_exists(__DIR__ . '/../MDMAgent.apk'); ?>

<?php if (!empty($recentlyLost)): ?>
<div class="alert alert-danger d-flex align-items-start gap-3 mb-4" style="border-left:4px solid #dc3545">
  <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0 mt-1"></i>
  <div class="w-100">
    <strong><?php echo count($recentlyLost); ?> device<?php echo count($recentlyLost) > 1 ? 's have' : ' has'; ?> gone silent in the last 24 hours</strong>
    <span class="text-muted" style="font-size:0.82rem"> — MDM agent may have been uninstalled.</span>
    <table class="table table-sm mt-2 mb-0" style="font-size:0.82rem">
      <thead><tr><th>Device</th><th>Serial</th><th>Last Seen</th><th>Gone Silent</th></tr></thead>
      <tbody>
        <?php foreach ($recentlyLost as $rl): ?>
        <tr>
          <td><?php echo htmlspecialchars($rl['device_name']); ?></td>
          <td><code><?php echo htmlspecialchars($rl['serial_number']); ?></code></td>
          <td class="text-muted"><?php echo htmlspecialchars($rl['last_seen'] ?? '—'); ?></td>
          <td class="text-danger fw-semibold"><?php echo htmlspecialchars($rl['removed_at']); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($offlineDevices > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" style="font-size:0.875rem">
  <i class="bi bi-wifi-off flex-shrink-0"></i>
  <span><strong><?php echo $offlineDevices; ?> device<?php echo $offlineDevices > 1 ? 's are' : ' is'; ?> offline</strong> — no heartbeat for &gt;5 minutes. Will be marked removed if silent for 30 minutes.</span>
  <a href="devices.php" class="ms-auto btn btn-sm btn-warning">View Devices</a>
</div>
<?php endif; ?>
<div class="data-card apk-card mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="padding:18px 24px">
  <div class="d-flex align-items-center gap-3">
    <div class="stat-icon blue" style="width:46px;height:46px;font-size:1.3rem">
      <i class="bi bi-android2"></i>
    </div>
    <div>
      <div style="font-weight:700;color:#1e293b;font-size:0.95rem">MDM Agent APK</div>
      <div style="font-size:0.78rem;color:#64748b">Install on managed Android devices</div>
    </div>
  </div>
  <?php if ($apkExists): ?>
  <a href="download_apk.php"
     class="btn btn-primary d-flex align-items-center gap-2" style="background:#1A2E4A;border:none;padding:8px 20px;font-size:0.85rem">
    <i class="bi bi-download"></i> Download APK
    <span class="badge bg-secondary ms-1" style="font-size:0.7rem;font-weight:500">
      <?php echo round(filesize(__DIR__ . '/../MDMAgent.apk') / 1048576, 1); ?> MB
    </span>
  </a>
  <?php else: ?>
  <span class="text-muted" style="font-size:0.85rem"><i class="bi bi-exclamation-circle me-1"></i>APK not found on server</span>
  <?php endif; ?>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-tablet"></i></div>
      <div>
        <div class="stat-number"><?php echo (int)$deviceCount; ?></div>
        <div class="stat-label">Total Devices</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon red" style="background:#fee2e2;color:#991b1b"><i class="bi bi-shield-x"></i></div>
      <div>
        <div class="stat-number" style="<?php echo $removedCount > 0 ? 'color:#991b1b' : ''; ?>"><?php echo (int)$removedCount; ?></div>
        <div class="stat-label">Removed Devices</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-globe2"></i></div>
      <div>
        <div class="stat-number"><?php echo (int)$siteCount; ?></div>
        <div class="stat-label">Blocked Sites</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon amber"><i class="bi bi-app-indicator"></i></div>
      <div>
        <div class="stat-number"><?php echo (int)$appCount; ?></div>
        <div class="stat-label">Blocked Apps</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="data-card">
      <div class="card-header">
        <h6><i class="bi bi-tablet me-1"></i> Recent Devices</h6>
        <a href="devices.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>Student</th><th>Device</th><th>Status</th><th>Last Seen</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentDevices as $d): ?>
            <tr>
              <td>
                <?php if (!empty($d['student_name'])): ?>
                <span class="d-flex align-items-center gap-1">
                  <i class="bi bi-person-fill text-primary" style="font-size:0.85rem"></i>
                  <strong style="font-size:0.88rem"><?php echo htmlspecialchars($d['student_name']); ?></strong>
                </span>
                <?php else: ?>
                <span class="text-muted" style="font-size:0.82rem">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.85rem"><?php echo htmlspecialchars($d['device_name']); ?></td>
              <td>
                <span class="badge-status badge-<?php echo $d['status']; ?>">
                  <i class="bi bi-circle-fill" style="font-size:0.4rem"></i>
                  <?php echo htmlspecialchars($d['status']); ?>
                </span>
              </td>
              <td class="text-muted" style="font-size:0.8rem">
                <?php echo $d['last_seen'] ? htmlspecialchars($d['last_seen']) : '—'; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentDevices)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">No devices yet</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="data-card">
      <div class="card-header">
        <h6><i class="bi bi-journal-text me-1"></i> Recent Logs</h6>
        <a href="logs.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>Device</th><th>Message</th><th>Time</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentLogs as $l): ?>
            <tr>
              <td><?php echo htmlspecialchars($l['device_name'] ?? 'Unknown'); ?></td>
              <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?php echo htmlspecialchars($l['message']); ?>
              </td>
              <td class="text-muted" style="font-size:0.8rem">
                <?php echo htmlspecialchars($l['created_at']); ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentLogs)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">No logs yet</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
</html>