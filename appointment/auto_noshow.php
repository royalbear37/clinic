<?php
include("../config/mysql_connect.inc.php");

date_default_timezone_set('Asia/Taipei');
$now = new DateTime();

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
?>

<?php include("../header.php"); ?>
<link rel="stylesheet" href="/clinic/style.css">

<div class="dashboard" style="max-width:600px;margin:40px auto;">
    <h2 style="text-align:center;">⏰ 自動標記未到（no-show）</h2>
    <div style="text-align:center;font-size:1.2em;margin-top:30px;">
        ✅ 本輪執行，自動標記 no-show：<?= $count ?> 筆。
    </div>
</div>

<?php include("../footer.php"); ?>
