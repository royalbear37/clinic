<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? '';
if (!$appointment_id) {
    echo "❌ 無效的預約 ID。";
    exit();
}

// 驗證預約是否屬於登入病患
$user_id = $_SESSION['uid'];
$stmt = $conn->prepare("SELECT a.appointment_id
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.patient_id
                        WHERE a.appointment_id = ? AND p.user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "❌ 無權操作此預約。";
    exit();
}

// 標記為報到
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE appointments SET status = 'checked-in', checkin_time = ? WHERE appointment_id = ?");
$stmt->bind_param("si", $now, $appointment_id);
if ($stmt->execute()) {
    echo "✅ 報到成功！";
} else {
    echo "❌ 報到失敗：" . $stmt->error;
}

echo "<br><a href='appointment_list.php'>🔙 返回預約列表</a>";
