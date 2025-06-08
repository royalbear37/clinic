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
$stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, medication, notes)
                        VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiss", $appointment_id, $doctor_id, $patient_id, $medication_text, $notes);
$stmt->execute();

header("Location: dashboard.php?msg=prescribed");
