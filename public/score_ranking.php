<?php
session_start();
require_once __DIR__ . '/../php/db_connect.php';

/* ===============================
   CHECK LOGIN
================================ */
if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

/* ===============================
   TOTAL MARK CALCULATION
================================ */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(final_score),0) AS total
    FROM rankings
    WHERE student_id = ?
");
$stmt->execute([$student_id]);
$total_mark = $stmt->fetchColumn();

/* ===============================
   CATEGORY-WISE BREAKUP (OPTIONAL)
================================ */
$stmt2 = $pdo->prepare("
    SELECT 'TOPSIS' AS category, topsis_score AS marks
    FROM rankings
    WHERE student_id = ?
");
$stmt2->execute([$student_id]);
$breakup = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Total Mark Calculation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg,#003973,#00c6ff);
    font-family: 'Poppins', sans-serif;
    color: white;
}
.card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
</style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 text-center">
                <h3 class="mb-3">📊 Total Mark Calculation</h3>
                <p><strong>Student ID:</strong> <?= htmlspecialchars($student_id) ?></p>
                <h1 class="display-4 fw-bold text-warning"><?= number_format($total_mark, 2) ?></h1>
                <p class="text-light">Total Score</p>
                <hr class="text-white">
                <h5 class="mb-3">📂 Mark Breakdown</h5>
                <?php if ($breakup): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($breakup as $row): ?>
                            <li class="list-group-item bg-transparent text-white d-flex justify-content-between">
                                <span><?= ucfirst($row['category']) ?></span>
                                <strong><?= number_format($row['marks'], 2) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-warning mt-3">No marks calculated yet.</p>
                <?php endif; ?>
                <a href="student_dashboard.php" class="btn btn-light mt-4">⬅ Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
