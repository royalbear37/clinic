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

// 班別選項
$shifts = ['morning' => '早班', 'afternoon' => '中班', 'evening' => '晚班'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $leave_date = $_POST['leave_date'] ?? '';
    $shift = $_POST['shift'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (!$leave_date || !$shift) {
        $message = "❌ 請選擇請假日期與班別。";
    } else {
        // 可加上避免重複插入（doctor_id, date, shift）邏輯
        $stmt = $conn->prepare("INSERT INTO schedules (doctor_id, schedule_date, shift, is_available, note)
                                VALUES (?, ?, ?, 0, ?)");
        $stmt->bind_param("isss", $doctor_id, $leave_date, $shift, $reason);
        if ($stmt->execute()) {
            $label = $shifts[$shift] ?? $shift;
            $message = "✅ 已登記請假：{$leave_date}（{$label}）";
        } else {
            $message = "❌ 登記失敗：" . $stmt->error;
        }
    }
}
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:480px;margin:40px auto;">
    <h2 style="text-align:center;">🏖️ 醫師請假登記</h2>

    <?php if ($message): ?>
        <p class="<?= strpos($message, '❌') !== false ? 'error' : 'success' ?>" style="text-align:center;">
            <?= $message ?>
        </p>
    <?php endif; ?>

    <form method="post" style="margin:2em auto;max-width:340px;">
        <div class="form-group">
            <label>請假日期：</label>
            <input type="date" name="leave_date" required>
        </div>
        <div class="form-group">
            <label>請假班別：</label>
            <select name="shift" required>
                <?php foreach ($shifts as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>理由備註（可留空）：</label>
            <textarea name="reason" rows="3" style="width:100%;"></textarea>
        </div>
        <button type="submit" class="button" style="width:100%;">送出請假</button>
    </form>

    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/doctors/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
    </div>
</div>
<?php include("../footer.php"); ?>