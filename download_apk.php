<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/auth.php';

$apkPath = realpath(__DIR__ . '/../MDMAgent.apk');

// Ensure the resolved path is within the expected directory (prevent path traversal)
$allowedDir = realpath(__DIR__ . '/..');
if ($apkPath === false || strpos($apkPath, $allowedDir . '/') !== 0 || !is_file($apkPath)) {
    http_response_code(404);
    exit('APK file not found on server.');
}

$filename = basename($apkPath);
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($apkPath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($apkPath);
exit;
