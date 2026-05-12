<?php
require_once 'includes/auth.php';

$message = '';
$msgType = 'success';

// Load classes for dropdowns
$classes = $pdo->query('SELECT name FROM classes ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);

// ── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'register';

    if ($action === 'register') {
        $deviceName  = substr(trim($_POST['device_name']   ?? ''), 0, 100);
        $serial      = substr(trim($_POST['serial_number'] ?? ''), 0, 100);
        $studentName = substr(trim($_POST['student_name']  ?? ''), 0, 120);
        $className   = substr(trim($_POST['class_name']    ?? ''), 0, 80);

        if ($deviceName !== '' && $serial !== '') {
            $pdo->prepare(
                'INSERT INTO devices (device_name, student_name, serial_number, class_name)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     device_name=VALUES(device_name),
                     student_name=VALUES(student_name),
                     class_name=VALUES(class_name)'
            )->execute([$deviceName, $studentName, $serial, $className]);
            $message = 'Device registered successfully.';
        } else {
            $message = 'Device name and serial number are required.';
            $msgType = 'danger';
        }

    } elseif ($action === 'edit') {
        $id          = (int)($_POST['device_id'] ?? 0);
        $studentName = substr(trim($_POST['student_name'] ?? ''), 0, 120);
        $className   = substr(trim($_POST['class_name']   ?? ''), 0, 80);
        $deviceName  = substr(trim($_POST['device_name']  ?? ''), 0, 100);
        if ($id > 0) {
            $pdo->prepare(
                'UPDATE devices SET student_name=?, class_name=?, device_name=? WHERE id=?'
            )->execute([$studentName, $className, $deviceName, $id]);
            $message = 'Device updated successfully.';
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['device_id'] ?? 0);
        if ($id > 0) {
            // Remove related records first to avoid FK issues
            foreach (['device_commands','device_locations','installed_apps','logs'] as $tbl) {
                try { $pdo->prepare("DELETE FROM $tbl WHERE device_id=?")->execute([$id]); } catch (PDOException $e) {}
            }
            $pdo->prepare('DELETE FROM devices WHERE id=?')->execute([$id]);
            $message = 'Device permanently deleted.';
            $msgType = 'warning';
        }
    }
}

// ── Filter by class ──────────────────────────────────────────────────────────
$filterClass = trim($_GET['class'] ?? '');

if ($filterClass !== '') {
    $stmt = $pdo->prepare('SELECT * FROM devices WHERE class_name=? ORDER BY student_name ASC, id DESC');
    $stmt->execute([$filterClass]);
} else {
    $stmt = $pdo->query('SELECT * FROM devices ORDER BY class_name ASC, student_name ASC, id DESC');
}
$devices = $stmt->fetchAll();

$removedCount = array_reduce($devices, fn($c, $d) => $c + ($d['status'] === 'removed' ? 1 : 0), 0);
$offlineCount = array_reduce($devices, fn($c, $d) => $c + ($d['status'] === 'offline' ? 1 : 0), 0);

// Count per class (all devices, for sidebar summary)
$classCounts = $pdo->query(
    "SELECT class_name, COUNT(*) AS cnt FROM devices WHERE status != 'removed' GROUP BY class_name ORDER BY class_name ASC"
)->fetchAll();

$pageTitle = 'Devices';
require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $msgType; ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-<?php echo $msgType === 'success' ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
  <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- ── Register Form ───────────────────────────────────────────────────────── -->
<div class="form-card mb-4">
  <h6><i class="bi bi-tablet me-1"></i> Register New Device</h6>
  <form method="POST" class="row g-3">
    <input type="hidden" name="action" value="register">
    <div class="col-md-3">
      <label class="form-label fw-semibold" style="font-size:0.85rem">User Full Name</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-person"></i></span>
        <input type="text" name="student_name" class="form-control" placeholder="e.g. John Smith" maxlength="120" required>
      </div>
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold" style="font-size:0.85rem">Class</label>
      <select name="class_name" class="form-select">
        <option value="">— No class —</option>
        <?php foreach ($classes as $cls): ?>
        <option value="<?php echo htmlspecialchars($cls); ?>"><?php echo htmlspecialchars($cls); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold" style="font-size:0.85rem">Device Name / Label</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-tablet"></i></span>
        <input type="text" name="device_name" class="form-control" placeholder="e.g. Tablet-01" maxlength="100" required>
      </div>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold" style="font-size:0.85rem">Serial Number</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-upc"></i></span>
        <input type="text" name="serial_number" class="form-control" placeholder="Serial Number" maxlength="100" required>
      </div>
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button class="btn btn-primary w-100" type="submit" title="Register">
        <i class="bi bi-plus-lg"></i>
      </button>
    </div>
  </form>
