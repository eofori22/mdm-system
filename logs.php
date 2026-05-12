<?php
require_once 'includes/auth.php';

$logs = $pdo->query(
    'SELECT l.id, d.device_name, l.message, l.created_at
     FROM logs l
     LEFT JOIN devices d ON l.device_id = d.id
     ORDER BY l.created_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Logs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <h2>Device Logs</h2>
    <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>#</th>
            <th>Device</th>
            <th>Message</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td><?php echo (int)$log['id']; ?></td>
            <td><?php echo htmlspecialchars($log['device_name'] ?? 'Unknown'); ?></td>
            <td><?php echo htmlspecialchars($log['message']); ?></td>
            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?>
          <tr><td colspan="4" class="text-center text-muted">No logs yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
