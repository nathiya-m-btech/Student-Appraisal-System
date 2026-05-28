<?php
session_start();
require_once __DIR__ . '/db_connect.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) {
    echo "<script>alert('Enter username and password'); 
          window.location='../public/enhanced_login.html';</script>";
    exit;
}

$pdo = getPDO();

// GET USER DETAILS
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>alert('Invalid Username'); 
          window.location='../public/enhanced_login.html';</script>";
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    echo "<script>alert('Invalid Password'); 
          window.location='../public/enhanced_login.html';</script>";
    exit;
}

$role = $user['role'];

// --- STUDENT LOGIN ---
if ($role === 'student') {

    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    $_SESSION['student'] = [
        'id' => $user['id'],
        'name' => $student['name']
    ];

    header("Location: ../public/student_dashboard.php");
    exit;
}

// --- ADMIN LOGIN ---
if ($role === 'admin') {
    $_SESSION['admin'] = [
        'id' => $user['id'],
        'username' => $user['username']
    ];
    header("Location: ../public/admin_dashboard.php");
    exit;
}

// --- FACULTY LOGIN ---
if ($role === 'faculty') {
    $_SESSION['faculty'] = [
        'id' => $user['id'],
        'username' => $user['username']
    ];
    header("Location: ../public/faculty_dashboard.php");
    exit;
}

echo "<script>alert('Unknown role. Contact admin.');</script>";
?>
