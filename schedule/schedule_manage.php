<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 撈出所有醫師
$doctors = $conn->query("SELECT d.doctor_id, u.name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.name");

// 整點時間選項
function getHourOptions() {
    $options = [];
    for ($h = 9; $h <= 17; $h++) {
        $time = str_pad($h, 2, "0", STR_PAD_LEFT) . ":00";
        $options[] = $time;
    }
    return $options;
}
$hours = getHourOptions();
?>

<h2>🗓️ 醫師排班管理</h2>

<form method="post" action="schedule_submit.php">
    醫師：
    <select name="doctor_id" required>
        <?php while ($doc = $doctors->fetch_assoc()): ?>
            <option value="<?= $doc['doctor_id'] ?>"><?= htmlspecialchars($doc['name']) ?></option>
        <?php endwhile; ?>
    </select><br>

    日期：<input type="date" name="schedule_date" required><br>

    開始時間：
    <select name="start_time" required>
        <?php foreach ($hours as $h): ?>
            <option value="<?= $h ?>"><?= $h ?></option>
        <?php endforeach; ?>
    </select>

    結束時間：
    <select name="end_time" required>
        <?php foreach ($hours as $h): ?>
            <option value="<?= $h ?>"><?= $h ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">儲存排班</button>
</form>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>