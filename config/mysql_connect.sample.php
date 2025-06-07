<?php
$host = "localhost";
$username = "root";
$password = "abc060744";  // 如果沒有密碼就留空字串 ""
$database = "clinic_db";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("資料庫連線失敗：" . $conn->connect_error);
}
?>