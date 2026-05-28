<?php
session_start();
require_once __DIR__ . '/db_connect.php';

/* ===============================
   LOGIN CHECK
================================ */
if (!isset($_SESSION['student'])) {
    die("Unauthorized access");
}

$pdo = getPDO();
$student_id = $_SESSION['student']['student_id'];

/* ===============================
   FILE UPLOAD CHECK
================================ */
if (!isset($_FILES['certificate'])) {
    die("No file uploaded");
}

$upload_dir = __DIR__ . '/../public/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_name = time() . "_" . basename($_FILES['certificate']['name']);
$target_file = $upload_dir . $file_name;

if (!move_uploaded_file($_FILES['certificate']['tmp_name'], $target_file)) {
    die("File upload failed");
}

/* ===============================
   RUN OCR (PYTHON)
================================ */
$python = "C:/Users/YourUser/AppData/Local/Programs/Python/Python39/python.exe"; 
$script = realpath(__DIR__ . '/../python/certificate_ocr.py');
$image  = realpath($target_file);

$cmd = "\"$python\" \"$script\" \"$image\"";
$ocr_text = shell_exec($cmd);

if (!$ocr_text) {
    $ocr_text = "";
}

/* ===============================
   SIMPLE MARK LOGIC (DEMO)
================================ */
$certificate_mark = 10; // default

if (stripos($ocr_text, 'national') !== false) {
    $certificate_mark = 50;
} elseif (stripos($ocr_text, 'state') !== false) {
    $certificate_mark = 30;
} elseif (stripos($ocr_text, 'college') !== false) {
    $certificate_mark = 20;
}

/* ===============================
   SAVE CERTIFICATE
================================ */
$stmt = $pdo->prepare("
    INSERT INTO certificates (student_id, file_path, extracted_text)
    VALUES (?, ?, ?)
");
$stmt->execute([
    $student_id,
    'uploads/' . $file_name,
    $ocr_text
]);

/* ===============================
   INSERT MARKS (IMPORTANT)
================================ */
$stmt2 = $pdo->prepare("
    INSERT INTO marks (student_id, category, raw_score, computed_marks)
    VALUES (?, 'certificate', ?, ?)
");
$stmt2->execute([
    $student_id,
    $certificate_mark,
    $certificate_mark
]);

/* ===============================
   SUCCESS
================================ */
header("Location: ../public/score_ranking.php?success=1");
exit;
