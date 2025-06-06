<?php
session_start(); // 啟用 session，這行要放最前面

// 清除所有 session 變數
session_unset();

// 銷毀 session
session_destroy();

// 跳轉回登入頁面
echo '您已成功登出，將返回登入頁面...';
echo '<meta http-equiv="REFRESH" content="1;url=login.php">';
?>
