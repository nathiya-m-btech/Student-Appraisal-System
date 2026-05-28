<?php
session_start();
require_once "../php/db_connect.php";

if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

/* ----------------- SCORE LOGIC ----------------- */
function calcPublicationScore($publisher)
{
    $p = strtolower($publisher);

    if (strpos($p, 'journal') !== false || strpos($p, 'ieee') !== false)
        return 10;

    if (strpos($p, 'conference') !== false || strpos($p, 'scopus') !== false)
        return 8;

    return 4;
}

/* ----------------- DELETE ----------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $pdo->prepare("SELECT file_path FROM publications WHERE id=? AND student_id=?");
    $stmt->execute([$id, $student_id]);
    $file = $stmt->fetchColumn();

    if ($file && file_exists(__DIR__ . "/" . $file)) {
        unlink(__DIR__ . "/" . $file);
    }

    $pdo->prepare("DELETE FROM publications WHERE id=? AND student_id=?")
        ->execute([$id, $student_id]);

    header("Location: publication.php");
    exit;
}

/* ----------------- INSERT / UPDATE ----------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title']);
    $publisher = trim($_POST['publisher']);
    $year = intval($_POST['year']);
    $score = calcPublicationScore($publisher);
    $filePath = null;

    /* -------- FILE UPLOAD -------- */
    if (!empty($_FILES['certificate']['name']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {

        $uploadDir = __DIR__ . "/uploads/publications/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

        if (in_array($ext, $allowed)) {
            $newName = time() . "_" . rand(1000, 9999) . "." . $ext;
            $fullPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['certificate']['tmp_name'], $fullPath)) {
                $filePath = "uploads/publications/" . $newName;
            }
        }
    }

    /* -------- INSERT -------- */
    if ($id === '') {
        $stmt = $pdo->prepare("
            INSERT INTO publications (student_id, title, publisher, year, score, file_path)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $title, $publisher, $year, $score, $filePath]);
    }
    /* -------- UPDATE -------- */
    else {
        if ($filePath) {
            $stmt = $pdo->prepare("
                UPDATE publications
                SET title=?, publisher=?, year=?, score=?, file_path=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$title, $publisher, $year, $score, $filePath, $id, $student_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE publications
                SET title=?, publisher=?, year=?, score=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$title, $publisher, $year, $score, $id, $student_id]);
        }
    }

    header("Location: publication.php");
    exit;
}

/* ----------------- FETCH ----------------- */
$stmt = $pdo->prepare("SELECT * FROM publications WHERE student_id=? ORDER BY id DESC");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll();

$editData = null;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $eid = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM publications WHERE id=? AND student_id=?");
    $stmt->execute([$eid, $student_id]);
    $editData = $stmt->fetch();
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Publications</title>
<style>
body { font-family: Arial; background: #f2f7ff; padding: 20px; }
.container { width: 95%; margin: auto; }
.add-btn { background: #007bff; color: white; padding: 10px 16px; border-radius: 6px; text-decoration: none; }
table { width: 100%; background: #fff; margin-top: 20px; border-collapse: collapse; }
th, td { padding: 10px; border-bottom: 1px solid #ddd; }
th { background: #e9f1ff; }
.form-box { background: white; padding: 20px; margin-top: 20px; }
</style>
</head>

<body>
<div class="container">

<div style="display:flex;justify-content:space-between;">
    <h2>Publications</h2>
    <a href="publication.php?edit=0" class="add-btn">+ Add</a>
</div>

<table>
<tr>
    <th>Title</th>
    <th>Publisher</th>
    <th>Year</th>
    <th>Marks</th>
    <th>File</th>
    <th>Action</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['title']) ?></td>
    <td><?= htmlspecialchars($r['publisher']) ?></td>
    <td><?= $r['year'] ?></td>
    <td><b><?= $r['score'] ?></b></td>
    <td>
        <?php if (!empty($r['file_path'])): ?>
            <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank">View</a>
        <?php else: ?>
            <span style="color:red;">No File</span>
        <?php endif; ?>
    </td>
    <td>
        <a href="publication.php?edit=<?= $r['id'] ?>">Edit</a> |
        <a href="publication.php?delete=<?= $r['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php if (isset($_GET['edit'])): ?>
<div class="form-box">
<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<label>Title</label><br>
<input name="title" required value="<?= $editData['title'] ?? '' ?>"><br><br>

<label>Publisher</label><br>
<input name="publisher" required value="<?= $editData['publisher'] ?? '' ?>"><br><br>

<label>Year</label><br>
<input type="number" name="year" required value="<?= $editData['year'] ?? '' ?>"><br><br>

<label>Certificate</label><br>
<input type="file" name="certificate"><br><br>

<button type="submit">Save</button>
</form>
</div>
<?php endif; ?>

</div>
</body>
</html>