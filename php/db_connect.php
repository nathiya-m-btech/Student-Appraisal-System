<?php
// db_connect.php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'student_appraisal';
$DB_USER = 'root';
$DB_PASS = ''; // set your password

function getPDO() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    return new PDO($dsn, $DB_USER, $DB_PASS, $opts);
}
?>
