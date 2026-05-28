<?php
require_once __DIR__ . '/db_connect.php';
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'not_auth']); exit; }

$data = $_POST;
$pdo = getPDO();
// upsert student row for this user
$stmt = $pdo->prepare('SELECT student_id FROM students WHERE user_id = ?');
stmt->execute([$_SESSION['user_id']]);
$existing = $stmt->fetch();
if ($existing) {
    $stmt = $pdo->prepare('UPDATE students SET name=?, roll=?, department=?, cgpa=?, coding_score=? WHERE user_id=?');
    $stmt->execute([$data['name'],$data['roll'],$data['department'], $data['cgpa'],$data['coding_score'], $_SESSION['user_id']]);
    echo json_encode(['success'=>true,'updated'=>true]);
} else {
    $stmt = $pdo->prepare('INSERT INTO students (user_id,name,roll,department,cgpa,coding_score) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$_SESSION['user_id'],$data['name'],$data['roll'],$data['department'],$data['cgpa'],$data['coding_score']]);
    echo json_encode(['success'=>true,'created'=>true]);
}
