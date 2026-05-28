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
   AUTO-DETECT PAGE NAME (FIXES "NOT FOUND" ERROR)
---------------------------------------------------- */
$current_page = basename($_SERVER['PHP_SELF']);

/* ----------------------------------------------------
   SCORE CALCULATION
---------------------------------------------------- */
function calcHackathonScore($level, $prize) {
    $l = strtolower($level);
    $p = strtolower($prize);

    if (strpos($l, 'international') !== false) $base = 10;
    elseif (strpos($l, 'national') !== false) $base = 8;
    elseif (strpos($l, 'state') !== false) $base = 6;
    else $base = 4;

    if (preg_match('/1|first|winner|gold/i', $p)) $base += 2;
    elseif (preg_match('/2|second|silver/i', $p)) $base += 1;

    return min($base, 10);
}

/* ----------------------------------------------------
   DELETE
---------------------------------------------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM hackathon WHERE id=? AND student_id=?")
        ->execute([$id, $student_id]);
    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   INSERT / UPDATE WITH CERTIFICATE UPLOAD
---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id    = $_POST['id'] ?? '';
    $event = trim($_POST['event']);
    $level = trim($_POST['level']);
    $prize = trim($_POST['prize']);
    $score = calcHackathonScore($level, $prize);

    $certificatePath = null;

    /* HANDLE FILE UPLOAD */
    if (!empty($_FILES['certificate']['name'])) {

        $folder = "../uploads/hackathon/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $fileName = time() . "_" . basename($_FILES['certificate']['name']);
        $targetPath = $folder . $fileName;

        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $targetPath)) {
            $certificatePath = "uploads/hackathon/" . $fileName;
        }
    }

    if ($id == '') {
        /* INSERT */
        $stmt = $pdo->prepare("
            INSERT INTO hackathon (student_id, event, level, prize, score, certificate)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$student_id, $event, $level, $prize, $score, $certificatePath]);

    } else {
        /* UPDATE QUERY */
        if ($certificatePath) {
            $stmt = $pdo->prepare("
                UPDATE hackathon
                SET event=?, level=?, prize=?, score=?, certificate=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$event, $level, $prize, $score, $certificatePath, $id, $student_id]);

        } else {
            $stmt = $pdo->prepare("
                UPDATE hackathon
                SET event=?, level=?, prize=?, score=?
                WHERE id=? AND student_id=?
            ");
            $stmt->execute([$event, $level, $prize, $score, $id, $student_id]);
        }
    }

    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   FETCH RECORDS
---------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM hackathon WHERE student_id=? ORDER BY id DESC");
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
        $stmt2 = $pdo->prepare("SELECT * FROM hackathon WHERE id=? AND student_id=?");
        $stmt2->execute([$eid, $student_id]);
        $editData = $stmt2->fetch();
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Hackathon</title>

<style>
body{font-family:Arial;background:#f2f7ff;padding:20px}
.container{width:95%;margin:auto}
.top-bar{display:flex;justify-content:space-between}
.add-btn{background:#007bff;color:#fff;padding:10px;border-radius:6px;text-decoration:none}
table{width:100%;background:#fff;margin-top:20px;box-shadow:0 3px 8px rgba(0,0,0,.1)}
th,td{padding:10px;border-bottom:1px solid #ddd}
th{background:#e9f1ff;color:#003366}
.action-btn{padding:6px 10px;color:#fff;border-radius:4px;text-decoration:none}
.edit-btn{background:#28a745}
.delete-btn{background:#dc3545}
.form-box{background:#fff;padding:20px;margin-top:20px}
</style>
</head>

<body>
<div class="container">

  <div class="top-bar">
      <h2>Hackathon</h2>
      <a class="add-btn" href="<?= $current_page ?>?edit=0">+ Add</a>
  </div>

  <table>
    <tr>
      <th>Event</th>
      <th>Level</th>
      <th>Prize</th>
      <th>Marks</th>
      <th>Certificate</th>
      <th>Action</th>
    </tr>

    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['event']) ?></td>
        <td><?= htmlspecialchars($r['level']) ?></td>
        <td><?= htmlspecialchars($r['prize']) ?></td>
        <td><b><?= htmlspecialchars($r['score']) ?></b></td>

        <td>
            <?php if ($r['certificate']): ?>
                <a href="../<?= $r['certificate'] ?>" target="_blank">View File</a>
            <?php else: ?>
                No file
            <?php endif; ?>
        </td>

        <td>
            <a class="action-btn edit-btn" href="<?= $current_page ?>?edit=<?= $r['id'] ?>">Edit</a>
            <a class="action-btn delete-btn" href="<?= $current_page ?>?delete=<?= $r['id'] ?>" 
               onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php if ($showForm): ?>
  <div class="form-box">
    <form method="POST" enctype="multipart/form-data">

      <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

      <label>Event</label>
      <input name="event" required value="<?= $editData['event'] ?? '' ?>">

      <label>Level</label>
      <input name="level" required value="<?= $editData['level'] ?? '' ?>">

      <label>Prize</label>
      <input name="prize" value="<?= $editData['prize'] ?? '' ?>">

      <label>Upload Certificate (image/pdf)</label>
      <input type="file" name="certificate" accept=".jpg,.jpeg,.png,.pdf">

      <?php if (!empty($editData['certificate'])): ?>
        <p>Current File: <a href="../<?= $editData['certificate'] ?>" target="_blank">View</a></p>
      <?php endif; ?>

      <button type="submit"><?= ($editData) ? 'Update' : 'Add' ?></button>

    </form>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
