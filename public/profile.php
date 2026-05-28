<?php
session_start();
require_once "../php/db_connect.php";

// Check student login
if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();
$user_id = $_SESSION['student']['user_id'];

/* =========================
   PROFILE PIC UPLOAD
========================= */
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];

    if ($file['error'] === 0) {
        $folder = "uploads/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $filename = time() . "_" . basename($file['name']);
        $path = $folder . $filename;

        if (move_uploaded_file($file['tmp_name'], $path)) {
            $stmt = $pdo->prepare(
                "UPDATE students SET profile_pic=? WHERE user_id=?"
            );
            $stmt->execute([$filename, $user_id]);
            $msg = "Profile picture updated successfully";
        }
    }
}

/* =========================
   FETCH STUDENT DETAILS
========================= */
$sql = "
SELECT 
    s.name,
    s.roll,
    s.dob,
    s.batch,
    s.department,
    s.mentor_name,
    s.family_members,
    s.profile_pic,
    u.email
FROM students s
JOIN users u ON s.user_id = u.id
WHERE s.user_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

function show($arr, $key) {
    return htmlspecialchars($arr[$key] ?? '');
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Profile</title>
<style>
body { font-family: Arial; background:#f4f4f4; }
.profile-container {
    max-width:650px;
    background:white;
    padding:25px;
    margin:40px auto;
    border-radius:10px;
}
.profile-pic {
    width:150px;
    height:150px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #ccc;
}
table { width:100%; margin-top:20px; }
td { padding:10px; border-bottom:1px solid #ddd; }
.msg { color:green; font-weight:bold; }
</style>
</head>

<body>

<div class="profile-container">
<h2>Student Profile</h2>

<?php if (!empty($msg)) echo "<p class='msg'>$msg</p>"; ?>

<form method="post" enctype="multipart/form-data">
    <img src="uploads/<?= show($student,'profile_pic') ?: 'default.png' ?>" class="profile-pic"><br><br>
    <input type="file" name="profile_pic" required>
    <button name="upload_pic">Upload</button>
</form>

<table>
<tr><td><b>Full Name</b></td><td><?= show($student,'name') ?></td></tr>
<tr><td><b>Register Number</b></td><td><?= show($student,'roll') ?></td></tr>
<tr><td><b>Date of Birth</b></td><td><?= show($student,'dob') ?></td></tr>
<tr><td><b>Department</b></td><td><?= show($student,'department') ?></td></tr>
<tr><td><b>Batch</b></td><td><?= show($student,'batch') ?></td></tr>
<tr><td><b>Mentor Name</b></td><td><?= show($student,'mentor_name') ?></td></tr>
<tr><td><b>Family Members</b></td><td><?= show($student,'family_members') ?></td></tr>
<tr><td><b>Email</b></td><td><?= show($student,'email') ?></td></tr>
</table>

</div>
</body>
</html>
