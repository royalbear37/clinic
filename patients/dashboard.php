<?php
session_start();
echo "👋 歡迎，" . $_SESSION['name'] . "（病患）<br>";
?>

<a href="appointment_new.php">➕ 掛號預約</a><br>
<a href="my_appointments.php">📅 查看預約紀錄</a><br>
<a href="feedback.php">💬 看診回饋</a><br>
<a href="/clinic/users/logout.php">🚪 登出</a>
