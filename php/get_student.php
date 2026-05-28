<?php
require_once __DIR__ . '/../../php/db_connect.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){ exit('Unauthorized'); }

$pdo = getPDO();
$student_id = intval($_GET['id']);

// Student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Certificates
$stmt = $pdo->prepare("SELECT * FROM certificates WHERE student_id=?");
$stmt->execute([$student_id]);
$certs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ranking
$stmt = $pdo->prepare("SELECT * FROM rankings WHERE student_id=?");
$stmt->execute([$student_id]);
$rank = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<h5>Student: <?= htmlspecialchars($student['name']) ?></h5>
<p>CGPA: <?= htmlspecialchars($student['cgpa']) ?> | Coding Score: <?= htmlspecialchars($student['coding_score']) ?></p>
<h6>Certificates</h6>
<ul>
<?php foreach($certs as $c): ?>
  <li><?= htmlspecialchars($c['event_name']) ?> (<?= htmlspecialchars($c['level']) ?>) - Prize: <?= htmlspecialchars($c['prize']) ?> | Verified: <?= $c['verified'] ? 'Yes':'No' ?></li>
<?php endforeach; ?>
</ul>
<h6>Ranking</h6>
<p>Rank: <?= htmlspecialchars($rank['rank_pos']) ?> | Final Score: <?= htmlspecialchars($rank['final_score']) ?> | RF Label: <?= htmlspecialchars($rank['rf_predicted_label']) ?></p>
