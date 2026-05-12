<?php
require_once 'includes/auth.php';

$message = '';
$msgType = 'success';

$cfg = $pdo->query('SELECT * FROM curfew_settings WHERE id=1')->fetch();

// pushRulesToAllDevices() is defined in includes/db.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'curfew';

    if ($action === 'curfew') {
        $time    = preg_match('/^\d{2}:\d{2}$/', $_POST['curfew_time'] ?? '') ? $_POST['curfew_time'] : '22:00';
        $newPass = isset($_POST['unlock_password']) ? trim($_POST['unlock_password']) : '';

        if ($newPass !== '') {
            if (strlen($newPass) < 4 || strlen($newPass) > 32) {
                $message = 'Password must be 4-32 characters.';
                $msgType = 'danger';
            } else {
                $hash = hash('sha256', $newPass);
                $pdo->prepare('UPDATE curfew_settings SET enabled=1, curfew_time=:t, unlock_password_hash=:h WHERE id=1')
                    ->execute([':t' => $time . ':00', ':h' => $hash]);
                $message = 'Curfew schedule saved. Tablets will lock automatically at ' . $time . ' every night.';
            }
        } else {
            $pdo->prepare('UPDATE curfew_settings SET enabled=1, curfew_time=:t WHERE id=1')
                ->execute([':t' => $time . ':00']);
            $message = 'Curfew schedule saved. Tablets will lock automatically at ' . $time . ' every night.';
        }
        pushRulesToAllDevices($pdo);

    } elseif ($action === 'uninstall') {
        $newUninstallPass = trim($_POST['uninstall_password'] ?? '');
        $protection_on    = isset($_POST['uninstall_protection_enabled']) ? 1 : 0;

        if ($protection_on && $newUninstallPass === '' && ($cfg['uninstall_password_hash'] ?? '') === '') {
            $message = 'Please enter a password to enable uninstall protection.';
            $msgType = 'danger';
        } elseif ($newUninstallPass !== '' && (strlen($newUninstallPass) < 4 || strlen($newUninstallPass) > 32)) {
            $message = 'Uninstall password must be 4-32 characters.';
            $msgType = 'danger';
        } else {
            if ($newUninstallPass !== '') {
                $hash = hash('sha256', $newUninstallPass);
                $pdo->prepare(
                    'UPDATE curfew_settings SET uninstall_protection_enabled=:e, uninstall_password_hash=:h WHERE id=1'
                )->execute([':e' => $protection_on, ':h' => $hash]);
            } else {
                $pdo->prepare(
                    'UPDATE curfew_settings SET uninstall_protection_enabled=:e WHERE id=1'
                )->execute([':e' => $protection_on]);
            }
            pushRulesToAllDevices($pdo);
            $message = 'Uninstall protection settings updated. All devices will be notified.';
        }
    }

    $cfg = $pdo->query('SELECT * FROM curfew_settings WHERE id=1')->fetch();
}

