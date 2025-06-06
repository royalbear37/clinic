<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /clinic/users/login.php");
    exit();
}

$uid = $_SESSION['uid'];

// 查 doctor_id
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$doctor = $res->fetch_assoc();

if (!$doctor) {
    echo "❌ 找不到醫師資料。";
    exit();
}
$doctor_id = $doctor['doctor_id'];
$message = "";

// 整點時段：09:00 到 18:00
function generateHourlyOptions($start = 9, $end = 18) {
    $slots = [];
    for ($h = $start; $h <= $end; $h++) {
        $time = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00";
        $slots[] = $time;
    }
    return $slots;
}
$hour_options = generateHourlyOptions();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $leave_date = $_POST['leave_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (!$leave_date || !$start_time || !$end_time) {
        $message = "❌ 請完整選擇日期與請假時段。";
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $message = "❌ 開始時間不可晚於或等於結束時間。";
    } else {
        $stmt = $conn->prepare("INSERT INTO schedules (doctor_id, schedule_date, start_time, end_time, is_available, note)
                                VALUES (?, ?, ?, ?, 0, ?)");
        $stmt->bind_param("issss", $doctor_id, $leave_date, $start_time, $end_time, $reason);
        if ($stmt->execute()) {
            $message = "✅ 已登記請假：{$leave_date} {$start_time}~{$end_time}";
        } else {
            $message = "❌ 登記失敗：" . $stmt->error;
        }
    }
}
?>

<h2>🏖️ 醫師請假登記</h2>

<?php if ($message): ?>
    <p style="color:<?= strpos($message, '❌') !== false ? 'red' : 'green' ?>"><?= $message ?></p>
<?php endif; ?>

<form method="post">
    請假日期：<input type="date" name="leave_date" required><br>
    開始時間：
    <select name="start_time" required>
        <?php foreach ($hour_options as $time): ?>
            <option value="<?= $time ?>"><?= $time ?></option>
        <?php endforeach; ?>
    </select><br>
    結束時間：
    <select name="end_time" required>
        <?php foreach ($hour_options as $time): ?>
            <option value="<?= $time ?>"><?= $time ?></option>
        <?php endforeach; ?>
    </select><br>
    理由備註（可留空）：<br>
    <textarea name="reason" rows="3" cols="40"></textarea><br>
    <button type="submit">送出請假</button>
</form>

<p><a href="/clinic/doctors/dashboard.php">🔙 回到主頁</a></p>