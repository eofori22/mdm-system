<?php
require_once 'includes/auth.php';

$message = '';
$msgType = 'success';

// ── POST actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = substr(trim($_POST['class_name'] ?? ''), 0, 80);
        if ($name === '') {
            $message = 'Class name cannot be empty.';
            $msgType = 'danger';
        } else {
            try {
                $pdo->prepare('INSERT INTO classes (name) VALUES (?)')->execute([$name]);
                $message = "Class \"" . htmlspecialchars($name) . "\" created.";
            } catch (PDOException $e) {
                $message = "A class with that name already exists.";
                $msgType = 'warning';
            }
        }

    } elseif ($action === 'rename') {
        $id      = (int)($_POST['class_id'] ?? 0);
        $newName = substr(trim($_POST['new_name'] ?? ''), 0, 80);
        if ($id > 0 && $newName !== '') {
            // Also rename on all assigned devices
            $oldName = $pdo->prepare('SELECT name FROM classes WHERE id=?');
            $oldName->execute([$id]);
            $old = $oldName->fetchColumn();
            $pdo->prepare('UPDATE classes SET name=? WHERE id=?')->execute([$newName, $id]);
            $pdo->prepare('UPDATE devices SET class_name=? WHERE class_name=?')->execute([$newName, $old]);
            $message = "Class renamed to \"" . htmlspecialchars($newName) . "\".";
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['class_id'] ?? 0);
        if ($id > 0) {
            $row = $pdo->prepare('SELECT name FROM classes WHERE id=?');
            $row->execute([$id]);
            $name = $row->fetchColumn();
            // Unassign devices before deleting
            $pdo->prepare("UPDATE devices SET class_name='' WHERE class_name=?")->execute([$name]);
            $pdo->prepare('DELETE FROM classes WHERE id=?')->execute([$id]);
            $message = "Class \"" . htmlspecialchars($name) . "\" deleted. Devices have been unassigned.";
            $msgType = 'warning';
        }
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$classes = $pdo->query(
    "SELECT c.id, c.name, c.created_at,
            COUNT(d.id) AS device_count
     FROM classes c
     LEFT JOIN devices d ON d.class_name = c.name AND d.status != 'removed'
     GROUP BY c.id
     ORDER BY c.name ASC"
)->fetchAll();

$unassigned = (int)$pdo->query(
    "SELECT COUNT(*) FROM devices WHERE (class_name='' OR class_name IS NULL) AND status != 'removed'"
)->fetchColumn();

$pageTitle = 'Classes';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $msgType; ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-<?php echo $msgType==='success' ? 'check-circle-fill' : ($msgType==='warning' ? 'exclamation-triangle-fill' : 'x-circle-fill'); ?>"></i>
  <?php echo $message; ?>
</div>
<?php endif; ?>

<!-- ── Add Class ──────────────────────────────────────────────────────────── -->
<div class="form-card mb-4">
  <h6><i class="bi bi-mortarboard me-1"></i> Add New Class</h6>
  <form method="POST" class="row g-3 align-items-end">
    <input type="hidden" name="action" value="add">
    <div class="col-md-8">
      <label class="form-label fw-semibold" style="font-size:0.85rem">Class Name</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-mortarboard"></i></span>
        <input type="text" name="class_name" class="form-control"
               placeholder="e.g. Grade 10A, Form 3B, Year 9 Science" maxlength="80" required>
      </div>
    </div>
    <div class="col-md-4">
      <button class="btn btn-success w-100" type="submit">
        <i class="bi bi-plus-lg me-1"></i> Add Class
      </button>
    </div>
  </form>
</div>

<!-- ── Classes Table ──────────────────────────────────────────────────────── -->
<div class="data-card">
  <div class="card-header">
    <h6><i class="bi bi-list-ul me-1"></i> All Classes</h6>
    <span class="badge bg-primary"><?php echo count($classes); ?></span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Class Name</th>
          <th>Devices Assigned</th>
          <th>Created</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($classes as $cls): ?>
        <tr>
          <td>
            <span class="badge" style="background:#EFF6FF;color:#1D4ED8;font-size:0.85rem;font-weight:600;padding:6px 12px">
              <i class="bi bi-mortarboard me-1"></i><?php echo htmlspecialchars($cls['name']); ?>
            </span>
          </td>
          <td>
            <a href="devices.php?class=<?php echo urlencode($cls['name']); ?>"
               class="text-decoration-none fw-semibold">
              <?php echo (int)$cls['device_count']; ?> device<?php echo $cls['device_count'] != 1 ? 's' : ''; ?>
            </a>
          </td>
          <td class="text-muted" style="font-size:0.8rem"><?php echo htmlspecialchars($cls['created_at']); ?></td>
          <td>
            <button class="btn btn-sm btn-outline-secondary me-1"
                    onclick="openRename(<?php echo (int)$cls['id']; ?>, <?php echo json_encode($cls['name']); ?>)"
                    title="Rename">
              <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" class="d-inline"
                  onsubmit="return confirm('Delete class &quot;<?php echo htmlspecialchars(addslashes($cls['name'])); ?>&quot;?\nDevices will be unassigned.')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="class_id" value="<?php echo (int)$cls['id']; ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete">
                <i class="bi bi-trash3"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($classes)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No classes created yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($unassigned > 0): ?>
  <div class="p-3 pt-0">
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-0" style="font-size:0.85rem">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span><strong><?php echo $unassigned; ?> active device<?php echo $unassigned > 1 ? 's are' : ' is'; ?> not assigned to any class.</strong>
      <a href="devices.php" class="alert-link ms-1">Assign them →</a></span>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ── Rename Modal ───────────────────────────────────────────────────────── -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="rename">
        <input type="hidden" name="class_id" id="renameClassId">
        <div class="modal-header" style="background:#1A2E4A">
          <h6 class="modal-title text-white"><i class="bi bi-pencil-square me-1"></i> Rename Class</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label fw-semibold" style="font-size:0.85rem">New Name</label>
          <input type="text" name="new_name" id="renameInput" class="form-control" maxlength="80" required>
          <div class="form-text mt-1">All devices in this class will be updated automatically.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" style="background:#1A2E4A;border:none">
            <i class="bi bi-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openRename(id, currentName) {
  document.getElementById('renameClassId').value = id;
  document.getElementById('renameInput').value   = currentName;
  new bootstrap.Modal(document.getElementById('renameModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
