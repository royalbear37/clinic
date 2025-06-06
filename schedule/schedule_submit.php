<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $doctor_id = intval($_POST['doctor_id']);
    $date = $_POST['schedule_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    // 驗證時間順序
    if (strtotime($start) >= strtotime($end)) {
        echo "❌ 結束時間必須大於開始時間。";
        exit();
    }

    // 清除當日排班
    $stmt = $conn->prepare("DELETE FROM schedules WHERE doctor_id = ? AND schedule_date = ?");
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();

    // 寫入
    $stmt = $conn->prepare("INSERT INTO schedules (doctor_id, schedule_date, start_time, end_time, is_available) VALUES (?, ?, ?, ?, true)");
    $stmt->bind_param("isss", $doctor_id, $date, $start, $end);
    if ($stmt->execute()) {
        echo "✅ 排班完成：{$date} {$start}~{$end}";
    } else {
        echo "❌ 錯誤：" . $stmt->error;
    }

    echo "<br><a href='schedule_manage.php'>🔙 返回排班管理</a>";
}
?>