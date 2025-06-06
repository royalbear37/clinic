<?php
ini_set("display_errors","On");
error_reporting(E_ALL);

//資料庫變數
$db_type = 'mysql';//使用那一種資料庫
$db_host = 'localhost';//主機位置
$db_name = 'mysql';//資料庫名稱(已給定)
$db_user = 'royalbear37';//使用者
$db_password= 'jiuwu1127';//密碼
 
// 資料庫連線
try {
    $db = new PDO($db_type . ':host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_password);
    $db->query('SET NAMES UTF8'); // 資料庫使用 UTF8 編碼
    echo "Connection Success<br/>";
} catch (PDOException $e) {
    echo 'Error!: ' . $e->getMessage() . '<br />';
}
date_default_timezone_set("Asia/Taipei");//設定時區
?>
