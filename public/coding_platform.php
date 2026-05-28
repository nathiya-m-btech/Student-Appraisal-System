<?php
session_start();
require_once "../php/db_connect.php";

if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

/* ----------------------------------------------------
   GET CURRENT FILE NAME (PREVENT NOT FOUND ERROR)
---------------------------------------------------- */
$current_page = basename($_SERVER['PHP_SELF']);

/* ----------------------------------------------------
   CODING PLATFORM SCORING FUNCTION
---------------------------------------------------- */
function calculateCodingScore($problems, $rating) {
    if ($problems >= 200 && $rating >= 1500) return 10;
    if ($problems >= 150 && $rating >= 1300) return 9;
    if ($problems >= 100 && $rating >= 1000) return 7;
    if ($problems >= 50 && $rating >= 800) return 5;
    return 3;
}

/* ----------------------------------------------------
   DELETE RECORD
---------------------------------------------------- */
if (isset($_GET['delete'])) {
    $deleteID = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM coding_platform WHERE id=? AND student_id=?")
        ->execute([$deleteID, $student_id]);
    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   INSERT / UPDATE RECORD
---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? '';

    $platform = trim($_POST['platform']);
    $username = trim($_POST['username']);
    $problems = intval($_POST['problems_solved']);
    $rating = intval($_POST['rating']);

    // Auto-score calculation
    $coding_score = calculateCodingScore($problems, $rating);

    if ($id == '') {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO coding_platform (
                student_id, platform, username, problems_solved, rating, coding_score
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $platform, $username, $problems, $rating, $coding_score]);

    } else {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE coding_platform
            SET platform=?, username=?, problems_solved=?, rating=?, coding_score=?
            WHERE id=? AND student_id=?
        ");
        $stmt->execute([$platform, $username, $problems, $rating, $coding_score, $id, $student_id]);
    }

    /* Update student's main coding score */
    $pdo->prepare("UPDATE students SET coding_score=? WHERE student_id=?")
        ->execute([$coding_score, $student_id]);

    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   FETCH ALL RECORDS
---------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM coding_platform WHERE student_id=? ORDER BY id DESC");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ----------------------------------------------------
   EDIT MODE
---------------------------------------------------- */
$editData = null;
$showForm = false;

if (isset($_GET['edit'])) {
    $showForm = true;

    if (intval($_GET['edit']) > 0) {
        $eid = intval($_GET['edit']);
        $stmt2 = $pdo->prepare("SELECT * FROM coding_platform WHERE id=? AND student_id=?");
        $stmt2->execute([$eid, $student_id]);
        $editData = $stmt2->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Coding Platform</title>

<style>
body {
    font-family: Arial;
    background: #f2f7ff;
    padding: 20px;
}
.container { width: 95%; margin: auto; }
.top-bar { display: flex; justify-content: space-between; align-items: center; }
.top-bar h2 { margin: 0; color: #003366; }
.add-btn {
    background: #007bff;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}
table {
    width: 100%;
    border-collapse: collapse;
    background:white;
    margin-top:20px;
    box-shadow:0 3px 8px rgba(0,0,0,0.1);
}
table th, table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}
table th {
    background: #e9f1ff;
    color: #003366;
}
.action-btn { padding: 6px 10px; border-radius: 4px; text-decoration: none; color: white; }
.edit-btn { background:#28a745; }
.delete-btn { background:#dc3545; }
.form-box {
    background:white;
    padding:20px;
    margin-top:20px;
    box-shadow:0 3px 8px rgba(0,0,0,0.15);
}
.form-box input {
    width:100%;
    padding:8px;
    margin-bottom:12px;
}
.form-box button {
    background:#003366;
    padding:10px 15px;
    border:none;
    color:white;
    border-radius:6px;
}
</style>
</head>

<body>
<div class="container">

<div class="top-bar">
    <h2>Coding Platform Performance</h2>

    <!-- NEVER FAILS - ALWAYS LINKS TO CURRENT PAGE -->
    <a href="<?= $current_page ?>?edit=0" class="add-btn">+ Add Record</a>
</div>

<table>
<tr>
    <th>Platform</th>
    <th>Username</th>
    <th>Problems Solved</th>
    <th>Rating</th>
    <th>Allocated Marks</th>
    <th>Action</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['platform']) ?></td>
    <td><?= htmlspecialchars($r['username']) ?></td>
    <td><?= htmlspecialchars($r['problems_solved']) ?></td>
    <td><?= htmlspecialchars($r['rating']) ?></td>
    <td><b><?= htmlspecialchars($r['coding_score']) ?></b></td>

    <td>
        <a class="action-btn edit-btn" href="<?= $current_page ?>?edit=<?= $r['id'] ?>">Edit</a>
        <a class="action-btn delete-btn" href="<?= $current_page ?>?delete=<?= $r['id'] ?>" onclick="return confirm('Delete this record?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>

</table>

<?php if ($showForm): ?>
<div class="form-box">

<form method="POST">

<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<label>Platform:</label>
<input name="platform" required value="<?= $editData['platform'] ?? '' ?>">

<label>Username:</label>
<input name="username" required value="<?= $editData['username'] ?? '' ?>">

<label>Problems Solved:</label>
<input type="number" name="problems_solved" required value="<?= $editData['problems_solved'] ?? '' ?>">

<label>Rating:</label>
<input type="number" name="rating" required value="<?= $editData['rating'] ?? '' ?>">

<button type="submit">
    <?= ($editData) ? "Update Record" : "Add Record" ?>
</button>

</form>
</div>
<?php endif; ?>

</div>
</body>
</html>
