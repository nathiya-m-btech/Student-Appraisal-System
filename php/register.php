<?php
require_once __DIR__ . '/db_connect.php';

$name       = trim($_POST['name'] ?? '');
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';
$role       = 'student';

// validation
if (!$name || !$username || !$email || !$password || !$confirm) {
    die("<script>alert('All fields required'); window.history.back();</script>");
}

if ($password !== $confirm) {
    die("<script>alert('Passwords do not match'); window.history.back();</script>");
}

$pdo = getPDO();

// Check if username exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    die("<script>alert('Username already exists'); window.history.back();</script>");
}

$pass_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert into users table
$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, email)
                       VALUES (?,?,?,?)");
$stmt->execute([$username, $pass_hash, $role, $email]);

$user_id = $pdo->lastInsertId();

// Insert into students table
$stmt = $pdo->prepare("INSERT INTO students (user_id, name) VALUES (?,?)");
$stmt->execute([$user_id, $name]);

echo "<script>
alert('Registration successful! Please login.');
window.location='/student_appraisal/public/enhanced_login.html';
</script>";
?>
