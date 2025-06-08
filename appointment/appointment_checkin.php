<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? '';
if (!$appointment_id) {
    echo "❌ 無效的預約 ID。";
    exit();
}

// 驗證預約是否屬於登入病患
$user_id = $_SESSION['uid'];
$stmt = $conn->prepare("SELECT a.appointment_id
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.patient_id
                        WHERE a.appointment_id = ? AND p.user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "❌ 無權操作此預約。";
    exit();
}

// 標記為報到
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE appointments SET status = 'checked_in', checkin_time = ? WHERE appointment_id = ?");
$stmt->bind_param("si", $now, $appointment_id);
// ✅ 改這裡：先執行完 UPDATE 再跳出 PHP 模式
$checkin_success = $stmt->execute();
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>報到結果</title>
    <link rel="stylesheet" href="/clinic/style.css">
    <style>
        .checkin-result {
            max-width: 500px;
            margin: 80px auto;
            padding: 2em;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            font-size: 1.2em;
        }

        .checkin-result a {
            display: inline-block;
            margin-top: 1.5em;
            font-weight: bold;
            color: var(--accent);
        }
    </style>
</head>

<body>
    <div class="checkin-result">
        <?php if ($checkin_success): ?>
            ✅ 報到成功！
        <?php else: ?>
            ❌ 報到失敗：<?= $stmt->error ?>
        <?php endif; ?>
        <br>
        <a href='my_appointment.php'>🔙 返回預約列表</a>
    </div>
</body>

</html>