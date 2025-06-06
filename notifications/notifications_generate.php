<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

date_default_timezone_set('Asia/Taipei');
$target_date = date('Y-m-d', strtotime('+1 day'));

// 查詢明日預約資料
$sql = "SELECT a.appointment_id, a.patient_id, u.name AS patient_name, a.time_slot
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.id
        WHERE a.appointment_date = ? AND a.status = 'scheduled'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $target_date);
$stmt->execute();
$result = $stmt->get_result();

$previews = [];
$inserted = 0;
$records = [];

while ($row = $result->fetch_assoc()) {
    $previews[] = $row;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $appointment_id = $row['appointment_id'];
        $patient_id = $row['patient_id'];
        $message = "提醒您：明日（{$target_date}）有預約，時段：{$row['time_slot']}，請準時報到。";

        // 檢查是否已存在
        $check = $conn->prepare("SELECT * FROM notifications WHERE appointment_id = ? AND type = 'email'");
        $check->bind_param("i", $appointment_id);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows === 0) {
            $insert = $conn->prepare("INSERT INTO notifications (appointment_id, patient_id, message, type) VALUES (?, ?, ?, 'email')");
            $insert->bind_param("iis", $appointment_id, $patient_id, $message);
            if ($insert->execute()) {
                $inserted++;
                $records[] = ['name' => $row['patient_name'], 'time' => $row['time_slot'], 'status' => '✅ 已建立'];
            } else {
                $records[] = ['name' => $row['patient_name'], 'time' => $row['time_slot'], 'status' => '❌ 寫入失敗'];
            }
        } else {
            $records[] = ['name' => $row['patient_name'], 'time' => $row['time_slot'], 'status' => '⚠️ 已存在'];
        }
    }
}
?>

<h2>📩 明日預約通知產生（<?= $target_date ?>）</h2>

<?php if (count($previews) === 0): ?>
    <p>📭 明日沒有任何預約，無需發送通知。</p>
<?php else: ?>
    <h3>🔍 預覽：將通知以下病患</h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>病患姓名</th>
            <th>預約時段</th>
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <th>通知狀態</th>
            <?php endif; ?>
        </tr>
        <?php foreach ($previews as $i => $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><?= $row['time_slot'] ?></td>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <td><?= $records[$i]['status'] ?? '—' ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' && count($previews) > 0): ?>
    <form method="post" style="margin-top: 1em;">
        <button type="submit">📩 確定發送通知（寫入資料庫）</button>
    </form>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p>✅ 共新增通知 <?= $inserted ?> 筆。</p>
    <p><a href="notifications_generate.php">🔁 返回重新查詢</a></p>
<?php endif; ?>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>
