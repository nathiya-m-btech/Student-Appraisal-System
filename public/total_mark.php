<?php
session_start();

/*
 TOTAL MARK CALCULATION PAGE (FINAL STABLE VERSION)
 - No name column required
 - Prevents all unknown-column errors
 - Uses Python to calculate total
*/

// ---------------- DB CONNECTION ----------------
$cfg = [
  "host" => "127.0.0.1",
  "user" => "root",
  "password" => "",
  "database" => "student_appraisal"
];

$mysqli = new mysqli($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database']);
if ($mysqli->connect_errno) {
    die("DB error: " . $mysqli->connect_error);
}

// SECURITY: Student login required
if (!isset($_SESSION['student']['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = (int)$_SESSION['student']['student_id'];


// ---------------- FETCH MARKS ----------------
// No name column used → avoids all errors
$query = "
    SELECT 
        test_mark,
        attendance_mark,
        certificate_mark,
        total_mark
    FROM students 
    WHERE student_id = $student_id
    LIMIT 1
";

$res = $mysqli->query($query);
$student = $res->fetch_assoc();

if (!$student) {
    die("Student not found in database.");
}

$test_mark = $student['test_mark'];
$attendance_mark = $student['attendance_mark'];
$certificate_mark = $student['certificate_mark'];
$total_mark = $student['total_mark'];


// ---------------- RUN PYTHON IF BUTTON CLICKED ----------------
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['calculate'])) {

    $python_script = __DIR__ . "/python/mark_calculator.py";

    if (!file_exists($python_script)) {
        die("<h3>Error: Python file missing!</h3>");
    }

    // Run python script with student_id
    $cmd = "python3 " . escapeshellcmd($python_script) . " " . escapeshellarg($student_id);

    $output = [];
    exec($cmd, $output, $status);

    if ($status === 0) {
        // Python returned total
        $new_total = (int)$output[0];

        // Update total_mark in DB
        $stmt = $mysqli->prepare("UPDATE students SET total_mark=? WHERE student_id=?");
        $stmt->bind_param("ii", $new_total, $student_id);
        $stmt->execute();

        $total_mark = $new_total;
        $message = "Total mark updated successfully!";
    } else {
        $message = "Python calculation failed. Check script.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Total Mark Calculation</title>
<style>
body { font-family: Arial; background:#f0f2f5; padding:30px; }
.container {
    max-width: 600px;
    margin:auto;
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 0 12px rgba(0,0,0,0.1);
}
.field { margin:12px 0; font-size:18px; }
button {
    padding:12px 20px;
    border:none;
    background:#0066ff;
    color:white;
    border-radius:8px;
    font-size:16px;
    cursor:pointer;
}
.msg { font-weight:bold; margin-top:15px; }
</style>
</head>
<body>

<div class="container">
<h2>Total Mark Calculation</h2>

<div class="field"><b>Student ID:</b> <?= $student_id ?></div>
<div class="field"><b>Test Mark:</b> <?= $test_mark ?></div>
<div class="field"><b>Attendance Mark:</b> <?= $attendance_mark ?></div>
<div class="field"><b>Certificate Mark:</b> <?= $certificate_mark ?></div>

<hr>

<div class="field"><b>Total Mark:</b> <?= $total_mark ?></div>

<form action="/php/score_ranking.php" method="post">
    <button type="submit" class="btn btn-warning">
        Calculate Marks
    </button>
</form>


<?php if (!empty($message)): ?>
    <div class="msg"><?= $message ?></div>
<?php endif; ?>

</div>

</body>
</html>
