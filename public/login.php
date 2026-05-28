<?php
session_start();
require_once "../php/db_connect.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $pdo = getPDO("student_appraisal");

    // Fetch user from users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = "User not found.";
    } else if (!password_verify($password, $user['password_hash'])) {
        $message = "Incorrect password.";
    } else if ($user['role'] !== $role) {
        $message = "Role mismatch! You selected '$role' but account is '{$user['role']}'.";
    } 
    else {

        /* ---------------------------------------------------------
            STUDENT LOGIN
        --------------------------------------------------------- */
        if ($role === "student") {

            // Check if student already has basic details
            $stmt2 = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
            $stmt2->execute([$user['id']]);
            $studentData = $stmt2->fetch(PDO::FETCH_ASSOC);

            // SESSION FIX — STORE user_id ALWAYS
            $_SESSION['student'] = [
                'user_id'    => $user['id'],                        // ALWAYS needed
                'student_id' => $studentData['student_id'] ?? null, // null until basic details saved
                'username'   => $user['username']
            ];

            header("Location: student_dashboard.php");
            exit;
        }

        /* ---------------------------------------------------------
            ADMIN LOGIN
        --------------------------------------------------------- */
        if ($role === "admin") {
            $_SESSION['admin'] = [
                'admin_id' => $user['id'],
                'username' => $user['username'],
                'role'     => 'admin'
            ];
            header("Location: admin_dashboard.php");
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MKCE Connect — Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body, html {height:100%; margin:0; font-family:'Segoe UI',sans-serif; background:url('sub.gif') no-repeat center/cover;}
.wrapper {display:flex; height:100vh;}
.animation-section {flex:1; display:flex; align-items:center; justify-content:center;}
#lottie-animation {width:70%; max-width:500px;}
.login-section {flex:1; display:flex; justify-content:center; align-items:center; padding-right:5%;}
.login-card {
    width:100%; max-width:360px; padding:30px; border-radius:15px;
    background:rgba(255,255,255,0.1); backdrop-filter:blur(12px);
    box-shadow:0 8px 32px rgba(0,0,0,0.3); text-align:center; color:#fff;
}
.login-card h3 {margin-bottom:20px; font-weight:bold;}

.role-btn-group {display:flex; justify-content:space-between; margin-bottom:20px;}
.role-btn {flex:1; margin:0 5px;}
.role-btn.active {background:#ffd369 !important;color:#000 !important;border:none !important;}

.form-control {background:rgba(255,255,255,0.2); border:none; color:#fff;}
.form-control::placeholder {color:rgba(255,255,255,0.7);}
.form-control:focus {background:rgba(255,255,255,0.25); border:1px solid #fff; box-shadow:none; color:#fff;}

.login-btn {background:#ffd369; color:#000; font-weight:bold; border:none;}
.login-btn:hover {background:#fff; color:#000;}

.register-links a {font-weight:bold;}
</style>

</head>
<body>

<div class="wrapper">

  <!-- LEFT: Animation -->
  <div class="animation-section">
    <div id="lottie-animation"></div>
  </div>

  <!-- RIGHT: Login Form -->
  <div class="login-section">
    <div class="login-card">

      <img src="logo.jpg" alt="Logo" style="width:70px;margin-bottom:10px;">
      <h3>MKCE Student Appraisal</h3>

      <?php if (!empty($message)): ?>
      <div class="alert alert-danger py-2"><?=$message?></div>
      <?php endif; ?>

      <!-- ROLE SELECT -->
      <div class="role-btn-group">
        <button type="button" class="btn btn-outline-light role-btn active" onclick="setRole('student')" id="btnStudent">Student</button>
        <button type="button" class="btn btn-outline-light role-btn" onclick="setRole('admin')" id="btnAdmin">Admin</button>
      </div>

      <!-- Login Form -->
      <form method="POST" action="">
        <input type="hidden" name="role" id="role" value="student">

        <div class="mb-3">
            <input type="text" class="form-control" name="username" placeholder="Username / Email" required>
        </div>

        <div class="mb-3">
            <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>

        <button type="submit" class="btn login-btn w-100">Login</button>
      </form>

      <!-- Added Student/Admin Register Buttons -->
      <div class="mt-3">
          <a href="student_register.php" class="btn btn-outline-light w-100 mb-2">Register as Student</a>
          <a href="admin_register.php" class="btn btn-outline-warning w-100">Register as Admin</a>
      </div>

    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<script>
// Lottie Animation
lottie.loadAnimation({
    container: document.getElementById('lottie-animation'),
    renderer: 'svg',
    loop: true,
    autoplay: true,
    path: 'https://assets6.lottiefiles.com/packages/lf20_jcikwtux.json'
});

// Role Switcher
function setRole(r) {
    document.getElementById("role").value = r;

    document.getElementById("btnStudent").classList.remove("active");
    document.getElementById("btnAdmin").classList.remove("active");

    if (r === "student") {
        document.getElementById("btnStudent").classList.add("active");
    } else {
        document.getElementById("btnAdmin").classList.add("active");
    }
}
</script>

</body>
</html>
