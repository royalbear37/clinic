<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], ['doctor', 'patient'])) {
    header("Location: /clinic/users/login.php");
    exit();
}

$role = $_SESSION['role'];
$uid = $_SESSION['uid'];
$doctor_id = null;
$patient_id = null;
$time_slot = $_GET['slot'] ?? 'morning';

if ($role === 'doctor') {
    // 醫師登入：查 doctor_id
    $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $doc = $res->fetch_assoc();
    if (!$doc) die("❌ 找不到醫師資料");
    $doctor_id = $doc['doctor_id'];
} elseif ($role === 'patient') {
    // 病患登入：查 patient_id，再查對應 doctor_id
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $pat = $res->fetch_assoc();
    if (!$pat) die("❌ 找不到病患資料");
    $patient_id = $pat['patient_id'];

    // 查今天預約的 doctor_id
    $stmt = $conn->prepare("SELECT doctor_id, time_slot FROM appointments WHERE patient_id = ? AND appointment_date = CURDATE()");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $app = $res->fetch_assoc();
    if (!$app) die("❌ 您今天沒有預約");
    $doctor_id = $app['doctor_id'];
    $time_slot = $app['time_slot'];
}

// 查詢今日該時段的所有病患
$sql = "SELECT a.*, u.name AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.id
        WHERE a.doctor_id = ?
          AND a.appointment_date = CURDATE()
          AND a.time_slot = ?
        ORDER BY a.checkin_time IS NULL, a.checkin_time, a.appointment_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $doctor_id, $time_slot);
$stmt->execute();
$result = $stmt->get_result();

$completed = $waiting = $position = 0;
$next_patient = null;
$patients = [];
$found_self = false;

while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
    if ($row['status'] === 'completed') $completed++;
    if ($row['status'] === 'checked_in') {
        $waiting++;
        if (!$next_patient) $next_patient = $row;
    }
    if ($role === 'patient' && $row['patient_id'] == $patient_id && !$found_self) {
        $position = $completed + $waiting + 1;
        $found_self = true;
    }
}
?>

<h2>👩‍⚕️ <?= htmlspecialchars($time_slot) ?> 看診進度</h2>
<p>✅ 已完成：<?= $completed ?> 人</p>
<p>⚡️ 等候中：<?= $waiting ?> 人</p>
<?php if ($role === 'doctor'): ?>
    <p>🔁 下一位病患：<?= $next_patient['patient_name'] ?? '無' ?></p>
<?php elseif ($role === 'patient'): ?>
    <p>🧰 您目前排第 <?= $position ?> 位</p>
<?php endif; ?>

<h3 style="margin-top:2em;">📅 今日 <?= htmlspecialchars($time_slot) ?> 預約列表</h3>
<table border="1" cellpadding="6">
    <tr>
        <th>病患姓名</th>
        <th>狀態</th>
        <th>報到時間</th>
    </tr>
    <?php foreach ($patients as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['patient_name']) ?></td>
            <td><?= $p['status'] ?></td>
            <td><?= $p['checkin_time'] ?? '—' ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<p style="margin-top:2em;"><a href="/clinic/<?= $role === 'doctor' ? 'doctors' : 'patients' ?>/dashboard.php">🔙 回主頁</a></p>