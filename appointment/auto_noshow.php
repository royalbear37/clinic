<?php
include("../config/mysql_connect.inc.php");

// 現在時間
date_default_timezone_set('Asia/Taipei'); // 或改為你的伺服器時區
$now = new DateTime();

// 查詢今天以前或今天的未報到預約
$sql = "SELECT appointment_id, appointment_date, time_slot
        FROM appointments
        WHERE status = 'scheduled' AND appointment_date <= CURDATE()";
$result = $conn->query($sql);

$count = 0;

while ($row = $result->fetch_assoc()) {
    [$start, $end] = explode("-", $row['time_slot']); // e.g. "13:00-13:30"
    $appointment_end = new DateTime("{$row['appointment_date']} {$end}:00");

    if ($now >= $appointment_end) {
        $stmt = $conn->prepare("UPDATE appointments SET status = 'no-show' WHERE appointment_id = ?");
        $stmt->bind_param("i", $row['appointment_id']);
        $stmt->execute();
        $count++;
    }
}

echo "✅ 本輪執行，自動標記 no-show：{$count} 筆。\n";
?>
