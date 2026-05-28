<?php
require_once __DIR__ . '/../db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'student') { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
$cert_id = intval($_POST['cert_id'] ?? 0);
$action = $_POST['action'] ?? 'approve';
$pdo = getPDO();
if ($action=='approve'){
  $stmt = $pdo->prepare('UPDATE certificates SET verified=1 WHERE cert_id=?');
  $stmt->execute([$cert_id]);
  echo json_encode(['success'=>true]);
} else {
  $stmt = $pdo->prepare('DELETE FROM certificates WHERE cert_id=?');
  $stmt->execute([$cert_id]);
  echo json_encode(['deleted'=>true]);
}
