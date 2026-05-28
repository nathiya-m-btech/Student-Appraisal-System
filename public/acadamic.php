<?php
session_start();
require_once __DIR__ . '/../php/db_connect.php';

// Ensure only logged-in admin or student can access
if (!isset($_SESSION['admin']) && !isset($_SESSION['student'])) {
    header("Location: login.php");
    exit;
}

$pdo = getPDO();

// Define categories (cards)
$cards = [
    ['name'=>'CGPA','icon'=>'fa-star','page'=>'cgpa.php'],
    ['name'=>'Coding Platform','icon'=>'fa-code','page'=>'coding_platform.php'],
    ['name'=>'Project','icon'=>'fa-project-diagram','page'=>'project.php'],
    ['name'=>'Hackathon','icon'=>'fa-trophy','page'=>'hackathon.php'],
    ['name'=>'Workshop / Seminar','icon'=>'fa-chalkboard-teacher','page'=>'workshop.php'],
    ['name'=>'Online Courses','icon'=>'fa-laptop-code','page'=>'online_course.php'],
    ['name'=>'International Certificate','icon'=>'fa-certificate','page'=>'certification.php'],
    ['name'=>'Internship / Inplant','icon'=>'fa-briefcase','page'=>'internship.php'],
    ['name'=>'Publications','icon'=>'fa-book','page'=>'publication.php']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Academic Details</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
  margin: 0;
  padding: 20px;
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
  min-height: 100vh;
  overflow-x: hidden;
  position: relative;
}

/* Animated Background Circles */
@keyframes float {
  0%,100% { transform: translateY(0) scale(1); opacity: 1; }
  50% { transform: translateY(-50px) scale(1.1); opacity: 0.8; }
}
.circle { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.25); animation: float 6s infinite; z-index: 0; }
.circle:nth-child(1){width:100px;height:100px;top:10%;left:15%;animation-delay:0s;}
.circle:nth-child(2){width:120px;height:120px;top:40%;left:70%;animation-delay:1s;}
.circle:nth-child(3){width:80px;height:80px;top:70%;left:30%;animation-delay:2s;}
.circle:nth-child(4){width:150px;height:150px;top:20%;left:85%;animation-delay:3s;}
.circle:nth-child(5){width:90px;height:90px;top:80%;left:60%;animation-delay:1.5s;}

h2 { text-align:center;color:#003366;margin-bottom:25px;font-size:28px;position:relative;z-index:2; }

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
  padding: 20px;
  position: relative;
  z-index: 2;
}

.card {
  text-align: center;
  padding: 20px 10px;
  cursor: pointer;
  color: #003366;
  font-weight: 600;
  border-radius: 12px;
  background: rgba(255,255,255,0.6);
  box-shadow: 0 3px 8px rgba(0,0,0,0.15);
  transition: transform 0.3s, background 0.3s;
}

.card:hover {
  transform: scale(1.06);
  background: rgba(255,255,255,0.9);
}

.card i { font-size: 36px; margin-bottom: 10px; display: block; }
.card h3 { font-size: 18px; margin:0; }
</style>
</head>

<body>

<!-- Background Circles -->
<div class="circle"></div>
<div class="circle"></div>
<div class="circle"></div>
<div class="circle"></div>
<div class="circle"></div>

<h2><i class="fa fa-graduation-cap"></i> Academic Details</h2>

<div class="grid">
<?php foreach($cards as $card): ?>
    <div class="card" onclick="navigate('<?php echo htmlspecialchars($card['page']); ?>')">
        <i class="fa <?php echo htmlspecialchars($card['icon']); ?>"></i>
        <h3><?php echo htmlspecialchars($card['name']); ?></h3>
    </div>
<?php endforeach; ?>
</div>

<script>
function navigate(page) {
    window.location.href = page;
}
</script>

</body>
</html>
