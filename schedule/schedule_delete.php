<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

$schedule_id = $_GET['schedule_id'] ?? 0;
$redirect_date = $_GET['date'] ?? date('Y-m-d');

if ($schedule_id) {
    $stmt = $conn->prepare("DELETE FROM schedules WHERE schedule_id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
}

header("Location: schedule_overview.php?date=" . urlencode($redirect_date));
exit();
?>