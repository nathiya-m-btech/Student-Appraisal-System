<?php
session_start();
require_once "../php/db_connect.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();

/* ===============================
   ALL STUDENTS (LATEST SCORE)
================================ */
$sql = "
SELECT 
    s.student_id,
    s.name,
    u.email,
    s.cgpa,
    s.coding_score,

    IFNULL(r.topsis_score, 0) AS topsis_score,
    IFNULL(r.rf_predicted_label, 'not_evaluated') AS rf_predicted_label,
    IFNULL(r.final_score, 0) AS final_score

FROM students s
JOIN users u ON s.user_id = u.id

LEFT JOIN rankings r
ON r.id = (
    SELECT r2.id
    FROM rankings r2
    WHERE r2.student_id = s.student_id
    ORDER BY r2.last_updated DESC
    LIMIT 1
)

ORDER BY s.student_id
";
$students = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   TOP 3 SHORTLISTED (LATEST + BEST)
================================ */
$short_sql = "
SELECT 
    s.student_id,
    s.name,
    u.email,
    s.cgpa,
    s.coding_score,
    r.final_score

FROM students s
JOIN users u ON s.user_id = u.id

JOIN rankings r
ON r.id = (
    SELECT r2.id
    FROM rankings r2
    WHERE r2.student_id = s.student_id
    ORDER BY r2.last_updated DESC
    LIMIT 1
)

WHERE r.final_score IS NOT NULL
ORDER BY r.final_score DESC
LIMIT 3
";
$shortlisted = $pdo->query($short_sql)->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard - Student Appraisal</title>

<style>
body {
    font-family: Arial;
    background:#f4f4f4;
    margin:0;
}

/* HEADER */
.header {
    background:#2a3f54;
    color:white;
    padding:20px;
    font-size:26px;
    text-align:center;
    position:relative;
}

/* LOGOUT BUTTON */
.logout-btn {
    position:absolute;
    right:20px;
    top:20px;
    background:#dc3545;
    color:white;
    padding:10px 16px;
    border-radius:6px;
    text-decoration:none;
    font-size:14px;
}
.logout-btn:hover {
    background:#b02a37;
}

.container {
    width:95%;
    margin:20px auto;
}

table {
    width:100%;
    border-collapse:collapse;
    background:white;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}

th, td {
    padding:12px;
    border-bottom:1px solid #ddd;
}

th {
    background:#2a3f54;
    color:white;
}

.badge {
    padding:6px 10px;
    border-radius:6px;
    color:white;
    font-size:12px;
}
.badge-high { background:#28a745; }
.badge-medium { background:#ffc107; color:black; }
.badge-low { background:#dc3545; }

.view-btn {
    padding:6px 12px;
    background:#007bff;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
.view-btn:hover {
    background:#0056b3;
}
</style>
</head>

<body>

<!-- HEADER WITH LOGOUT -->
<div class="header">
    Admin Dashboard - Student Appraisal System
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">

<!-- TOP 3 -->
<h2>🏆 Top 3 Shortlisted Students</h2>
<table>
<tr>
    <th>Rank</th>
    <th>Name</th>
    <th>Email</th>
    <th>CGPA</th>
    <th>Coding</th>
    <th>Final Score</th>
    <th>Action</th>
</tr>

<?php $rank=1; foreach($shortlisted as $s):
$badge = $s['final_score'] >= 0.7 ? "badge-high" :
         ($s['final_score'] >= 0.4 ? "badge-medium" : "badge-low");
?>
<tr>
    <td><b><?= $rank++ ?></b></td>
    <td><?= htmlspecialchars($s['name']) ?></td>
    <td><?= htmlspecialchars($s['email']) ?></td>
    <td><?= $s['cgpa'] ?></td>
    <td><?= $s['coding_score'] ?></td>
    <td><span class="badge <?= $badge ?>"><?= number_format($s['final_score'],3) ?></span></td>
    <td>
        <a href="shortlisted_student.php?id=<?= $s['student_id'] ?>">
            <button class="view-btn">View</button>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<!-- ALL STUDENTS -->
<h2 style="margin-top:40px;">📌 All Students</h2>
<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>CGPA</th>
    <th>Coding</th>
    <th>TOPSIS</th>
    <th>RF</th>
    <th>Final</th>
    <th>Action</th>
</tr>

<?php foreach($students as $s):
$fs = $s['final_score'] ?? 0;
$badge = $fs >= 0.7 ? "badge-high" :
         ($fs >= 0.4 ? "badge-medium" : "badge-low");
?>
<tr>
    <td><?= $s['student_id'] ?></td>
    <td><?= htmlspecialchars($s['name']) ?></td>
    <td><?= htmlspecialchars($s['email']) ?></td>
    <td><?= $s['cgpa'] ?></td>
    <td><?= $s['coding_score'] ?></td>
    <td><?= number_format($s['topsis_score'] ?? 0,3) ?></td>
    <td><?= $s['rf_predicted_label'] ?></td>
    <td><span class="badge <?= $badge ?>"><?= number_format($fs,3) ?></span></td>
    <td>
        <a href="shortlisted_student.php?id=<?= $s['student_id'] ?>">
            <button class="view-btn">View</button>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</table>

</div>
</body>
</html>
