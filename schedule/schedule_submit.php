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
    $shift = $_POST['shift'];
    $is_available = 1; // 表示這筆是排班（不是請假）

    $valid_shifts = ['morning', 'afternoon', 'evening'];
    if (!in_array($shift, $valid_shifts)) {
        echo "❌ 錯誤：無效的班別。";
        exit();
    }

    // 避免重複：刪除同日同班別
    $stmt = $conn->prepare("DELETE FROM schedules WHERE doctor_id = ? AND schedule_date = ? AND shift = ?");
    $stmt->bind_param("iss", $doctor_id, $date, $shift);
    $stmt->execute();

    // 寫入排班
    $stmt = $conn->prepare("INSERT INTO schedules (doctor_id, schedule_date, shift, is_available) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $doctor_id, $date, $shift, $is_available);
    if ($stmt->execute()) {
        echo "✅ 排班完成：{$date}（{$shift} shift）";
    } else {
        echo "❌ 錯誤：" . $stmt->error;
    }

    echo "<br><a href='schedule_manage.php'>🔙 返回排班管理</a>";
}
?>
