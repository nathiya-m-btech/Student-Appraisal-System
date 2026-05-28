<?php
require_once "db_connect.php";
$pdo = getPDO();

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Insert user
$stmt = $pdo->prepare("INSERT INTO users(username,email,password_hash,role) VALUES(?,?,?,'student')");
$stmt->execute([$username,$email,$password]);

$user_id = $pdo->lastInsertId();

// Create student row
$pdo->prepare("INSERT INTO students(user_id) VALUES(?)")->execute([$user_id]);

echo "OK";
