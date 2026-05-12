<?php
require_once 'includes/auth.php';

$message = '';
$msgType = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username  = trim($_POST['username'] ?? '');
        $fullname  = trim($_POST['full_name'] ?? '');
        $password  = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $message = 'Username and password are required.';
            $msgType = 'danger';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $message = 'Username must be 3-30 characters (letters, numbers, underscores only).';
            $msgType = 'danger';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
            $msgType = 'danger';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO app_users (username, password_hash, full_name) VALUES (?, ?, ?)')
                    ->execute([$username, $hash, $fullname]);
                $message = "User <strong>" . htmlspecialchars($username) . "</strong> created successfully.";
            } catch (PDOException $e) {
                $message = 'That username is already taken.';
                $msgType = 'warning';
            }
        }
    }

    elseif ($action === 'delete') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid > 0) {
            $pdo->prepare('DELETE FROM app_users WHERE id = ?')->execute([$uid]);
            $message = 'User deleted.';
        }
    }

    elseif ($action === 'toggle') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid > 0) {
            $pdo->prepare('UPDATE app_users SET is_active = NOT is_active WHERE id = ?')->execute([$uid]);
            $message = 'User status updated.';
        }
    }

    elseif ($action === 'reset_password') {
        $uid      = (int)($_POST['user_id'] ?? 0);
        $newpass  = $_POST['new_password'] ?? '';
        if ($uid > 0 && strlen($newpass) >= 6) {
            $hash = password_hash($newpass, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE app_users SET password_hash = ? WHERE id = ?')->execute([$hash, $uid]);
            $message = 'Password updated successfully.';
        } else {
            $message = 'Password must be at least 6 characters.';
            $msgType = 'danger';
        }
    }
}

$users = $pdo->query('SELECT id, username, full_name, is_active, created_at FROM app_users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'APK Users';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-<?= $msgType==='success'?'check-circle-fill':'exclamation-triangle-fill' ?>"></i>
  <?= $message ?>
</div>
<?php endif; ?>

<!-- Add User Form -->
<div class="form-card mb-4">
  <h6><i class="bi bi-person-plus-fill me-1"></i> Add APK User</h6>
  <p class="text-muted mb-3" style="font-size:0.85rem">
    Create credentials that device operators use to log in to the MDM Agent app.
  </p>
  <form method="POST" class="row g-3">
    <input type="hidden" name="action" value="add">
    <div class="col-md-3">
      <label class="form-label fw-semibold">Username</label>
      <input type="text" name="username" class="form-control" placeholder="e.g. teacher01" required
        pattern="[a-zA-Z0-9_]{3,30}" title="3-30 chars, letters/numbers/underscores">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Full Name <span class="text-muted fw-normal">(optional)</span></label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Mr. Johnson">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold">Password</label>
      <div class="input-group">
        <input type="password" name="password" id="addPassword" class="form-control" placeholder="Min 6 characters" required minlength="6">
        <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('addPassword',this)">
          <i class="bi bi-eye"></i>
        </button>
      </div>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i> Add</button>
    </div>
  </form>
</div>

<!-- Users Table -->
<div class="data-card">
  <div class="card-header">
    <h6><i class="bi bi-people-fill me-1"></i> APK Users</h6>
    <span class="badge bg-secondary"><?= count($users) ?> users</span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr><th>Username</th><th>Full Name</th><th>Status</th><th>Created</th><th>Reset Password</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4">
            <i class="bi bi-people me-1"></i> No APK users yet. Add one above.
          </td>
        </tr>
        <?php else: foreach ($users as $u): ?>
        <tr class="<?= $u['is_active'] ? '' : 'table-secondary opacity-75' ?>">
          <td>
            <code><?= htmlspecialchars($u['username']) ?></code>
          </td>
          <td><?= htmlspecialchars($u['full_name'] ?: '—') ?></td>
          <td>
            <?php if ($u['is_active']): ?>
              <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
            <?php else: ?>
              <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i>Disabled</span>
            <?php endif; ?>
          </td>
          <td class="text-muted" style="font-size:0.82rem"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <form method="POST" class="d-flex gap-2 align-items-center" style="min-width:220px">
              <input type="hidden" name="action" value="reset_password">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <div class="input-group input-group-sm">
                <input type="password" name="new_password" id="pw_<?= $u['id'] ?>" class="form-control form-control-sm"
                  placeholder="New password" minlength="6" required>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="togglePwd('pw_<?= $u['id'] ?>',this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <button type="submit" class="btn btn-sm btn-outline-warning text-nowrap">
                <i class="bi bi-key me-1"></i>Reset
              </button>
            </form>
          </td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> me-1">
                <i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>-fill"></i>
                <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
              </button>
            </form>
            <form method="POST" style="display:inline"
              onsubmit="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>? This cannot be undone.')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  if (!inp) return;
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
  } else {
    inp.type = 'password';
    btn.innerHTML = '<i class="bi bi-eye"></i>';
  }
}
</script>

<?php require_once 'includes/footer.php'; ?>
