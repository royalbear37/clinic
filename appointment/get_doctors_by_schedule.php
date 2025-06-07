<?php
include("../config/mysql_connect.inc.php");

$dept = $_GET['department_id'] ?? '';
$date = $_GET['appointment_date'] ?? '';

$sql = "SELECT d.doctor_id, u.name AS doctor_name
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        JOIN schedules s ON d.doctor_id = s.doctor_id
        WHERE d.department_id = ? AND s.schedule_date = ? AND s.is_available = 1
        GROUP BY d.doctor_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $dept, $date);
$stmt->execute();
$res = $stmt->get_result();

$doctors = [];
while ($row = $res->fetch_assoc()) {
    $doctors[] = $row;
}

header("Content-Type: application/json");
echo json_encode($doctors);
