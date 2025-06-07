<?php
session_start(); // 啟用 session，這行要放最前面
include("../header.php"); // 加在最前面

// 清除所有 session 變數
session_unset();

// 銷毀 session
session_destroy();

// 跳轉回登入頁面
echo '<div class="success">您已成功登出，將返回登入頁面...</div>';
echo '<meta http-equiv="REFRESH" content="1;url=login.php">';

include("../footer.php"); // 加在最後面
?>
