<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../clinic/users/login.php");
    exit();
}

// 撈出所有啟用中的醫師
$doctor_sql = "SELECT doctor_id, users.name AS doctor_name, departments.name AS dept 
               FROM doctors 
               JOIN users ON doctors.user_id = users.id 
               JOIN departments ON doctors.department_id = departments.department_id 
               WHERE is_active = 'yes'";
$doctors = $conn->query($doctor_sql);

// 建立時間區間（09:00~17:30，每 30 分鐘）
function generateTimeSlots($start = "09:00", $end = "17:30", $interval = 30) {
    $slots = [];
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    while ($startTime < $endTime) {
        $nextTime = $startTime + $interval * 60;
        $slots[] = date("H:i", $startTime) . "-" . date("H:i", $nextTime);
        $startTime = $nextTime;
    }
    return $slots;
}
$time_slots = generateTimeSlots();
?>

<h2>新增預約</h2>

<form method="post" action="appointment_submit.php">
    醫師：
    <select name="doctor_id" required>
        <?php while ($row = $doctors->fetch_assoc()): ?>
            <option value="<?= $row['doctor_id'] ?>">
                <?= $row['dept'] ?> - <?= $row['doctor_name'] ?>
            </option>
        <?php endwhile; ?>
    </select><br>

    預約日期：<input type="date" name="appointment_date" required><br>

    時段：
    <select name="time_slot" required>
        <?php foreach ($time_slots as $slot): ?>
            <option value="<?= $slot ?>"><?= $slot ?></option>
        <?php endforeach; ?>
    </select><br>

    服務類型：
    <select name="service_type" required>
        <option value="consultation">一般諮詢</option>
        <option value="checkup">健檢</option>
        <option value="follow_up">回診</option>
        <option value="emergency">急診</option>
    </select><br>

    <button type="submit">送出預約</button>
</form>