<?php
require_once __DIR__ . '/../db_connect.php';
$pdo = getPDO();
$stmt = $pdo->query("SELECT r.rank_pos, r.final_score, s.name, s.roll FROM rankings r JOIN students s ON r.student_id = s.student_id ORDER BY r.rank_pos ASC LIMIT 50");
$rows = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($rows);
