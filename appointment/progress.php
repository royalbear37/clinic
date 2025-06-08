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

// 取得目前選取的時段
$selected_time_slot = $_GET['time_slot'] ?? '';

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

    // 查今天預約的 doctor_id 和時段
    $stmt = $conn->prepare("SELECT doctor_id, time_slot FROM appointments WHERE patient_id = ? AND appointment_date = CURDATE()");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $app = $res->fetch_assoc();
    if (!$app) die("❌ 您今天沒有預約");
    $doctor_id = $app['doctor_id'];
    if (!$selected_time_slot) $selected_time_slot = $app['time_slot'];
}

// 查詢今天該醫師所有有預約的時段
$time_slots = [];
$stmt = $conn->prepare("SELECT DISTINCT time_slot FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE() ORDER BY time_slot");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $time_slots[] = $row['time_slot'];
}
if (!$selected_time_slot && count($time_slots)) {
    $selected_time_slot = $time_slots[0];
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
$stmt->bind_param("is", $doctor_id, $selected_time_slot);
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

<link rel="stylesheet" href="/clinic/style.css">

<div class="dashboard" style="max-width:600px;margin:40px auto;">
    <h2 style="text-align:center;">👩‍⚕️ 看診進度</h2>
    <!-- 新增時段選單 -->
    <form method="get" style="text-align:center;margin-bottom:1.5em;">
        <label for="time_slot">選擇時段：</label>
        <select name="time_slot" id="time_slot" onchange="this.form.submit()" style="padding:6px 16px;">
            <?php foreach ($time_slots as $slot): ?>
                <option value="<?= htmlspecialchars($slot) ?>" <?= $selected_time_slot == $slot ? 'selected' : '' ?>>
                    <?= htmlspecialchars($slot) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <div style="display:flex;justify-content:space-between;max-width:400px;margin:0 auto 1.5em auto;">
        <span>✅ 已完成：<?= $completed ?> 人</span>
        <span>⚡️ 等候中：<?= $waiting ?> 人</span>
    </div>
    <?php if ($role === 'doctor'): ?>
        <p style="text-align:center;">🔁 下一位病患：<strong><?= $next_patient['patient_name'] ?? '無' ?></strong></p>
    <?php elseif ($role === 'patient'): ?>
        <?php
        $my_status = null;
        foreach ($patients as $p) {
            if ($p['patient_id'] == $patient_id) {
                $my_status = $p['status'];
                break;
            }
        }
        ?>
        <?php if ($my_status === 'completed'): ?>
            <p style="text-align:center;">
                <span style="font-size:1.25em; font-weight:700; color:#227d3b;">✅ 您已完成今日看診</span>
            </p>
        <?php else: ?>
            <p style="text-align:center;">
                <span style="font-size:1.25em; font-weight:700;"> 您目前排第</span>
                <strong style="color:#2b6cb0; font-size:1.5em; font-weight:bold;"><?= $position ?></strong>
                <span style="font-size:1.25em; font-weight:700;">位</span>
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <h3 style="margin-top:2em;text-align:center;">📅 今日 <?= htmlspecialchars($selected_time_slot) ?> 預約列表</h3>
    <table class="progress-table" style="margin:0 auto;">
        <tr>
            <th>病患姓名</th>
            <th>狀態</th>
            <th>報到時間</th>
        </tr>
        <?php foreach ($patients as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['patient_name']) ?></td>
                <td>
                    <?php
                    if ($p['status'] === 'scheduled') echo '<span style="color:#227d3b;">預約中</span>';
                    elseif ($p['status'] === 'checked_in') echo '<span style="color:#2b6cb0;">已報到</span>';
                    elseif ($p['status'] === 'completed') echo '<span style="color:#555;">已完成</span>';
                    elseif ($p['status'] === 'no-show') echo '<span style="color:#a94442;">未到</span>';
                    elseif ($p['status'] === 'cancelled') echo '<span style="color:#a94442;">已取消</span>';
                    else echo htmlspecialchars($p['status']);
                    ?>
                </td>
                <td><?= $p['checkin_time'] ?? '—' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div style="text-align:center;margin-top:2em;">
        <a href="/clinic/<?= $role === 'doctor' ? 'doctors' : 'patients' ?>/dashboard.php" class="button" style="max-width:200px;">🔙 回主頁</a>
    </div>
</div>

<style>
    .progress-table {
        border-collapse: collapse;
        width: 100%;
        background: #fffdfa;
        margin-top: 1em;
        box-shadow: 0 2px 10px rgba(34, 35, 43, 0.06);
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-table th,
    .progress-table td {
        border: 1px solid #e2ded6;
        padding: 0.7em 1.2em;
        text-align: center;
        font-size: 1em;
    }

    .progress-table th {
        background: #f5f2ee;
        color: #2d323a;
        font-weight: 600;
    }

    .progress-table tr:nth-child(even) {
        background: #faf8f4;
    }
</style>