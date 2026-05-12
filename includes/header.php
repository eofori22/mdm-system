<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$adminName   = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($pageTitle ?? 'MDM System'); ?> — MDM System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assests/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-brand d-flex align-items-center gap-2">
    <div class="sidebar-logo"><i class="bi bi-shield-lock-fill"></i></div>
    <div>
      <div class="sidebar-title">MDM System</div>
      <div class="sidebar-subtitle">Tablet Control</div>
    </div>
  </div>

  <div class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <div class="nav-item">
      <a href="dashboard.php" class="nav-link <?php echo $currentPage==='dashboard.php'?'active':''; ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
    </div>
    <div class="nav-item">
      <a href="devices.php" class="nav-link <?php echo $currentPage==='devices.php'?'active':''; ?>">
        <i class="bi bi-tablet"></i> Devices
      </a>
    </div>
    <div class="nav-item">
      <a href="classes.php" class="nav-link <?php echo $currentPage==='classes.php'?'active':''; ?>">
        <i class="bi bi-mortarboard"></i> Classes
      </a>
    </div>
    <div class="nav-item">
      <a href="tracking.php" class="nav-link <?php echo $currentPage==='tracking.php'?'active':''; ?>">
        <i class="bi bi-geo-alt-fill"></i> Tracking
      </a>
    </div>

    <div class="sidebar-section">Policies</div>
    <div class="nav-item">
      <a href="policies.php" class="nav-link <?php echo $currentPage==='policies.php'?'active':''; ?>">
        <i class="bi bi-moon-stars-fill"></i> Curfew Schedule
      </a>
    </div>
    <div class="nav-item">
      <a href="lockdown.php" class="nav-link <?php echo $currentPage==='lockdown.php'?'active':''; ?>">
        <i class="bi bi-shield-lock-fill"></i> Lockdown Mode
      </a>
    </div>
    <div class="nav-item">
      <a href="block_sites.php" class="nav-link <?php echo $currentPage==='block_sites.php'?'active':''; ?>">
        <i class="bi bi-globe2"></i> Blocked Sites
      </a>
    </div>
    <div class="nav-item">
      <a href="block_apps.php" class="nav-link <?php echo $currentPage==='block_apps.php'?'active':''; ?>">
        <i class="bi bi-app-indicator"></i> Blocked Apps
      </a>
    </div>

    <div class="nav-item">
      <a href="installed_apps.php" class="nav-link <?php echo $currentPage==='installed_apps.php'?'active':''; ?>">
        <i class="bi bi-grid-3x3-gap-fill"></i> Installed Apps
      </a>
    </div>
    <div class="nav-item">
      <a href="admin_policy.php" class="nav-link <?php echo $currentPage==='admin_policy.php'?'active':''; ?>">
        <i class="bi bi-shield-fill-gear"></i> Admin Policy
      </a>
    </div>
    <div class="nav-item">
      <a href="app_users.php" class="nav-link <?php echo $currentPage==='app_users.php'?'active':''; ?>">
        <i class="bi bi-person-badge-fill"></i> APK Users
      </a>
    </div>

    <div class="sidebar-section">System</div>
    <div class="nav-item">
      <a href="logs.php" class="nav-link <?php echo $currentPage==='logs.php'?'active':''; ?>">
        <i class="bi bi-journal-text"></i> Logs
      </a>
    </div>
  </div>

  <div class="sidebar-footer">
    <a href="logout.php" class="nav-link">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</div>

<!-- Page wrapper -->
<div class="page-wrapper">
  <div class="topbar">
    <span class="topbar-title"><?php echo htmlspecialchars($pageTitle ?? 'MDM System'); ?></span>
    <div class="topbar-user">
      <div class="topbar-avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
      <span><?php echo htmlspecialchars($adminName); ?></span>
    </div>
  </div>
  <div class="content-wrap">