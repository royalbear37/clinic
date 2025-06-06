<?php
session_start();
echo "👨‍⚕️ 歡迎，" . $_SESSION['name'] . " 醫師<br>";
?>

<a href="appointment_list.php">📋 今日病人清單</a><br>
<a href="/clinic/users/logout.php">🚪 登出</a>
