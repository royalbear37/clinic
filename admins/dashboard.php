<?php
session_start();
echo "👩‍💼 歡迎管理員：" . $_SESSION['name'] . "<br>";
?>

<h2>🔧 管理功能選單</h2>
<ul>
    <li><a href="user_management.php">👥 使用者管理</a></li>
    <li><a href="/clinic/schedule/schedule_manage.php">📅 醫師排班設定</a></li>
    <li><a href="/clinic/schedule/schedule_overview.php">🗓️ 排班狀況總覽</a></li>
    <li><a href="/clinic/notifications/notifications_generate.php">📂 產生明日預約通知</a></li>
</ul>
<h2>📊 管理員報表</h2>

<ul>
  <li><a href="/clinic/report/report_appointments.php">📅 預約統計</a></li>
  <li><a href="/clinic/report/report_doctors.php">🩺 醫師看診狀況</a></li>
  <li><a href="/clinic/report/report_patients.php">👤 病患紀錄分析</a></li>
  <li><a href="/clinic/report/report_feedback.php">🌟 滿意度與評價</a></li>
</ul>
<a href="/clinic/users/logout.php">🚪 登出</a>
