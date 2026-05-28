<?php
require_once "../php/db_connect.php";

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pdo = getPDO();

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check username exists
    $check = $pdo->prepare("SELECT username FROM users WHERE username = ?");
    $check->execute([$username]);

    if ($check->fetch()) {
        $message = "Username already exists!";
    } else {

        // Insert into users table
        $stmt = $pdo->prepare("
            INSERT INTO users(username, email, password_hash, role)
            VALUES(?, ?, ?, 'student')
        ");
        $stmt->execute([$username, $email, $password]);

        $user_id = $pdo->lastInsertId();

        // Auto-create student profile row
        $pdo->prepare("INSERT INTO students(user_id) VALUES(?)")->execute([$user_id]);

        header("Location: login.php?msg=registered");
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
<title>Student Registration</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-dark text-white">

<div class="container mt-5">
    <h2 class="text-center mb-4">Student Registration</h2>

    <?php if ($message): ?>
        <div class="alert alert-warning"><?=$message?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label>Username</label>
            <input name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input name="password" type="password" class="form-control" required>
        </div>

        <button class="btn btn-warning w-100">Register</button>

    </form>
</div>

</body>
</html>
