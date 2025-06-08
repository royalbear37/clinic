<?php
session_start();
include("../config/mysql_connect.inc.php");

// ✅ 僅允許醫師登入
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /clinic/users/login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? '';
if (!$appointment_id) {
    die("❌ 缺少 appointment_id");
}

// 查預約和病患資料
$sql = "SELECT a.*, u.name AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.id
        WHERE a.appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();
$appointment = $res->fetch_assoc();

if (!$appointment) {
    die("❌ 找不到預約資料");
}
?>

<h2>📝 為 <?= htmlspecialchars($appointment['patient_name']) ?> 開立處方</h2>
<form method="POST" action="prescription_submit.php">
    <input type="hidden" name="appointment_id" value="<?= $appointment_id ?>">
    <input type="hidden" name="patient_id" value="<?= $appointment['patient_id'] ?>">

    <label>藥品內容：</label><br>
    <textarea name="medication" rows="5" style="width:100%;" required></textarea><br><br>

    <label>備註：</label><br>
    <textarea name="notes" rows="3" style="width:100%;"></textarea><br><br>

    <button type="submit" style="padding:0.5em 1.5em;">✅ 提交處方</button>
</form>