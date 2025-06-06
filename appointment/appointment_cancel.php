<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$user_id = $_SESSION['uid'];
$appointment_id = $_GET['id'] ?? null;

// 查該病患的 patient_id
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if (!$row) {
    echo "❌ 找不到病患資料。";
    exit();
}
$patient_id = $row['patient_id'];

// 檢查是否為此病患的預約
$check = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND patient_id = ?");
$check->bind_param("ii", $appointment_id, $patient_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows === 0) {
    echo "⚠️ 你無權取消這筆預約。";
    exit();
}

// 更新狀態為 cancelled
$update = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
$update->bind_param("i", $appointment_id);

if ($update->execute()) {
    echo "✅ 預約已成功取消。<br><a href='my_appointment.php'>返回預約紀錄</a>";
} else {
    echo "❌ 無法取消預約：" . $update->error;
}
?>