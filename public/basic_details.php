<?php
session_start();
require_once __DIR__ . '/../php/db_connect.php';
$pdo = getPDO();

/* -----------------------------------------------------------
   CHECK STUDENT LOGIN SESSION
----------------------------------------------------------- */
if (!isset($_SESSION['student']) || !isset($_SESSION['student']['user_id'])) {
    die("ERROR: Student session missing. Login must store: \$_SESSION['student']['user_id']");
}

$user_id = $_SESSION['student']['user_id'];

/* -----------------------------------------------------------
   FETCH STUDENT BASIC DETAILS
----------------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$details = $stmt->fetch(PDO::FETCH_ASSOC);

/* -----------------------------------------------------------
   EDIT MODE
----------------------------------------------------------- */
$edit_mode = isset($_GET['edit']) ? true : false;

/* -----------------------------------------------------------
   FORM SUBMISSION
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name           = $_POST['name'];
    $roll           = $_POST['roll'];
    $batch          = $_POST['batch'];
    $department     = $_POST['department'];
    $mentor_name    = $_POST['mentor_name'];
    $family_members = $_POST['family_members'];
    $dob            = $_POST['dob'];

    if ($details) {
        // UPDATE
        $update = $pdo->prepare("UPDATE students SET
            name=?, roll=?, batch=?, department=?, mentor_name=?, family_members=?, dob=?
            WHERE user_id=?");

        $update->execute([
            $name, $roll, $batch, $department, $mentor_name, $family_members, $dob,
            $user_id
        ]);

    } else {
        // INSERT
        $insert = $pdo->prepare("INSERT INTO students 
            (user_id, name, roll, batch, department, mentor_name, family_members, dob, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $insert->execute([
            $user_id, $name, $roll, $batch, $department,
            $mentor_name, $family_members, $dob
        ]);
    }

    header("Location: basic_details.php?saved=1");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Basic Details</title>

    <style>
        body {
            font-family: Arial;
            margin: 0;
            padding: 0;
            background: url('background.gif') no-repeat center center/cover;
            height: 100vh;
            color: white;
        }

        .container {
            max-width: 500px;
            margin: 60px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #fff;
        }

        /* Top buttons */
        .top-buttons {
            text-align: center;
            margin-bottom: 20px;
        }

        .top-buttons a {
            display: inline-block;
            padding: 10px 18px;
            margin: 5px;
            background: #2196f3;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .top-buttons a:hover {
            background: #42a5f5;
        }

        label {
            font-weight: bold;
            display: block;
            margin: 15px 0 5px 2px;
            color: white;
        }

        input {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: none;
            background: rgba(255,255,255,0.25);
            color: white;
            outline: none;
        }

        input:read-only {
            background: rgba(255,255,255,0.1);
            cursor: not-allowed;
        }

        input::placeholder {
            color: #f0f0f0;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #00c853;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #03e97b;
        }

        .msg {
            color: #00ff80;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>

</head>
<body>

<div class="container">

<h1>Basic Details</h1>

<div class="top-buttons">
    <?php if (!$edit_mode): ?>
        <a href="basic_details.php?edit=1">✏ Edit</a>
    <?php endif; ?>
    <a href="student_dashboard.php">⬅ Back to Dashboard</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <p class="msg">✔ Details saved successfully!</p>
<?php endif; ?>

<form method="POST">

    <label>Name</label>
    <input type="text" name="name" required 
           value="<?= $details['name'] ?? '' ?>"
           <?= $edit_mode ? '' : 'readonly' ?>>

    <label>Roll Number</label>
    <input type="text" name="roll" required 
           value="<?= $details['roll'] ?? '' ?>"
           <?= $edit_mode ? '' : 'readonly' ?>>

    <label>Batch</label>
    <input type="text" name="batch" required 
           value="<?= $details['batch'] ?? '' ?>"
           <?= $edit_mode ? '' : 'readonly' ?>>

    <label>Department</label>
    <input type="text" name="department" required 
           value="<?= $details['department'] ?? '' ?>"
           <?= $edit_mode ? '' : 'readonly' ?>>

    <label>Mentor Name</label>
    <input type="text" name="mentor_name" 
           value="<?= $details['mentor_name'] ?? '' ?>"
           <?= $edit_mode ? '' : 'readonly' ?>>

    <label>Family Members</label>
    <input type="number" name="family_members"
           value="<?= $details['family_members'] ?? '' ?>"
           <?= $edit_mode ? '' : 'readonly' ?>>

    <label>Date of Birth</label>
    <input type="date" name="dob"
           value="<?= $details['dob'] ?? '' ?>"
           <?= $edit_mode ? '' : 'readonly' ?>>

    <?php if ($edit_mode): ?>
        <button type="submit">Save Changes</button>
    <?php endif; ?>

</form>

</div>

</body>
</html>
