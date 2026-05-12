<?php
/**
 * POST /api/app_auth.php
 * Authenticates an APK user. Body: {"username":"...","password":"..."}
 * Returns: {"success":true,"user_id":1,"full_name":"..."}
 */
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'username and password required']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, password_hash, full_name, is_active FROM app_users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !(bool)$user['is_active'] || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    exit;
}

echo json_encode([
    'success'   => true,
    'user_id'   => (int)$user['id'],
    'full_name' => $user['full_name'],
]);
