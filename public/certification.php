<?php
session_start();
require_once "../php/db_connect.php";

if (!isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

$errors = [];
$msg = "";

/* ---------------------------------------------------------
   DELETE CERTIFICATE
--------------------------------------------------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt0 = $pdo->prepare("SELECT file_path FROM certifications WHERE cert_id=? AND student_id=?");
    $stmt0->execute([$id, $student_id]);
    $file = $stmt0->fetchColumn();

    if ($file && file_exists(__DIR__ . "/" . $file)) {
        unlink(__DIR__ . "/" . $file);
    }

    $pdo->prepare("DELETE FROM certifications WHERE cert_id=? AND student_id=?")
        ->execute([$id, $student_id]);

    header("Location: certifications.php");
    exit;
}

/* ---------------------------------------------------------
   ADD CERTIFICATE
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cert_name = trim($_POST['cert_name'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $year = intval($_POST['year'] ?? 0);

    if ($cert_name === '') $errors[] = "Certificate name required.";
    if ($organization === '') $errors[] = "Organization required.";

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Please upload certificate file.";
    }

    if (empty($errors)) {

        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $original = basename($_FILES['file']['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Allowed: PDF, JPG, JPEG, PNG only.";
        } else {
            $safe = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($original, PATHINFO_FILENAME));
            $newName = time() . "_" . $safe . "." . $ext;
            $fullPath = $uploadDir . $newName;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $fullPath)) {
                $errors[] = "Upload failed.";
            } else {
                $dbPath = "uploads/" . $newName;

                $stmt = $pdo->prepare("
                    INSERT INTO certifications (student_id, cert_name, organization, year, file_path)
                    VALUES (?,?,?,?,?)
                ");
                $stmt->execute([$student_id, $cert_name, $organization, ($year ?: null), $dbPath]);

                $cert_id = $pdo->lastInsertId();

                // Run OCR silently in background
                $python = escapeshellarg(__DIR__ . '/../python/certificate_ocr.py');
                $fileEsc = escapeshellarg($fullPath);
                $idEsc = escapeshellarg((string)$cert_id);

                shell_exec("python3 $python $fileEsc $idEsc > /dev/null 2>&1 &");

                $msg = "Certificate uploaded successfully!";
            }
        }
    }
}

/* ---------------------------------------------------------
   FETCH CERTIFICATES
--------------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM certifications WHERE student_id=? ORDER BY cert_id DESC");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Certifications</title>
<style>
body {font-family:Arial;background:#f2f7ff;padding:20px;}
.container {width:95%;margin:auto;}
.add-btn {background:#007bff;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;font-weight:bold;}
.hidden-form {display:none;}
table {width:100%;background:white;margin-top:20px;border-collapse:collapse;box-shadow:0 3px 8px rgba(0,0,0,.1);}
th,td {padding:10px;border-bottom:1px solid #ddd;}
th {background:#e9f1ff;color:#003366;}
</style>

<script>
function toggleForm() {
    let f = document.getElementById("uploadForm");
    f.style.display = (f.style.display === "none") ? "block" : "none";
}
</script>
</head>

<body>
<div class="container">

    <div style="display:flex;justify-content:space-between;">
        <h2>Certifications</h2>
        <a class="add-btn" href="#" onclick="toggleForm()">+ Add</a>
    </div>

    <?php if ($errors): ?>
        <div style="color:red;">
            <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div style="color:green;"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- UPLOAD FORM -->
    <div id="uploadForm" class="hidden-form" style="background:#fff;padding:20px;margin-top:20px;box-shadow:0 3px 8px rgba(0,0,0,.1);">
        <form method="POST" enctype="multipart/form-data">
            <label>Certificate Name</label><br>
            <input name="cert_name" required><br><br>

            <label>Organization</label><br>
            <input name="organization" required><br><br>

            <label>Year</label><br>
            <input type="number" name="year"><br><br>

            <label>Certificate File (PDF / JPG / PNG)</label><br>
            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required><br><br>

            <button type="submit" style="background:#003366;color:white;padding:10px 16px;border:none;border-radius:6px;">
                Upload Certificate
            </button>
        </form>
    </div>

    <!-- CERTIFICATE TABLE -->
    <table>
        <tr>
            <th>Name</th>
            <th>Organization</th>
            <th>Year</th>
            <th>File</th>
            <th>Action</th>
        </tr>

        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['cert_name']) ?></td>
            <td><?= htmlspecialchars($r['organization']) ?></td>
            <td><?= htmlspecialchars($r['year']) ?></td>

            <td>
                <?php if ($r['file_path']): ?>
                    <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank">View</a>
                <?php endif; ?>
            </td>

            <td>
                <a href="certifications.php?delete=<?= $r['cert_id'] ?>" 
                   onclick="return confirm('Delete this certificate?')" 
                   style="color:red;">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

</div>
</body>
</html>
