<?php
session_start();
include("../header.php");
?>
<div class="dashboard">
    <h2>👋 歡迎，<?php echo $_SESSION['name']; ?>（病患）</h2>
    <ul class="dashboard-menu">
        <li><a href="/clinic/appointment/new_appointment.php">➕ 掛號預約</a></li>
        <li><a href="/clinic/appointment/my_appointment.php">📅 查看預約紀錄</a></li>
        <li><a href="/clinic/feedback/new_feedback.php">💬 看診回饋</a></li>
        <li><a href="/clinic/patients/profile_edit.php">📝 個人資料修改</a></li>
        <li><a href="/clinic/users/logout.php">🚪 登出</a></li>
    </ul>
</div>
<?php include("../footer.php"); ?>
