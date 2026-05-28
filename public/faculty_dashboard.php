<?php
require_once __DIR__ . '/../../php/db_connect.php';
session_start();
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'faculty' && $_SESSION['role'] !== 'admin')) { header('Location: /student_appraisal/public/enhanced_login.html'); exit; }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Faculty Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/student_appraisal/public/assets/styles.css">
</head><body class="dashboard-bg">
  <div class="d-flex" id="app-root">
    <aside class="sidebar shadow-sm">
      <div class="sidebar-header text-center">
        <img src="/student_appraisal/public/assets/logo.svg" class="logo-sm mb-2">
        <h6 class="m-0">MKCE Faculty</h6>
      </div>
      <nav class="nav flex-column mt-3">
        <a class="nav-link active" href="#">Overview</a>
        <a class="nav-link" href="#">Verify Certificates</a>
        <a class="nav-link" href="#">Interviews</a>
      </nav>
      <div class="sidebar-footer mt-auto p-3 text-center small text-muted">Faculty Tools</div>
    </aside>
    <main class="content p-4">
      <h3>Pending Certificate Verifications</h3>
      <div class="card p-3">
        <ul class="list-group" id="pendingCerts">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            AI Workshop — John Smith
            <div>
              <button class="btn btn-sm btn-success">Approve</button>
              <button class="btn btn-sm btn-danger">Reject</button>
            </div>
          </li>
        </ul>
      </div>
    </main>
  </div>
  <script src="/student_appraisal/public/assets/app.js"></script>
</body></html>
