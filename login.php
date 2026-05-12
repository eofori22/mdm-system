<?php
// Load database connection
require_once 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Get admin by username
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    // Verify password with bcrypt hash
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In — MDM System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assests/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-box">

    <div class="login-icon">
      <i class="bi bi-shield-lock-fill"></i>
    </div>
    <h3>MDM System</h3>
    <p class="login-sub">Sign in to your admin account</p>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2 px-3 mb-3">
        <i class="bi bi-exclamation-circle me-1"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" name="username" class="form-control"
                 placeholder="Enter username" required autocomplete="username">
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="password" class="form-control"
                 placeholder="Enter password" required autocomplete="current-password">
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
      </button>
    </form>

  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
</html>