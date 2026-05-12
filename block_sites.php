<?php
// Require login
require_once 'includes/auth.php';

$message = '';
// pushRulesToAllDevices() is defined in includes/db.php

// Add blocked site
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain'] ?? '');
    if ($domain !== '') {
        $stmt = $pdo->prepare('INSERT INTO blocked_sites (domain) VALUES (?)');
        $stmt->execute([$domain]);
        pushRulesToAllDevices($pdo);
        $message = 'Website blocked successfully';
    }
}

// Delete blocked site
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM blocked_sites WHERE id = ?');
    $stmt->execute([(int)$_GET['delete']]);
    pushRulesToAllDevices($pdo);
    header('Location: block_sites.php');
    exit;
}

$sites = $pdo->query('SELECT * FROM blocked_sites ORDER BY id DESC')->fetchAll();

$pageTitle = 'Blocked Sites';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
  <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill"></i>
    <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>

<div class="form-card">
  <h6><i class="bi bi-globe2 me-1"></i> Block a Website</h6>
  <form method="POST" class="row g-3">
    <div class="col-md-9">
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-globe2"></i></span>
        <input type="text" name="domain" class="form-control" placeholder="e.g. facebook.com" required>
      </div>
    </div>
    <div class="col-md-3">
      <button class="btn btn-danger w-100" type="submit">
        <i class="bi bi-ban me-1"></i> Block Site
      </button>
    </div>
  </form>
</div>

<div class="data-card">
  <div class="card-header">
    <h6><i class="bi bi-shield-x me-1"></i> Blocked Websites</h6>
    <span class="badge bg-danger"><?php echo count($sites); ?></span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr><th>#</th><th>Domain</th><th>Date Added</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($sites as $site): ?>
        <tr>
          <td><?php echo (int)$site['id']; ?></td>
          <td><i class="bi bi-globe2 text-muted me-1"></i><?php echo htmlspecialchars($site['domain']); ?></td>
          <td class="text-muted"><?php echo htmlspecialchars($site['created_at']); ?></td>
          <td>
            <a href="block_sites.php?delete=<?php echo (int)$site['id']; ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Remove <?php echo htmlspecialchars($site['domain'], ENT_QUOTES); ?> from blocklist?')">
              <i class="bi bi-trash3"></i> Remove
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($sites)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No sites blocked yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
?>