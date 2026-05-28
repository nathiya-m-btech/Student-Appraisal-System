<?php
session_start();
require_once "../php/db_connect.php";
if (!isset($_SESSION['student'])) { header("Location: login.php"); exit; }

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

/* ----------------------------------------------------
   AUTO-DETECT FILENAME (Prevents NOT FOUND error)
---------------------------------------------------- */
$current_page = basename($_SERVER['PHP_SELF']);

/* ----------------------------------------------------
   WORKSHOP SCORING FUNCTION
---------------------------------------------------- */
function calcWorkshopScore($hours){
    if ($hours >= 40) return 6;
    if ($hours >= 20) return 4;
    if ($hours >= 8) return 2;
    return 1;
}

/* ----------------------------------------------------
   DELETE WORKSHOP
---------------------------------------------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM workshop WHERE id=? AND student_id=?")
        ->execute([$id, $student_id]);

    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   INSERT / UPDATE WORKSHOP + CERTIFICATE UPLOAD
---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id     = $_POST['id'] ?? '';
    $title  = trim($_POST['title']);
    $org    = trim($_POST['organizer']);
    $hours  = intval($_POST['hours']);
    $score  = calcWorkshopScore($hours);

    $certificatePath = null;

    /* ----------- HANDLE FILE UPLOAD ----------- */
    if (!empty($_FILES['certificate']['name'])) {

        $folder = "../uploads/workshop/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $fileName = time() . "_" . basename($_FILES['certificate']['name']);
        $targetPath = $folder . $fileName;

        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $targetPath)) {
            $certificatePath = "uploads/workshop/" . $fileName;
        }
    }

    if ($id === '') {
        /* INSERT */
        $stmt = $pdo->prepare("
            INSERT INTO workshop (student_id, title, organizer, hours, score, certificate)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$student_id, $title, $org, $hours, $score, $certificatePath]);

    } else {

        if ($certificatePath) {
            /* UPDATE WITH CERTIFICATE */
            $stmt = $pdo->prepare("
                UPDATE workshop SET
                    title=?, organizer=?, hours=?, score=?, certificate=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$title, $org, $hours, $score, $certificatePath, $id, $student_id]);

        } else {
            /* UPDATE WITHOUT CHANGING CERTIFICATE */
            $stmt = $pdo->prepare("
                UPDATE workshop SET
                    title=?, organizer=?, hours=?, score=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$title, $org, $hours, $score, $id, $student_id]);
        }
    }

    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   FETCH WORKSHOPS
---------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM workshop WHERE student_id=? ORDER BY id DESC");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll();

/* ----------------------------------------------------
   EDIT MODE
---------------------------------------------------- */
$editData = null;
$showForm = false;

if (isset($_GET['edit'])) {
    $showForm = true;

    if (intval($_GET['edit']) > 0) {
        $eid = intval($_GET['edit']);
        $stmt2 = $pdo->prepare("SELECT * FROM workshop WHERE id=? AND student_id=?");
        $stmt2->execute([$eid, $student_id]);
        $editData = $stmt2->fetch();
    }
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Workshops / Seminars</title>

<style>
body{font-family:Arial;background:#f2f7ff;padding:20px}
.container{width:95%;margin:auto}
.table-box{background:#fff;margin-top:20px;box-shadow:0 3px 8px rgba(0,0,0,.1)}
th,td{padding:10px;border-bottom:1px solid #ddd}
th{background:#e9f1ff;color:#003366}
.add-btn{background:#007bff;color:#fff;padding:10px;border-radius:6px;text-decoration:none}
.form-box{background:#fff;padding:20px;margin-top:20px;box-shadow:0 3px 8px rgba(0,0,0,.15)}
input,textarea{width:100%;padding:8px;margin-bottom:12px}
</style>

</head>
<body>

<div class="container">

  <div style="display:flex;justify-content:space-between">
      <h2>Workshops / Seminars</h2>
      <a href="<?= $current_page ?>?edit=0" class="add-btn">Add Details</a>
  </div>

  <table width="100%" class="table-box">
    <tr>
        <th>Title</th>
        <th>Organizer</th>
        <th>Hours</th>
        <th>Marks</th>
        <th>Certificate</th>
        <th>Action</th>
    </tr>

    <?php foreach($rows as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= htmlspecialchars($r['organizer']) ?></td>
        <td><?= htmlspecialchars($r['hours']) ?></td>
        <td><b><?= htmlspecialchars($r['score']) ?></b></td>

        <td>
            <?php if ($r['certificate']): ?>
                <a href="../<?= $r['certificate'] ?>" target="_blank">View</a>
            <?php else: ?>
                No file
            <?php endif; ?>
        </td>

        <td>
            <a href="<?= $current_page ?>?edit=<?= $r['id'] ?>">Edit</a> |
            <a href="<?= $current_page ?>?delete=<?= $r['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <?php if ($showForm): ?>
  <div class="form-box">

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

      <label>Title</label>
      <input name="title" required value="<?= $editData['title'] ?? '' ?>">

      <label>Organizer</label>
      <input name="organizer" required value="<?= $editData['organizer'] ?? '' ?>">

      <label>Hours</label>
      <input type="number" name="hours" required value="<?= $editData['hours'] ?? '' ?>">

      <label>Upload Certificate (image/pdf)</label>
      <input type="file" name="certificate" accept=".jpg,.jpeg,.png,.pdf">

      <?php if (!empty($editData['certificate'])): ?>
        <p>Current: <a href="../<?= $editData['certificate'] ?>" target="_blank">View</a></p>
      <?php endif; ?>

      <button type="submit"><?= ($editData) ? 'Update' : 'Add Details' ?></button>
    </form>

  </div>
  <?php endif; ?>

</div>
</body>
</html>
