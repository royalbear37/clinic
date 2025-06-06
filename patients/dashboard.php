<?php
session_start();
echo "👋 歡迎，" . $_SESSION['name'] . "（病患）<br>";
?>

<a href="/clinic/appointment/new_appointment.php">➕ 掛號預約</a><br>
<a href="/clinic/appointment/my_appointment.php">📅 查看預約紀錄</a><br>
<a href="/clinic/feedback/new_feedback.php">💬 看診回饋</a><br>
<a href="/clinic/patients/profile_edit.php">💬 個人資料修改</a><br>
<a href="/clinic/users/logout.php">🚪 登出</a>