$currentTime = substr($cfg['curfew_time'], 0, 5);
$pageTitle = 'Policies';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $msgType; ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-<?php echo $msgType==='success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
  <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-moon-stars-fill me-1" style="color:#1A2E4A"></i> Curfew Schedule</h6>
    <span class="badge bg-success">
      <i class="bi bi-clock-fill me-1"></i> Automatic
    </span>
  </div>
  <div class="p-3">
    <div class="alert alert-info d-flex align-items-start gap-2 mb-4 py-2" style="font-size:0.85rem">
      <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
      <div>
        <strong>Automatic Curfew</strong> — Tablets lock themselves every night at the time below.
        No manual activation needed. The admin unlock password lets students request access after lock.
      </div>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="curfew">
      <div class="row g-4">
        <div class="col-md-4">
          <label class="form-label fw-semibold" style="font-size:0.85rem">
            <i class="bi bi-clock me-1"></i>Automatic Lock Time
          </label>
          <input type="time" name="curfew_time" class="form-control"
                 value="<?php echo htmlspecialchars($currentTime); ?>" required>
          <div class="form-text">All tablets lock at this time every night.</div>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold" style="font-size:0.85rem">
            <i class="bi bi-key me-1"></i>Admin Unlock Password
          </label>
          <div class="input-group">
            <input type="password" name="unlock_password" class="form-control" id="passInput"
                   placeholder="Leave blank to keep current" autocomplete="new-password" maxlength="32">
            <button type="button" class="btn btn-outline-secondary" id="togglePass">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <div class="form-text">
            <?php echo $cfg['unlock_password_hash'] !== '' ? '&#10003; Password is set.' : '&#9888; No password set yet — tablets cannot be unlocked after curfew!'; ?>
          </div>
        </div>

        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100"
                  style="background:#1A2E4A;border:none;padding:10px">
            <i class="bi bi-save me-1"></i> Save Schedule
          </button>
        </div>
      </div>
    </form>

    <!-- Current status summary -->
    <div class="mt-4 p-3 rounded" style="background:#F0F4FF;border:1px solid #CBD5E1">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-dark" style="font-size:0.8rem">
          <i class="bi bi-moon-fill me-1"></i>
          Locks at <?php echo htmlspecialchars($currentTime); ?> every night
        </span>
        <?php if ($cfg['unlock_password_hash'] !== ''): ?>
        <span class="badge bg-success" style="font-size:0.8rem">
          <i class="bi bi-key-fill me-1"></i> Unlock password set
        </span>
        <?php else: ?>
        <span class="badge bg-warning text-dark" style="font-size:0.8rem">
          <i class="bi bi-exclamation-triangle me-1"></i> No unlock password
        </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-shield-lock-fill me-1" style="color:#1A2E4A"></i> Uninstall Protection</h6>
    <?php
      $upEnabled = (bool)($cfg['uninstall_protection_enabled'] ?? true);
      $upHasHash = ($cfg['uninstall_password_hash'] ?? '') !== '';
    ?>
    <span class="badge <?php echo ($upEnabled && $upHasHash) ? 'bg-success' : 'bg-secondary'; ?>">
      <?php echo ($upEnabled && $upHasHash) ? 'Active' : ($upHasHash ? 'Disabled' : 'Not Set'); ?>
    </span>
  </div>
  <div class="p-3">
    <p class="text-muted mb-4" style="font-size:0.875rem">
      When enabled, devices must enter this password before the MDM Agent can be uninstalled
      or Device Admin access removed. Applies to <strong>all enrolled devices</strong>.
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="uninstall">
      <div class="row g-4">
        <div class="col-12">
          <div class="form-check form-switch d-flex align-items-center gap-3" style="padding-left:0">
            <input class="form-check-input" type="checkbox" id="uninstallProtToggle"
                   name="uninstall_protection_enabled" role="switch"
                   style="width:3rem;height:1.5rem;cursor:pointer"
                   <?php echo $upEnabled ? 'checked' : ''; ?>>
            <label class="form-check-label fw-semibold" for="uninstallProtToggle" style="font-size:0.95rem">
              Enable Uninstall Protection on All Devices
            </label>
          </div>
          <?php if (!$upHasHash): ?>
          <div class="alert alert-warning mt-2 py-2 px-3 mb-0" style="font-size:0.82rem">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            No password set yet — protection will not activate until a password is saved below.
          </div>
          <?php endif; ?>
        </div>

        <div class="col-md-5">
          <label class="form-label fw-semibold" style="font-size:0.85rem">
            <i class="bi bi-shield-lock me-1"></i>
            <?php echo $upHasHash ? 'Change Uninstall Password' : 'Set Uninstall Password'; ?>
          </label>
          <div class="input-group">
            <input type="password" name="uninstall_password" class="form-control" id="uninstallPassInput"
                   placeholder="<?php echo $upHasHash ? 'Leave blank to keep current password' : 'Enter uninstall password'; ?>"
                   autocomplete="new-password" maxlength="32">
            <button type="button" class="btn btn-outline-secondary" id="toggleUninstallPass">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <div class="form-text">
            <?php echo $upHasHash
              ? '&#10003; Password is set. Leave blank to keep it unchanged.'
              : '&#9888; No password set — enter one to activate protection.'; ?>
          </div>
        </div>

        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-danger w-100" style="padding:10px">
            <i class="bi bi-shield-lock me-1"></i> Save Protection
          </button>
        </div>

        <div class="col-md-4 d-flex align-items-end">
          <div class="alert alert-info mb-0 py-2 px-3 w-100" style="font-size:0.78rem">
            <i class="bi bi-info-circle me-1"></i>
            Changes are pushed to all devices immediately via the next heartbeat.
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-info-circle me-1"></i> How It Works</h6>
  </div>
  <div class="p-3">
    <div class="row g-3">
      <div class="col-md-4">
        <div class="d-flex gap-3 align-items-start">
          <div class="stat-icon blue" style="width:38px;height:38px;font-size:1rem;flex-shrink:0">
            <i class="bi bi-moon"></i>
          </div>
          <div>
            <div class="fw-semibold" style="font-size:0.85rem">At Curfew Time</div>
            <div class="text-muted" style="font-size:0.78rem">
              All enrolled tablets automatically lock and display the curfew screen.
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="d-flex gap-3 align-items-start">
          <div class="stat-icon" style="width:38px;height:38px;font-size:1rem;flex-shrink:0;background:#fff3cd">
            <i class="bi bi-lock-fill" style="color:#b45309"></i>
          </div>
          <div>
            <div class="fw-semibold" style="font-size:0.85rem">Student Attempt</div>
            <div class="text-muted" style="font-size:0.78rem">
              When a student turns on the tablet, they see a lock screen requiring the admin password.
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="d-flex gap-3 align-items-start">
          <div class="stat-icon" style="width:38px;height:38px;font-size:1rem;flex-shrink:0;background:#dcfce7">
            <i class="bi bi-unlock-fill" style="color:#15803d"></i>
          </div>
          <div>
            <div class="fw-semibold" style="font-size:0.85rem">Admin Override</div>
            <div class="text-muted" style="font-size:0.78rem">
              Entering the correct admin password unlocks the tablet until 6:00 AM next morning.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('togglePass').addEventListener('click', function () {
  var inp = document.getElementById('passInput');
  var ico = this.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'bi bi-eye'; }
});
document.getElementById('toggleUninstallPass').addEventListener('click', function () {
  var inp = document.getElementById('uninstallPassInput');
  var ico = this.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'bi bi-eye'; }
});
</script>

<?php require_once 'includes/footer.php'; ?>