</div>

<!-- ── Class filter tabs / summary ────────────────────────────────────────── -->
<div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
  <a href="devices.php" class="btn btn-sm <?php echo $filterClass === '' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
     style="<?php echo $filterClass === '' ? 'background:#1A2E4A;border:none' : ''; ?>">
    All Devices
  </a>
  <?php foreach ($classCounts as $cc): ?>
  <a href="devices.php?class=<?php echo urlencode($cc['class_name']); ?>"
     class="btn btn-sm <?php echo $filterClass === $cc['class_name'] ? 'btn-primary' : 'btn-outline-secondary'; ?>"
     style="<?php echo $filterClass === $cc['class_name'] ? 'background:#1A2E4A;border:none' : ''; ?>">
    <?php echo htmlspecialchars($cc['class_name'] ?: 'Unassigned'); ?>
    <span class="badge bg-white text-dark ms-1" style="font-size:0.7rem"><?php echo (int)$cc['cnt']; ?></span>
  </a>
  <?php endforeach; ?>
  <a href="classes.php" class="btn btn-sm btn-outline-primary ms-auto">
    <i class="bi bi-pencil-square me-1"></i> Manage Classes
  </a>
</div>

<!-- ── Device Table ───────────────────────────────────────────────────────── -->
<div class="data-card">
  <div class="card-header">
    <h6><i class="bi bi-tablet me-1"></i>
      <?php echo $filterClass !== '' ? 'Class: ' . htmlspecialchars($filterClass) : 'All Devices'; ?>
    </h6>
    <span class="badge bg-primary"><?php echo count($devices); ?></span>
  </div>
  <div class="p-3 pb-0">
    <?php if ($removedCount > 0): ?>
    <div class="alert d-flex align-items-center gap-2 mb-3" style="background:#fee2e2;color:#991b1b;border:none">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <strong><?php echo $removedCount; ?> device<?php echo $removedCount > 1 ? 's have' : ' has'; ?> been removed from MDM control.</strong>
    </div>
    <?php endif; ?>
    <?php if ($offlineCount > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" style="font-size:0.875rem">
      <i class="bi bi-wifi-off"></i>
      <strong><?php echo $offlineCount; ?> device<?php echo $offlineCount > 1 ? 's are' : ' is'; ?> offline</strong> — no heartbeat for &gt;5 minutes.
    </div>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>User (Full Name)</th>
          <th>Class</th>
          <th>Device Label</th>
          <th>Serial / IMEI</th>
          <th>Status</th>
          <th>Last Seen</th>
          <th style="width:60px"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($devices as $device): ?>
        <tr<?php echo $device['status'] === 'removed' ? ' style="opacity:0.65"' : ''; ?>>
          <td class="text-muted" style="font-size:0.8rem"><?php echo (int)$device['id']; ?></td>

          <!-- User name -->
          <td>
            <?php if (!empty($device['student_name'])): ?>
            <span class="d-flex align-items-center gap-1">
              <i class="bi bi-person-fill text-primary" style="font-size:0.9rem"></i>
              <strong><?php echo htmlspecialchars($device['student_name']); ?></strong>
            </span>
            <?php else: ?>
            <span class="text-muted fst-italic" style="font-size:0.82rem">Unassigned</span>
            <?php endif; ?>
          </td>

          <!-- Class -->
          <td>
            <?php if (!empty($device['class_name'])): ?>
            <span class="badge" style="background:#EFF6FF;color:#1D4ED8;font-size:0.78rem;font-weight:600">
              <i class="bi bi-mortarboard me-1"></i><?php echo htmlspecialchars($device['class_name']); ?>
            </span>
            <?php else: ?>
            <span class="text-muted fst-italic" style="font-size:0.8rem">—</span>
            <?php endif; ?>
          </td>

          <td><?php echo htmlspecialchars($device['device_name']); ?></td>

          <td>
            <code style="font-size:0.78rem"><?php echo htmlspecialchars($device['serial_number']); ?></code>
            <?php if (!empty($device['imei']) && $device['imei'] !== $device['serial_number']): ?>
            <br><span class="text-muted" style="font-size:0.75rem">IMEI: <?php echo htmlspecialchars($device['imei']); ?></span>
            <?php endif; ?>
          </td>

          <td>
            <span class="badge-status badge-<?php echo $device['status']; ?>">
              <i class="bi bi-circle-fill" style="font-size:0.4rem"></i>
              <?php echo htmlspecialchars($device['status']); ?>
            </span>
          </td>

          <td class="text-muted" style="font-size:0.8rem">
            <?php
            if ($device['last_seen']) {
                $diff = time() - strtotime($device['last_seen']);
                if      ($diff < 60)    $age = $diff . 's ago';
                elseif  ($diff < 3600)  $age = round($diff/60) . 'm ago';
                elseif  ($diff < 86400) $age = round($diff/3600) . 'h ago';
                else                    $age = round($diff/86400) . 'd ago';
                $color = $diff > 1800 ? 'bg-danger' : ($diff > 300 ? 'bg-warning text-dark' : 'bg-success');
                echo htmlspecialchars($device['last_seen']);
                echo ' <span class="badge ' . $color . '" style="font-size:0.68rem">' . $age . '</span>';
            } else {
                echo '—';
            }
            ?>
          </td>

          <!-- Edit / Delete buttons -->
          <td>
            <div class="d-flex gap-1">
              <?php if ($device['status'] !== 'removed'): ?>
              <button class="btn btn-sm btn-outline-secondary"
                      title="Edit user &amp; class"
                      onclick="openEdit(<?php echo (int)$device['id'];?>,
                                        <?php echo json_encode($device['student_name']);?>,
                                        <?php echo json_encode($device['class_name']);?>,
                                        <?php echo json_encode($device['device_name']);?>)">
                <i class="bi bi-pencil"></i>
              </button>
              <?php endif; ?>
              <button class="btn btn-sm btn-outline-danger"
                      title="Delete device"
                      onclick="openDelete(<?php echo (int)$device['id'];?>, <?php echo json_encode($device['device_name'] . ' — ' . ($device['student_name'] ?: 'Unassigned'));?>)">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($devices)): ?>
        <tr><td colspan="8" class="text-center text-muted py-5">No devices found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Edit Modal ─────────────────────────────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="device_id" id="editDeviceId">
        <div class="modal-header" style="background:#1A2E4A">
          <h6 class="modal-title text-white"><i class="bi bi-pencil-square me-1"></i> Edit Device Assignment</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem">
              <i class="bi bi-tablet me-1"></i> Device Label
            </label>
            <input type="text" name="device_name" id="editDeviceName" class="form-control" maxlength="100" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem">
              <i class="bi bi-person me-1"></i> User Full Name
            </label>
            <input type="text" name="student_name" id="editStudentName" class="form-control"
                   placeholder="e.g. John Smith" maxlength="120">
            <div class="form-text">Leave blank to remove user assignment.</div>
          </div>
          <div class="mb-1">
            <label class="form-label fw-semibold" style="font-size:0.85rem">
              <i class="bi bi-mortarboard me-1"></i> Class
            </label>
            <select name="class_name" id="editClassName" class="form-select">
              <option value="">— No class —</option>
              <?php foreach ($classes as $cls): ?>
              <option value="<?php echo htmlspecialchars($cls); ?>"><?php echo htmlspecialchars($cls); ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">
              Need a new class? <a href="classes.php" target="_blank">Manage Classes</a>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" style="background:#1A2E4A;border:none">
            <i class="bi bi-save me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Delete Confirmation Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="device_id" id="deleteDeviceId">
        <div class="modal-header" style="background:#991B1B">
          <h6 class="modal-title text-white"><i class="bi bi-trash3 me-1"></i> Delete Device</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="mb-1">Permanently delete:</p>
          <p class="fw-bold" id="deleteDeviceLabel"></p>
          <div class="alert alert-danger py-2 mb-0" style="font-size:0.82rem">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            This removes all logs, commands, and location history for this device. <strong>Cannot be undone.</strong>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash3 me-1"></i> Delete Permanently
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEdit(id, studentName, className, deviceName) {
  document.getElementById('editDeviceId').value    = id;
  document.getElementById('editStudentName').value = studentName || '';
  document.getElementById('editDeviceName').value  = deviceName  || '';
  const sel = document.getElementById('editClassName');
  sel.value = className || '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}
function openDelete(id, label) {
  document.getElementById('deleteDeviceId').value    = id;
  document.getElementById('deleteDeviceLabel').textContent = label;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
