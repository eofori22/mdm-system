  </div><!-- end content-wrap -->
</div><!-- end page-wrapper -->

<!-- Mobile bottom navigation (visible on phones ≤767px) -->
<nav class="mobile-nav">
  <a href="dashboard.php" class="mobile-nav-item <?php echo $currentPage==='dashboard.php'?'active':''; ?>">
    <i class="bi bi-speedometer2"></i><span>Home</span>
  </a>
  <a href="devices.php" class="mobile-nav-item <?php echo $currentPage==='devices.php'?'active':''; ?>">
    <i class="bi bi-tablet"></i><span>Devices</span>
  </a>
  <a href="block_sites.php" class="mobile-nav-item <?php echo $currentPage==='block_sites.php'?'active':''; ?>">
    <i class="bi bi-globe2"></i><span>Sites</span>
  </a>
  <a href="policies.php" class="mobile-nav-item <?php echo $currentPage==='policies.php'?'active':''; ?>">
    <i class="bi bi-moon-stars-fill"></i><span>Curfew</span>
  </a>
  <a href="block_apps.php" class="mobile-nav-item <?php echo $currentPage==='block_apps.php'?'active':''; ?>">
    <i class="bi bi-app-indicator"></i><span>Apps</span>
  </a>
  <a href="logs.php" class="mobile-nav-item <?php echo $currentPage==='logs.php'?'active':''; ?>">
    <i class="bi bi-journal-text"></i><span>Logs</span>
  </a>
  <a href="logout.php" class="mobile-nav-item mobile-nav-logout">
    <i class="bi bi-box-arrow-right"></i><span>Logout</span>
  </a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>