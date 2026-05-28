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
   AUTO-DETECT CURRENT PAGE (FIXES "NOT FOUND" ERROR)
---------------------------------------------------- */
$current_page = basename($_SERVER['PHP_SELF']);

/* ----------------------------------------------------
   PROJECT SCORING FUNCTION
---------------------------------------------------- */
function calculateProjectScore($type) {
    $type = strtolower($type);

    if (strpos($type, 'international') !== false || 
        strpos($type, 'journal') !== false || 
        strpos($type, 'ieee') !== false)
        return 10;

    if (strpos($type, 'national') !== false || 
        strpos($type, 'conference') !== false)
        return 8;

    if (strpos($type, 'mini') !== false || 
        strpos($type, 'college') !== false)
        return 6;

    return 4;
}

/* ----------------------------------------------------
   DELETE PROJECT
---------------------------------------------------- */
if (isset($_GET['delete'])) {
    $deleteID = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM projects WHERE id=? AND student_id=?")
        ->execute([$deleteID, $student_id]);

    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   INSERT / UPDATE PROJECT
---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id   = $_POST['id'] ?? '';
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $type  = trim($_POST['project_type']);
    $link  = trim($_POST['link']);

    $score = calculateProjectScore($type);

    if ($id == '') {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO projects 
                (student_id, title, description, project_type, link, score)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$student_id, $title, $desc, $type, $link, $score]);

    } else {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE projects SET
                title=?, description=?, project_type=?, link=?, score=?
            WHERE id=? AND student_id=?
        ");
        $stmt->execute([$title, $desc, $type, $link, $score, $id, $student_id]);
    }

    header("Location: $current_page");
    exit;
}

/* ----------------------------------------------------
   FETCH PROJECT LIST
---------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM projects WHERE student_id=? ORDER BY id DESC");
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
        $stmt2 = $pdo->prepare("SELECT * FROM projects WHERE id=? AND student_id=?");
        $stmt2->execute([$eid, $student_id]);
        $editData = $stmt2->fetch(PDO::FETCH_ASSOC);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Projects</title>

<style>
body {
    font-family: Arial;
    background: #f2f7ff;
    padding: 20px;
}
.container { width: 95%; margin: auto; }
.top-bar { display: flex; justify-content: space-between; }
.top-bar h2 { margin: 0; color: #003366; }
.add-btn {
    background:#007bff; color:#fff; padding:10px 16px;
    border-radius:6px; text-decoration:none; font-weight:bold;
}
table {
    width:100%; border-collapse:collapse; background:white;
    margin-top:20px; box-shadow:0 3px 8px rgba(0,0,0,0.1);
}
table th, table td { padding:10px; border-bottom:1px solid #ddd; }
table th { background:#e9f1ff; color:#003366; }
.action-btn { padding:6px 10px; color:white; border-radius:4px; text-decoration:none; }
.edit-btn { background:#28a745; }
.delete-btn { background:#dc3545; }
.form-box {
    background:white; padding:20px; margin-top:20px;
    box-shadow:0 3px 8px rgba(0,0,0,0.15);
}
.form-box input, .form-box textarea {
    width:100%; padding:8px; margin-bottom:12px;
}
textarea { height:100px; }
.form-box button {
    background:#003366; color:white; padding:10px 15px; border:none;
    border-radius:6px;
}
</style>
</head>

<body>
<div class="container">

<div class="top-bar">
    <h2>Projects</h2>

    <!-- FIXED BUTTON: ALWAYS POINTS TO THIS SAME PAGE -->
    <a href="<?= $current_page ?>?edit=0" class="add-btn">+ Add Project</a>
</div>

<table>
<tr>
    <th>Project Title</th>
    <th>Description</th>
    <th>Type / Category</th>
    <th>Link</th>
    <th>Allocated Marks</th>
    <th>Action</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['title']) ?></td>
    <td><?= htmlspecialchars($r['description']) ?></td>
    <td><?= htmlspecialchars($r['project_type']) ?></td>

    <td>
        <?php if ($r['link']): ?>
            <a href="<?= htmlspecialchars($r['link']) ?>" target="_blank">View</a>
        <?php endif; ?>
    </td>

    <td><b><?= htmlspecialchars($r['score']) ?></b></td>

    <td>
        <a href="<?= $current_page ?>?edit=<?= $r['id'] ?>" class="action-btn edit-btn">Edit</a>
        <a href="<?= $current_page ?>?delete=<?= $r['id'] ?>" class="action-btn delete-btn"
           onclick="return confirm('Delete this project?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</table>


<?php if ($showForm): ?>
<div class="form-box">
<form method="POST">

<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<label>Project Title:</label>
<input name="title" required value="<?= $editData['title'] ?? '' ?>">

<label>Description:</label>
<textarea name="description" required><?= $editData['description'] ?? '' ?></textarea>

<label>Project Type (International / National / Mini Project / etc):</label>
<input name="project_type" required value="<?= $editData['project_type'] ?? '' ?>">

<label>Project Link (Optional):</label>
<input name="link" value="<?= $editData['link'] ?? '' ?>">

<button type="submit">
    <?= ($editData) ? "Update Project" : "Add Project" ?>
</button>

</form>
</div>
<?php endif; ?>

</div>
</body>
</html>
