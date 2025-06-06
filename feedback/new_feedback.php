<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$user_id = $_SESSION['uid'];

// 找對應的 patient_id
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) {
    echo "❌ 找不到病患資料。";
    exit();
}
$patient_id = $row['patient_id'];

// 抓該病患所有已完成但尚未回饋的預約
$sql = "SELECT a.appointment_id, a.appointment_date, a.time_slot, u.name AS doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.id
        WHERE a.patient_id = ? AND a.status = 'completed'
        AND NOT EXISTS (
            SELECT 1 FROM feedback f WHERE f.appointment_id = a.appointment_id
        )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>填寫回饋評價</h2>

<form method="post" action="feedback_submit.php">
    預約：
    <select name="appointment_id" required>
        <?php while ($row = $result->fetch_assoc()): ?>
            <option value="<?= $row['appointment_id'] ?>">
                <?= $row['appointment_date'] ?> <?= $row['time_slot'] ?> - <?= $row['doctor_name'] ?>
            </option>
        <?php endwhile; ?>
    </select><br>

    滿意度評分（1~5）：<input type="number" name="rating" min="1" max="5" required><br>
    留言建議（可選）：<br>
    <textarea name="comment" rows="4" cols="40"></textarea><br>

    <button type="submit">送出回饋</button>
</form>

<form method="get" action="feedback_list.php" style="margin-top: 10px;">
    <button type="submit">📋 查看歷史回饋</button>
</form>


<?php
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    echo "<p><a href='/clinic/{$role}s/dashboard.php'>🔙 回到主頁</a></p>";
}
?>