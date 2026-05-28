<?php
session_start();

/*
  CAREER PATH SYSTEM – FULL WORKING CODE
  Options:
    1) Higher Education
    2) Placement
    3) Off campus Placement
*/

// DATABASE CONNECTION
$cfg = [
  'host' => '127.0.0.1',
  'user' => 'root',
  'password' => '',
  'database' => 'student_appraisal',
];

$mysqli = new mysqli($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database']);
if ($mysqli->connect_errno) {
    die("DB connect error: " . $mysqli->connect_error);
}

// Check login
if (!isset($_SESSION['student']['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = (int)$_SESSION['student']['student_id'];

// ------------------- POST HANDLERS ------------------- //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header("Content-Type: application/json");

    $action = $_POST['action'];

    // ---------- SELECT PATH ---------- //
    if ($action === "select_path" && isset($_POST['path'])) {
        $path = $mysqli->real_escape_string($_POST['path']);

        // Check locked
        $result = $mysqli->query("SELECT career_locked FROM students WHERE student_id = $student_id");
        $row = $result->fetch_assoc();

        if ($row['career_locked'] == 1) {
            echo json_encode(["success" => false, "msg" => "Locked. Click Change Career Path to unlock."]);
            exit;
        }

        // Update DB + lock
        $stmt = $mysqli->prepare("UPDATE students SET career_path=?, career_locked=1 WHERE student_id=?");
        $stmt->bind_param("si", $path, $student_id);
        $stmt->execute();

        echo json_encode(["success" => true, "msg" => "Career path selected and locked."]);
        exit;
    }

    // ---------- UNLOCK ---------- //
    if ($action === "unlock") {
        $stmt = $mysqli->prepare("UPDATE students SET career_locked=0 WHERE student_id=?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "msg" => "Unknown action"]);
    exit;
}

// ------------------- FETCH CURRENT DATA ------------------- //
$res = $mysqli->query("SELECT career_path, career_locked FROM students WHERE student_id=$student_id LIMIT 1");
$student = $res->fetch_assoc();

$current = $student['career_path'];
$locked = (int)$student['career_locked'];

// ------------------- CAREER PATH OPTIONS ------------------- //
$paths = ["Higher Education", "Placement", "Off campus Placement"];

?>
<!DOCTYPE html>
<html>
<head>
<title>Career Path Selection</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
body { font-family: Arial; background:#f0f2f5; padding:30px; }
.container { background:#fff; padding:25px; max-width:800px; margin:auto; border-radius:12px; box-shadow:0 0 12px rgba(0,0,0,0.1); }
h2 { margin-top:0; }
.paths { display:flex; gap:15px; flex-wrap:wrap; margin-top:20px; }
.path {
    padding:15px 20px;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
    border:2px solid transparent;
    min-width:200px;
    text-align:center;
}
.selected { background:#e0ffe9; color:#006622; border:2px solid #26c95f; }
.not-selected { background:#ffe5e5; color:#8f0000; border:2px solid #ff4d4d; }
.locked-badge { padding:4px 8px; background:#333; color:#fff; border-radius:6px; font-size:12px; margin-left:10px; }
button { padding:10px 16px; border:none; cursor:pointer; border-radius:8px; font-weight:bold; }
.warn { background:#ff944d; color:white; }
.primary { background:#0066ff; color:white; }
#msg { margin-top:15px; font-weight:bold; }
</style>
</head>
<body>
<div class="container">

<h2>
  Select Your Career Path
  <?php if ($locked == 1): ?>
    <span class="locked-badge">Locked</span>
  <?php endif; ?>
</h2>

<div class="paths" id="paths">
<?php foreach ($paths as $p): ?>
    <?php $cls = ($current == $p) ? "selected" : "not-selected"; ?>
    <div class="path <?php echo $cls; ?>" data-path="<?php echo $p; ?>">
        <?php echo $p; ?>
        <?php if ($current == $p): ?>
            <div style="font-size:12px; margin-top:5px;">Selected</div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<br>

<button id="changeBtn" class="warn">Change Career Path</button>

<div id="unlockArea" style="display:none; margin-top:10px;">
    <button id="confirmUnlock" class="primary">Unlock Now</button>
    <button id="cancelUnlock">Cancel</button>
</div>

<div id="msg"></div>

</div>

<script>
let locked = <?php echo $locked; ?>;
const paths = document.querySelectorAll(".path");
const msg = document.getElementById("msg");
const changeBtn = document.getElementById("changeBtn");
const unlockArea = document.getElementById("unlockArea");
const confirmUnlock = document.getElementById("confirmUnlock");
const cancelUnlock = document.getElementById("cancelUnlock");

function showMsg(text, ok=true) {
    msg.textContent = text;
    msg.style.color = ok ? "green" : "red";
}

paths.forEach(el => {
    el.onclick = () => {
        if (locked === 1) {
            showMsg("Career path is locked. Click Change Career Path.", false);
            return;
        }

        const path = el.dataset.path;
        if (!confirm("Select '" + path + "' and lock your choice?")) return;

        const fd = new FormData();
        fd.append("action", "select_path");
        fd.append("path", path);

        fetch("", {method:"POST", body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                locked = 1;
                showMsg(res.msg);

                document.querySelectorAll(".locked-badge").forEach(x => x.remove());
                const badge = document.createElement("span");
                badge.className = "locked-badge";
                badge.textContent = "Locked";
                document.querySelector("h2").appendChild(badge);

                paths.forEach(p => {
                    if (p.dataset.path === path) {
                        p.classList.remove("not-selected");
                        p.classList.add("selected");
                    } else {
                        p.classList.remove("selected");
                        p.classList.add("not-selected");
                    }
                });
            } else {
                showMsg(res.msg, false);
            }
        });
    };
});

// Change Career Path Button
changeBtn.onclick = () => {
    unlockArea.style.display = "block";
    changeBtn.style.display = "none";
};

cancelUnlock.onclick = () => {
    unlockArea.style.display = "none";
    changeBtn.style.display = "inline-block";
};

// Confirm Unlock
confirmUnlock.onclick = () => {
    const fd = new FormData();
    fd.append("action", "unlock");

    fetch("", {method:"POST", body:fd})
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            locked = 0;

            // Remove locked badge
            document.querySelectorAll(".locked-badge").forEach(x => x.remove());

            showMsg("Unlocked. You can now select a new career path.");

            unlockArea.style.display = "none";
            changeBtn.style.display = "inline-block";
        } else {
            showMsg("Unlock failed.", false);
        }
    });
};
</script>

</body>
</html>
