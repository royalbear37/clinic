<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /clinic/users/login.php");
    exit();
}

$appointment_id = $_POST['appointment_id'];
$patient_id = $_POST['patient_id'];
$doctor_id = $_SESSION['uid'];
$notes = $_POST['notes'] ?? '';

// 處理複選藥品（組成文字）
$medications = $_POST['medication'] ?? [];
$medication_text = implode(", ", $medications);

// 若沒選藥品，拒絕寫入
if (empty($medication_text)) {
    die("<script>alert('❌ 請至少選擇一項藥品'); history.back();</script>");
}

// 檢查是否已有該預約的處方
$check_sql = "SELECT * FROM prescriptions WHERE appointment_id = ?";
$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("i", $appointment_id);
$stmt_check->execute();
$res = $stmt_check->get_result();

if ($res->num_rows > 0) {
    // 🔄 已存在：更新處方
    $stmt_update = $conn->prepare("UPDATE prescriptions SET medication = ?, notes = ? WHERE appointment_id = ?");
    $stmt_update->bind_param("ssi", $medication_text, $notes, $appointment_id);
    $success = $stmt_update->execute();
} else {
    // ➕ 不存在：新增處方
    $stmt_insert = $conn->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, medication, notes)
                                   VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_insert) {
        die("❌ prepare 失敗：" . $conn->error);
    }
    $stmt_insert->bind_param("iiiss", $appointment_id, $doctor_id, $patient_id, $medication_text, $notes);
    $success = $stmt_insert->execute();
}

// ✅ 成功與否通知
if ($success) {
    echo "<script>alert('✅ 處方已成功提交！'); window.location.href='/clinic/appointment/appointments_upcoming.php';</script>";
} else {
    echo "<script>alert('❌ 提交處方時發生錯誤，請稍後再試。'); history.back();</script>";
}
