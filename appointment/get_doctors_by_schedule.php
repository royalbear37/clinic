<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
include("../config/mysql_connect.inc.php");

$dept = $_GET['department_id'] ?? '';
$date = $_GET['appointment_date'] ?? '';
$shift = $_GET['shift'] ?? '';

// 撈出每位醫師平均評分與留言
$sql = "
SELECT 
    d.doctor_id, 
    u.name AS doctor_name, 
    ROUND(AVG(f.rating), 1) AS avg_rating,
    GROUP_CONCAT(f.comment SEPARATOR '||') AS comments
FROM doctors d
JOIN users u ON d.user_id = u.id
JOIN schedules s ON d.doctor_id = s.doctor_id
LEFT JOIN appointments a ON d.doctor_id = a.doctor_id AND a.status = 'completed'
LEFT JOIN feedback f ON f.appointment_id = a.appointment_id
WHERE d.department_id = ?
  AND s.schedule_date = ?
  AND s.shift = ?
  AND s.is_available = 1
GROUP BY d.doctor_id, u.name
";


$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "SQL prepare 錯誤：" . $conn->error]);
    exit;
}

$stmt->bind_param("iss", $dept, $date, $shift);
$stmt->execute();
$res = $stmt->get_result();

$doctors = [];
while ($row = $res->fetch_assoc()) {
    $comments = array_filter(explode('||', $row['comments'] ?? ''));
    $doctors[] = [
        'doctor_id' => $row['doctor_id'],
        'doctor_name' => $row['doctor_name'],
        'avg_rating' => is_null($row['avg_rating']) ? '尚無評價' : $row['avg_rating'],
        'comments' => $comments ?: ['尚無留言']
    ];
}
header("Content-Type: application/json");

echo json_encode($doctors, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
