<?php
// Start session
session_start();

// Remove all session data
session_unset();
session_destroy();

// Return to landing page
header('Location: index.php');
exit;