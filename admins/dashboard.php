<?php
session_start();
echo "👩‍💼 歡迎管理員：" . $_SESSION['name'] . "<br>";
?>

<a href="manage_users.php">👥 使用者管理</a><br>
<a href="manage_schedule.php">🗓 醫師排班</a><br>
<a href="reports.php">📊 系統統計</a><br>
<a href="/clinic/users/logout.php">🚪 登出</a>
