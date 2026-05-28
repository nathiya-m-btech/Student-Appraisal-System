<?php 
session_start();
require_once __DIR__ . '/../php/db_connect.php';

// Allow both student & admin (admin view shows scores only)
if (!isset($_SESSION['student']) && !isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'] ?? null;
$message = "";

/* -----------------------------------------------------------
   HANDLE FORM SUBMISSION (STUDENT)
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['student'])) {

    $student_id = $_SESSION['student']['student_id'];

    $type = $_POST['type'] ?? null;
    $year = intval($_POST['year'] ?? 0);
    $semester = intval($_POST['semester'] ?? 0);
    $activity = $_POST['activity'] ?? null;
    $eventType = $_POST['eventType'] ?? null;
    $winning = $_POST['winning'] ?? null;
    $participation = $_POST['participation'] ?? null;
    $eventName = $_POST['eventName'] ?? null;

    // Handle certificate upload
    $certificate = null;
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === 0) {
        $ext = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $filename = $student_id . "_" . time() . "." . $ext;
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $target = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $target)) {
            $certificate = $filename;

            // Windows-safe OCR call
            $cmd = "python \"" . __DIR__ . "/../certificate_ocr.py\" \"" . $target . "\" 0";
            exec($cmd);
        }
    }

    // Marks Allocation
    $marks = 0;
    if ($type === "NSS" || $type === "Extension") $marks = 5;
    elseif ($type === "Sports") $marks = 10;

    // Insert into DB
    $stmt = $pdo->prepare("INSERT INTO non_academic 
        (student_id,type,year,semester,activity,eventType,winning,participation,eventName,certificate,marks,created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");

    if ($stmt->execute([$student_id,$type,$year,$semester,$activity,$eventType,$winning,$participation,$eventName,$certificate,$marks])) {
        $message = "✅ Entry added successfully!";

        // Windows SAFE scoring call (NO python3 NO background)
        $score_cmd = "python \"" . __DIR__ . "/../run_scoring.py\"";
        exec($score_cmd);
    } else {
        $message = "❌ Failed to add entry.";
    }
}


/* -----------------------------------------------------------
   FETCH ALL ENTRIES
----------------------------------------------------------- */
$entries = [];
if ($student_id) {
    $stmt = $pdo->prepare("SELECT * FROM non_academic WHERE student_id=? ORDER BY created_at DESC");
    $stmt->execute([$student_id]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/* -----------------------------------------------------------
   FIX FOR WARNING (bool-to-array fix)
   Fetch latest entry safely
----------------------------------------------------------- */
$latest = [];

if ($student_id) {
    try {
        $stmt2 = $pdo->prepare("SELECT * FROM non_academic 
                                WHERE student_id=? ORDER BY created_at DESC LIMIT 1");
        $stmt2->execute([$student_id]);
        $latest = $stmt2->fetch(PDO::FETCH_ASSOC);

        if (!is_array($latest)) {     // IMPORTANT FIX
            $latest = [];
        }
    } catch (PDOException $e) {
        $latest = [];
    }
}

$activity_display = $latest['activity'] ?? $latest['eventName'] ?? 'No activity';
$award_display = $latest['winning'] ?? 'No award';


/* -----------------------------------------------------------
   FETCH STUDENT TOTAL MARKS + FINAL SCORE
----------------------------------------------------------- */
$student_total_marks = null;
$student_final_score = null;

if ($student_id) {
    $stmtR = $pdo->prepare("SELECT topsis_score, final_score FROM rankings WHERE student_id = ?");
    $stmtR->execute([$student_id]);
    $rank_row = $stmtR->fetch(PDO::FETCH_ASSOC);

    if ($rank_row && is_array($rank_row)) {
        $student_final_score = $rank_row['final_score'];
    }

    // If total marks stored in students table
    $stmtS = $pdo->prepare("SELECT total_marks FROM students WHERE student_id=?");
    $stmtS->execute([$student_id]);
    $m = $stmtS->fetch(PDO::FETCH_ASSOC);
    if ($m && is_array($m)) {
        $student_total_marks = $m['total_marks'];
    }
}


/* -----------------------------------------------------------
   ADMIN VIEW: ALL STUDENTS
----------------------------------------------------------- */
$all_students_summary = [];

if (isset($_SESSION['admin'])) {
    $stmtAll = $pdo->query("
        SELECT s.student_id, s.name, s.total_marks,
               r.topsis_score, r.final_score
        FROM students s
        LEFT JOIN rankings r ON s.student_id = r.student_id
        ORDER BY r.final_score DESC
    ");
    $all_students_summary = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
}


/* -----------------------------------------------------------
   TABLE FUNCTION
----------------------------------------------------------- */
function renderTable($data) {
    if (!$data) return "<p style='text-align:center;color:#777;'>No data available.</p>";

    $html = '<div class="table-container"><table><tr>
        <th>Type</th><th>Year</th><th>Semester</th><th>Activity/Event</th>
        <th>Certificate</th><th>Marks</th><th>Added</th></tr>';

    foreach ($data as $row) {
        $activityName = $row['activity'] ?? $row['eventName'] ?? '-';
        $certLink = $row['certificate'] ? '<a href="uploads/'.$row['certificate'].'" target="_blank">View</a>' : '-';
        $added = $row['created_at'] ?? '-';

        $html .= "<tr>
            <td>{$row['type']}</td>
            <td>{$row['year']}</td>
            <td>{$row['semester']}</td>
            <td>{$activityName}</td>
            <td>{$certLink}</td>
            <td>{$row['marks']}</td>
            <td>{$added}</td>
        </tr>";
    }

    $html .= '</table></div>';
    return $html;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Non-Academic Activities</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg,#a1c4fd,#c2e9fb); padding:20px; }
h1,h2 { text-align:center; color:#003366; }
form { max-width:700px; margin:auto; background:#fff; padding:20px; border-radius:12px; box-shadow:0 6px 12px rgba(0,0,0,0.1);}
input,select,button { width:100%; padding:8px; margin:6px 0; border-radius:6px; border:1px solid #ccc;}
button { background:#003366; color:#fff; font-weight:600; border:none; cursor:pointer;}
button:hover { background:#00509e;}
.table-container { margin-top:20px; overflow-x:auto; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden;}
th,td { padding:10px; border:1px solid #ccc; text-align:left;}
th { background:#003366; color:#fff;}
tr:nth-child(even) { background:#f9f9f9;}
a { color:#003366; text-decoration:underline;}
.message { text-align:center; font-weight:bold; color:green;}
.summary { max-width:700px; margin:20px auto; background:#fff; padding:12px; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.06); }
</style>
</head>

<body>

<h1><i class="fa fa-user-graduate"></i> Non-Academic Activities</h1>

<?php if($message) echo "<p class='message'>$message</p>"; ?>

<?php if (isset($_SESSION['student'])): ?>

<!-- ADD FORM -->
<h2>Add Activity</h2>
<form method="post" enctype="multipart/form-data">

    <label>Type</label>
    <select name="type" required>
        <option value="">Select Type</option>
        <option value="NSS">NSS</option>
        <option value="Extension">Extension</option>
        <option value="Sports">Sports</option>
    </select>

    <label>Year</label>
    <select name="year"><?php for($i=1;$i<=4;$i++) echo "<option>$i</option>"; ?></select>

    <label>Semester</label>
    <select name="semester"><?php for($i=1;$i<=8;$i++) echo "<option>$i</option>"; ?></select>

    <label>Activity Name</label><input type="text" name="activity">
    <label>Event Type (Sports)</label><input type="text" name="eventType">
    <label>Winning</label><input type="text" name="winning">
    <label>Participation</label><input type="text" name="participation">
    <label>Event Name</label><input type="text" name="eventName">
    <label>Upload Certificate</label><input type="file" name="certificate">

    <button type="submit"><i class="fa fa-plus"></i> Add Entry</button>
</form>


<!-- STUDENT SCORE SUMMARY -->
<div class="summary">
    <h2>Your Latest Non-Academic Detail</h2>
    <p><b>Activity:</b> <?= htmlspecialchars($activity_display) ?></p>
    <p><b>Award:</b> <?= htmlspecialchars($award_display) ?></p>

    <h3>Your Marks & Score</h3>
    <p><b>Total non-academic marks:</b> <?= htmlspecialchars($student_total_marks ?? 'Not calculated yet') ?></p>
    <p><b>Final score:</b> <?= htmlspecialchars($student_final_score ?? 'Not calculated yet') ?></p>
</div>

<?php endif; ?>

<h2>Your Entries</h2>
<?= renderTable($entries) ?>



</body>
</html>
