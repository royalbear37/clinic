<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$user_id = $_SESSION['uid'];  // users.id

// 先找出該使用者對應的病患 ID
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patientRow = $result->fetch_assoc();

if (!$patientRow) {
    echo "❌ 錯誤：找不到對應的病患資料。";
    exit();
}

$patient_id = $patientRow['patient_id'];

// 接收預約資料
$doctor_id = $_POST['doctor_id'] ?? null;
$appointment_date = $_POST['appointment_date'] ?? null;
$time_slot = $_POST['time_slot'] ?? null;
$service_type = $_POST['service_type'] ?? null;

if (!$doctor_id || !$appointment_date || !$time_slot || !$service_type) {
    echo "❌ 請完整填寫所有欄位。";
    exit();
}

// 設定每時段上限 3 人
$limit = 3;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments 
                        WHERE doctor_id = ? AND appointment_date = ? AND time_slot = ?");
$stmt->bind_param("iss", $doctor_id, $appointment_date, $time_slot);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] >= $limit) {
    echo "❌ 該時段已達人數上限，請選擇其他時段。";
    echo "<br><a href='new_appointment.php'>返回預約頁面</a>";
    exit();
}

// 寫入資料
$insert = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, service_type, status) 
                          VALUES (?, ?, ?, ?, ?, 'scheduled')");
$insert->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $time_slot, $service_type);

if ($insert->execute()) {
    echo "✅ 預約成功！<br><a href='new_appointment.php'>再預約一筆</a> | <a href='my_appointment.php'>查看預約紀錄</a>";
} else {
    echo "❌ 寫入失敗：" . $insert->error;
}
?>

<?php
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    echo "<p><a href='/clinic/{$role}s/dashboard.php'>🔙 回到主頁</a></p>";
}
?>
