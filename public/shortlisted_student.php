<?php
session_start();
require_once "../php/db_connect.php";

/* ===============================
   ADMIN CHECK
================================ */
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

/* ===============================
   VALIDATE STUDENT ID
================================ */
if (!isset($_GET['id'])) {
    die("Student ID missing");
}

$pdo = getPDO();
$student_id = $_GET['id'];

/* ===============================
   FETCH STUDENT (ONE ROW ONLY)
   ONE STUDENT = ONE TIME
================================ */
$sql = "
SELECT 
    s.student_id,
    s.name,
    u.email,
    s.cgpa,
    s.coding_score,
    s.batch,
    s.department,
    s.mentor_name,
    s.family_members,
    s.dob,
    IFNULL(r.topsis_score,0) AS topsis_score,
    IFNULL(r.rf_predicted_label,'-') AS rf_predicted_label,
    IFNULL(r.final_score,0) AS final_score
FROM students s
JOIN users u 
    ON s.user_id = u.id
JOIN rankings r 
    ON s.student_id = r.student_id
WHERE s.student_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) {
    die("Student not found or not shortlisted");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Shortlisted Student</title>

<style>
body {
    font-family: Arial;
    background:#f4f4f4;
    padding:30px;
}
.box {
    background:white;
    padding:30px;
    max-width:800px;
    margin:auto;
    border-radius:10px;
    box-shadow:0 0 15px rgba(0,0,0,0.15);
}
h2 {
    text-align:center;
    color:#2a3f54;
}
.row {
    margin-bottom:12px;
}
.label {
    font-weight:bold;
    width:220px;
    display:inline-block;
}
.score {
    font-weight:bold;
    color:#0d6efd;
}
hr {
    margin:20px 0;
}
.back {
    display:inline-block;
    margin-top:20px;
    text-decoration:none;
    color:#fff;
    background:#2a3f54;
    padding:10px 16px;
    border-radius:6px;
}
.back:hover {
    background:#1c2e3f;
}
</style>
</head>

<body>

<div class="box">
<h2>🏆 Shortlisted Student (Shown Once)</h2>

<div class="row"><span class="label">Student ID:</span><?= $s['student_id'] ?></div>
<div class="row"><span class="label">Name:</span><?= htmlspecialchars($s['name']) ?></div>
<div class="row"><span class="label">Email:</span><?= htmlspecialchars($s['email']) ?></div>
<div class="row"><span class="label">Department:</span><?= htmlspecialchars($s['department']) ?></div>
<div class="row"><span class="label">Batch:</span><?= htmlspecialchars($s['batch']) ?></div>
<div class="row"><span class="label">Mentor:</span><?= htmlspecialchars($s['mentor_name']) ?></div>
<div class="row"><span class="label">Family Members:</span><?= htmlspecialchars($s['family_members']) ?></div>
<div class="row"><span class="label">DOB:</span><?= htmlspecialchars($s['dob']) ?></div>

<hr>

<div class="row"><span class="label">CGPA:</span><?= number_format($s['cgpa'],2) ?></div>
<div class="row"><span class="label">Coding Score:</span><?= $s['coding_score'] ?></div>
<div class="row"><span class="label">TOPSIS Score:</span><span class="score"><?= number_format($s['topsis_score'],3) ?></span></div>
<div class="row"><span class="label">RF Prediction:</span><span class="score"><?= htmlspecialchars($s['rf_predicted_label']) ?></span></div>
<div class="row"><span class="label">Final Score:</span><span class="score"><?= number_format($s['final_score'],3) ?></span></div>

<a href="admin_dashboard.php" class="back">⬅ Back to Dashboard</a>
</div>

</body>
</html>
