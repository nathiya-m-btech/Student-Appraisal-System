<?php
session_start();
require_once "../php/db_connect.php";

if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

/* ---------------------------------------------------------
   INTERNSHIP SCORING
--------------------------------------------------------- */
function calcInternScore($months){
    if ($months >= 6) return 10;
    if ($months >= 3) return 6;
    if ($months >= 1) return 3;
    return 1;
}

/* ---------------------------------------------------------
   DELETE INTERNSHIP
--------------------------------------------------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // delete file also
    $stmt0 = $pdo->prepare("SELECT file_path FROM internship WHERE id=? AND student_id=?");
    $stmt0->execute([$id, $student_id]);
    $file = $stmt0->fetchColumn();
    
    if ($file && file_exists(__DIR__ . "/" . $file)) {
        unlink(__DIR__ . "/" . $file);
    }

    $pdo->prepare("DELETE FROM internship WHERE id=? AND student_id=?")
        ->execute([$id, $student_id]);

    header("Location: internship.php");
    exit;
}

/* ---------------------------------------------------------
   INSERT / UPDATE INTERNSHIP WITH FILE UPLOAD
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? '';
    $company = trim($_POST['company']);
    $role = trim($_POST['role']);
    $duration = intval($_POST['duration_months']);
    $score = calcInternScore($duration);

    $filePath = null;

    /* -------------- File Upload Handling -------------- */
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {

        $uploadDir = __DIR__ . "/uploads/internships/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $original = basename($_FILES['certificate']['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

        if (in_array($ext, $allowed)) {
            $safe = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($original, PATHINFO_FILENAME));
            $newName = time() . "_" . $safe . "." . $ext;
            $fullPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['certificate']['tmp_name'], $fullPath)) {
                $filePath = "uploads/internships/" . $newName;
            }
        }
    }

    /* --------------- INSERT ---------------- */
    if ($id === '') {

        $stmt = $pdo->prepare("
            INSERT INTO internship (student_id, company, role, duration_months, score, file_path)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$student_id, $company, $role, $duration, $score, $filePath]);

    } 
    /* --------------- UPDATE ---------------- */
    else {
        if ($filePath) {
            $stmt = $pdo->prepare("
                UPDATE internship SET company=?, role=?, duration_months=?, score=?, file_path=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$company, $role, $duration, $score, $filePath, $id, $student_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE internship SET company=?, role=?, duration_months=?, score=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$company, $role, $duration, $score, $id, $student_id]);
        }
    }

    header("Location: internship.php");
    exit;
}

/* ---------------------------------------------------------
   FETCH RECORDS
--------------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM internship WHERE student_id=? ORDER BY id DESC");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------
   EDIT MODE
--------------------------------------------------------- */
$editData = null;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $eid = intval($_GET['edit']);
    $stmt2 = $pdo->prepare("SELECT * FROM internship WHERE id=? AND student_id=?");
    $stmt2->execute([$eid, $student_id]);
    $editData = $stmt2->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Internships</title>

<style>
body {font-family:Arial;background:#f2f7ff;padding:20px;}
.container {width:95%;margin:auto;}
.add-btn {background:#007bff;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;font-weight:bold;}
table {width:100%;background:#fff;margin-top:20px;border-collapse:collapse;box-shadow:0 3px 8px rgba(0,0,0,.1);}
th, td {padding:10px;border-bottom:1px solid #ddd;}
th {background:#e9f1ff;color:#003366;}
.form-box {background:white;padding:20px;margin-top:20px;box-shadow:0 3px 8px rgba(0,0,0,0.15);}
</style>

</head>

<body>
<div class="container">

    <div style="display:flex;justify-content:space-between;">
        <h2>Internships</h2>
        <a href="internship.php?edit=0" class="add-btn">+ Add</a>
    </div>

<table>
<tr>
    <th>Company</th>
    <th>Role</th>
    <th>Duration (months)</th>
    <th>Marks</th>
    <th>Certificate</th>
    <th>Action</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['company']) ?></td>
    <td><?= htmlspecialchars($r['role']) ?></td>
    <td><?= htmlspecialchars($r['duration_months']) ?></td>
    <td><b><?= htmlspecialchars($r['score']) ?></b></td>

    <td>
        <?php if ($r['file_path']): ?>
            <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank">View</a>
        <?php else: ?>
            <span style="color:red;">No File</span>
        <?php endif; ?>
    </td>

    <td>
        <a href="internship.php?edit=<?= $r['id'] ?>">Edit</a> | 
        <a href="internship.php?delete=<?= $r['id'] ?>" onclick="return confirm('Delete this record?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php if (isset($_GET['edit'])): ?>
<div class="form-box">
<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<label>Company</label><br>
<input name="company" required value="<?= $editData['company'] ?? '' ?>"><br><br>

<label>Role</label><br>
<input name="role" required value="<?= $editData['role'] ?? '' ?>"><br><br>

<label>Duration (months)</label><br>
<input type="number" name="duration_months" required value="<?= $editData['duration_months'] ?? '' ?>"><br><br>

<label>Upload Certificate (PDF/JPG/PNG)</label><br>
<input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png"><br><br>

<button type="submit" style="background:#003366;color:white;padding:10px 16px;border:none;border-radius:6px;">
    <?= ($editData) ? "Update" : "Add" ?>
</button>

</form>
</div>
<?php endif; ?>

</div>
</body>
</html>
