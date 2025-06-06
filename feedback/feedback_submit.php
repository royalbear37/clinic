<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$appointment_id = $_POST['appointment_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = $_POST['comment'] ?? "";

if (!$appointment_id || !$rating) {
    echo "❌ 請填寫必要欄位（預約與評分）。";
    exit();
}

// 寫入 feedback
$stmt = $conn->prepare("INSERT INTO feedback (appointment_id, rating, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $appointment_id, $rating, $comment);

if ($stmt->execute()) {
    echo "✅ 感謝您的回饋！<br><a href='new_feedback.php'>繼續填寫</a> | <a href='/clinic/patients/dashboard.php'>返回主頁</a>";
} else {
    echo "❌ 寫入失敗：" . $stmt->error;
}
?>