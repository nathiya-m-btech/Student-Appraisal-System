<?php
session_start();
require_once "../php/db_connect.php";

if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

function calculateCGPAMark($cgpa) {
    if ($cgpa >= 9) return 10;
    if ($cgpa >= 8) return 9;
    if ($cgpa >= 7) return 7;
    if ($cgpa >= 6) return 5;
    if ($cgpa >= 5) return 3;
    return 1;
}

/* DELETE */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM academic_cgpa WHERE id=? AND student_id=?")
        ->execute([$id, $student_id]);
    header("Location: cgpa.php");
    exit;
}

/* INSERT / UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? '';
    $course = trim($_POST['course']);
    $institution = trim($_POST['institution']);
    $year = intval($_POST['passing_year']);
    $cgpa = floatval($_POST['cgpa']);
    $score = calculateCGPAMark($cgpa);

    $certificatePath = null;

    if (!empty($_FILES['certificate']['name'])) {
        $uploadDir = "../uploads/cgpa/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['certificate']['name']);
        move_uploaded_file($_FILES['certificate']['tmp_name'], $uploadDir . $fileName);

        $certificatePath = "uploads/cgpa/" . $fileName;
    }

    if ($id == '') {
        $stmt = $pdo->prepare("
            INSERT INTO academic_cgpa
            (student_id, course, institution, passing_year, cgpa, cgpa_score, certificate)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $student_id, $course, $institution,
            $year, $cgpa, $score, $certificatePath
        ]);
    } else {
        if ($certificatePath) {
            $stmt = $pdo->prepare("
                UPDATE academic_cgpa
                SET course=?, institution=?, passing_year=?, cgpa=?, cgpa_score=?, certificate=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([
                $course, $institution, $year,
                $cgpa, $score, $certificatePath,
                $id, $student_id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE academic_cgpa
                SET course=?, institution=?, passing_year=?, cgpa=?, cgpa_score=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([
                $course, $institution, $year,
                $cgpa, $score,
                $id, $student_id
            ]);
        }
    }

    header("Location: cgpa.php");
    exit;
}

/* FETCH */
$stmt = $pdo->prepare("SELECT * FROM academic_cgpa WHERE student_id=? ORDER BY passing_year DESC");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* EDIT */
$editData = null;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $eid = intval($_GET['edit']);
    $stmt2 = $pdo->prepare("SELECT * FROM academic_cgpa WHERE id=? AND student_id=?");
    $stmt2->execute([$eid, $student_id]);
    $editData = $stmt2->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CGPA</title>

<style>
body{font-family:Arial;background:#f2f7ff;padding:20px}
.container{width:95%;margin:auto}
.top-bar{display:flex;justify-content:space-between;align-items:center}
.add-btn{background:#007bff;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;font-weight:bold}

table{width:100%;background:#fff;margin-top:20px;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:center}
th{background:#e9f1ff;color:#003366}

.action-btn{
    padding:6px 12px;
    border-radius:6px;
    color:#fff;
    text-decoration:none;
    font-weight:bold;
    font-size:14px;
    margin:0 3px;
}

.edit-btn{background:#28a745}
.delete-btn{background:#dc3545}

.cert-img{max-width:100px}

.form-box{
    background:#fff;
    padding:20px;
    margin-top:20px;
    box-shadow:0 3px 8px rgba(0,0,0,.15)
}

.form-box input{
    width:100%;
    padding:8px;
    margin-bottom:12px;
}
</style>
</head>

<body>
<div class="container">

<div class="top-bar">
<h2>CGPA</h2>
<a class="add-btn" href="cgpa.php?edit=0">+ Add Details</a>
</div>

<table>
<tr>
<th>Course</th>
<th>Institution</th>
<th>Year</th>
<th>CGPA</th>
<th>Marks</th>
<th>Certificate</th>
<th>Action</th>
</tr>

<?php foreach($rows as $r): ?>
<tr>
<td><?= htmlspecialchars($r['course']) ?></td>
<td><?= htmlspecialchars($r['institution']) ?></td>
<td><?= htmlspecialchars($r['passing_year']) ?></td>
<td><?= htmlspecialchars($r['cgpa']) ?></td>
<td><b><?= htmlspecialchars($r['cgpa_score']) ?></b></td>
<td>
<?php if (!empty($r['certificate'])): ?>
    <a href="../<?= htmlspecialchars($r['certificate']) ?>" target="_blank">View</a>
<?php else: ?>
    No file
<?php endif; ?>
</td>
<td>
<a href="cgpa.php?edit=<?= $r['id'] ?>" class="action-btn edit-btn">Edit</a>
<a href="cgpa.php?delete=<?= $r['id'] ?>" 
   class="action-btn delete-btn"
   onclick="return confirm('Delete this record?')">Delete</a>
</td>
</tr>
<?php endforeach; ?>
</table>

<?php if (isset($_GET['edit'])): ?>
<div class="form-box">
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
<input name="course" placeholder="Course" required value="<?= $editData['course'] ?? '' ?>">
<input name="institution" placeholder="Institution" required value="<?= $editData['institution'] ?? '' ?>">
<input name="passing_year" placeholder="Year" required value="<?= $editData['passing_year'] ?? '' ?>">
<input name="cgpa" placeholder="CGPA" required value="<?= $editData['cgpa'] ?? '' ?>">
<input type="file" name="certificate">
<button type="submit" class="add-btn">Save</button>
</form>
</div>
<?php endif; ?>

</div>
</body>
</html>
