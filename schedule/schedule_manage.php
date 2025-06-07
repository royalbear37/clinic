<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 撈出所有醫師
$doctors = $conn->query("SELECT d.doctor_id, u.name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.name");

// 班別 shift 選項
$shifts = [
    'morning' => '早班（09:00～12:00）',
    'afternoon' => '中班（13:00～17:00）',
    'evening' => '晚班（18:00～21:00）'
];
?>

<h2>🗓️ 醫師排班管理（依班別）</h2>

<form method="post" action="schedule_submit.php">
    醫師：
    <select name="doctor_id" required>
        <?php while ($doc = $doctors->fetch_assoc()): ?>
            <option value="<?= $doc['doctor_id'] ?>"><?= htmlspecialchars($doc['name']) ?></option>
        <?php endwhile; ?>
    </select><br>

    日期：<input type="date" name="schedule_date" required><br>

    班別：
    <select name="shift" required>
        <?php foreach ($shifts as $key => $label): ?>
            <option value="<?= $key ?>"><?= $label ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">儲存排班</button>
</form>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>
