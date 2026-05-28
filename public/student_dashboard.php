<?php 
session_start();
require_once __DIR__ . '/../php/db_connect.php';

// check login
if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$student = $_SESSION['student'];

/*
-----------------------------------------------------
 FIX: Extract student name correctly from SESSION
-----------------------------------------------------
We check ALL possible field names to avoid "Student" default.
-----------------------------------------------------
*/
$student_name =
    $student['student_name'] ??
    $student['name'] ??
    $student['full_name'] ??
    $student['fname'] ??
    $student['username'] ??
    $student['email'] ??
    'Student';

// detect student id safely
$student_id = $student['student_id'] ?? $student['id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    margin: 0;
    background: linear-gradient(135deg, #00378f, #0099ff);
    font-family: 'Poppins', sans-serif;
    color: white;
}
.sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    padding-top: 25px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
}
.sidebar a {
    padding: 14px 25px;
    display: block;
    text-decoration: none;
    color: #fff;
}
.sidebar a:hover {
    background: rgba(255,255,255,0.3);
    border-radius: 10px;
}
.main {
    margin-left: 260px;
    padding: 40px;
}
.info-card {
    background: rgba(255,255,255,0.15);
    padding: 25px;
    border-radius: 16px;
}
</style>
</head>

<body>

<div class="sidebar">
    <h3 class="text-center mb-3"><i class="bi bi-person-circle"></i> Student Panel</h3>
    <p class="px-3">Welcome, <strong><?= htmlspecialchars($student_name) ?></strong></p>

    <a href="student_dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
    <a href="profile.php"><i class="bi bi-person"></i> Profile</a>
    <a href="basic_details.php"><i class="bi bi-card-list"></i> Basic Details</a>
    <a href="acadamic.php"><i class="bi bi-file-earmark-arrow-up"></i> Academic Details</a>
    <a href="non_acadamic.php"><i class="bi bi-table"></i> Non Academic</a>
    <a href="career_path.php"><i class="bi bi-gear"></i> Career Path</a>
    <a href="logout.php" style="color:#ffdddd;"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="main">
    <h2>👋 Welcome, <?= htmlspecialchars($student_name) ?></h2>

    <div class="row mt-4 g-4">

        <div class="col-md-4">
            <div class="info-card">
                <h5>Profile Information</h5>
                <p><strong>Name:</strong> <?= htmlspecialchars($student_name) ?></p>
                <p><strong>Student ID:</strong> <?= htmlspecialchars($student_id) ?></p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="info-card">
                <h5>Your Score</h5>
                <p>Your marks will appear after evaluation.</p>
                <a href="score_ranking.php" class="btn btn-warning btn-sm">Check Score</a>
            </div>
        </div>


    </div>

</div>

</body>
</html>
