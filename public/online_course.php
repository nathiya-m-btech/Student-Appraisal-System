<?php
session_start();
require_once "../php/db_connect.php";
if(!isset($_SESSION['student'])){ header("Location: login.php"); exit; }

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

/* ----------------------------------------------------
   AUTO-DETECT FILE NAME (PREVENTS NOT FOUND)
---------------------------------------------------- */
$current_page = basename($_SERVER['PHP_SELF']);

/* ----------------------------------------------------
   SCORING LOGIC
---------------------------------------------------- */
function calcOnlineScore($year){
    return ( (intval($year) >= (date('Y')-1)) ? 4 : 2 );
}

/* ----------------------------------------------------
   DELETE RECORD
---------------------------------------------------- */
if (isset($_GET['delete'])){
    $id=intval($_GET['delete']);
    $pdo->prepare("DELETE FROM online_courses WHERE id=? AND student_id=?")
        ->execute([$id,$student_id]);

    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   INSERT / UPDATE + CERTIFICATE UPLOAD
---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST'){

    $id       = $_POST['id'] ?? '';
    $title    = trim($_POST['title']);
    $provider = trim($_POST['provider']);
    $year     = intval($_POST['year']);
    $score    = calcOnlineScore($year);

    $certificatePath = null;

    /* ---------- HANDLE FILE UPLOAD ---------- */
    if (!empty($_FILES['certificate']['name'])) {

        $folder = "../uploads/online_courses/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $fileName = time() . "_" . basename($_FILES['certificate']['name']);
        $targetPath = $folder . $fileName;

        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $targetPath)) {
            $certificatePath = "uploads/online_courses/" . $fileName;
        }
    }

    /* ------------------ INSERT ------------------ */
    if ($id==='') {

        $stmt = $pdo->prepare("
            INSERT INTO online_courses (student_id, title, provider, year, score, certificate)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$student_id,$title,$provider,$year,$score,$certificatePath]);

    } 
    /* ------------------ UPDATE ------------------ */
    else {

        if ($certificatePath) {
            // (WITH certificate update)
            $stmt = $pdo->prepare("
                UPDATE online_courses SET
                    title=?, provider=?, year=?, score=?, certificate=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$title,$provider,$year,$score,$certificatePath,$id,$student_id]);

        } else {
            // (WITHOUT certificate update)
            $stmt = $pdo->prepare("
                UPDATE online_courses SET
                    title=?, provider=?, year=?, score=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$title,$provider,$year,$score,$id,$student_id]);
        }
    }

    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   FETCH RECORDS
---------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM online_courses WHERE student_id=? ORDER BY id DESC");
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
        $eid=intval($_GET['edit']);
        $stmt2=$pdo->prepare("SELECT * FROM online_courses WHERE id=? AND student_id=?");
        $stmt2->execute([$eid,$student_id]);
        $editData=$stmt2->fetch();
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Online Courses</title>

<style>
body{font-family:Arial;background:#f2f7ff;padding:20px}
.container{width:95%;margin:auto}
.table-box{background:#fff;margin-top:20px;box-shadow:0 3px 8px rgba(0,0,0,.1)}
th,td{padding:10px;border-bottom:1px solid #ddd}
th{background:#e9f1ff;color:#003366}
.add-btn{background:#007bff;color:#fff;padding:10px;border-radius:6px;text-decoration:none}
.form-box{background:#fff;padding:20px;margin-top:20px;box-shadow:0 3px 8px rgba(0,0,0,.15)}
input{width:100%;padding:8px;margin-bottom:12px}
</style>

</head>
<body>

<div class="container">

  <div style="display:flex;justify-content:space-between">
    <h2>Online Courses</h2>
    <a href="<?= $current_page ?>?edit=0" class="add-btn">+ Add</a>
  </div>

  <table width="100%" class="table-box">
    <tr>
        <th>Title</th>
        <th>Provider</th>
        <th>Year</th>
        <th>Marks</th>
        <th>Certificate</th>
        <th>Action</th>
    </tr>

    <?php foreach($rows as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= htmlspecialchars($r['provider']) ?></td>
        <td><?= htmlspecialchars($r['year']) ?></td>
        <td><b><?= htmlspecialchars($r['score']) ?></b></td>

        <td>
            <?php if ($r['certificate']): ?>
                <a href="../<?= $r['certificate'] ?>" target="_blank">View</a>
            <?php else: ?>
                No File
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

      <label>Provider</label>
      <input name="provider" required value="<?= $editData['provider'] ?? '' ?>">

      <label>Year</label>
      <input type="number" name="year" required value="<?= $editData['year'] ?? '' ?>">

      <label>Upload Certificate (PDF/Image)</label>
      <input type="file" name="certificate" accept=".jpg,.jpeg,.png,.pdf">

      <?php if (!empty($editData['certificate'])): ?>
        <p>Current: <a href="../<?= $editData['certificate'] ?>" target="_blank">View</a></p>
      <?php endif; ?>

      <button type="submit"><?= ($editData) ? 'Update' : 'Add' ?></button>
    </form>

  </div>
  <?php endif; ?>

</div>

</body>
</html>
