<?php
require_once '../includes/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
$body = json_decode(file_get_contents('php://input'), true);
$device_id = isset($body['device_id']) ? (int)$body['device_id'] : 0;
$message   = isset($body['message'])   ? trim($body['message'])   : '';
if ($device_id <= 0 || $message === '') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'device_id and message required']); exit; }
$pdo->prepare('INSERT INTO logs (device_id, message) VALUES (:d,:m)')->execute([':d'=>$device_id,':m'=>$message]);
echo json_encode(['success'=>true]);
