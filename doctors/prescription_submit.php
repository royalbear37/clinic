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

// 將複選藥品組合成字串
$medications = $_POST['medication'] ?? [];
$medication_text = implode(", ", $medications);

// 寫入處方資料
$check_sql = "SELECT * FROM prescriptions WHERE appointment_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $_POST['appointment_id']);
$stmt->execute();
$res = $stmt->get_result();

$medication = implode(",", $_POST['medication'] ?? []);
$notes = $_POST['notes'];

if ($res->num_rows > 0) {
    // UPDATE
    $update = $conn->prepare("UPDATE prescriptions SET medication = ?, notes = ? WHERE appointment_id = ?");
    $update->bind_param("ssi", $medication, $notes, $_POST['appointment_id']);
    $update->execute();
} else {
    // INSERT
    $insert = $conn->prepare("INSERT INTO prescriptions (appointment_id, patient_id, medication, notes) VALUES (?, ?, ?, ?)");
    $insert->bind_param("iiss", $_POST['appointment_id'], $_POST['patient_id'], $medication, $notes);
    $insert->execute();
}
if (($res->num_rows > 0 && $update) || ($res->num_rows == 0 && $insert)) {
    echo "<script>alert('處方已成功提交！'); window.location.href='/clinic/appointment/appointments_upcoming.php';</script>";
} else {
    echo "<script>alert('提交處方時發生錯誤，請稍後再試。'); history.back();</script>";
}
