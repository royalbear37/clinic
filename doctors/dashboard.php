<?php
session_start();
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /clinic/users/login.php");
    exit();
}
include("../header.php");
?>
<div class="dashboard">
    <h2>👨‍⚕️ 醫師主頁</h2>
    <p>歡迎，<?= $_SESSION['name'] ?> 醫師！</p>
    <ul class="dashboard-menu">
        <li><a href="/clinic/appointment/appointments_upcoming.php">📅 查看未來預約紀錄</a></li>
        <li><a href="/clinic/feedback/feedback_list.php">📝 查看病患回饋</a></li>
        <li><a href="/clinic/doctors/leave_apply.php">🏖️ 請假申請</a></li>
        <li><a href="/clinic/schedule/schedule_overview.php">🗓️ 排班狀況總覽</a></li>
        <li><a href="/clinic/appointment/progress.php">📋 查看今日看診進度</a></li>
        <li><a href="/clinic/users/logout.php">🚪 登出</a></li>
    </ul>
</div>
<?php include("../footer.php"); ?>