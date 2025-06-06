<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $src_date = $_POST['source_date'];
    $target_date = $_POST['target_date'];

    $src_start = date("Y-m-d", strtotime($src_date));
    $src_end = date("Y-m-d", strtotime($src_start . " +6 days"));
    $target_start = date("Y-m-d", strtotime($target_date));

    for ($i = 0; $i < 7; $i++) {
        $src_day = date("Y-m-d", strtotime("+{$i} day", strtotime($src_start)));
        $tgt_day = date("Y-m-d", strtotime("+{$i} day", strtotime($target_start)));

        // 取得所有當天排班
        $stmt = $conn->prepare("SELECT doctor_id, start_time, end_time, is_available FROM schedules WHERE schedule_date = ?");
        $stmt->bind_param("s", $src_day);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $stmt_insert = $conn->prepare("INSERT INTO schedules (doctor_id, schedule_date, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("isssi", $row['doctor_id'], $tgt_day, $row['start_time'], $row['end_time'], $row['is_available']);
            $stmt_insert->execute();
        }
    }
    $msg = "✅ 複製完成：{$src_start} ~ {$src_end} → 起始於 {$target_start}";
}
?>

<h2>📆 複製一週排班</h2>

<?php if ($msg) echo "<p style='color:green; font-weight:bold;'>$msg</p>"; ?>

<form method="post">
    來源週任一天：<input type="date" name="source_date" required><br>
    目標週起始日：<input type="date" name="target_date" required><br><br>
    <button type="submit">複製排班</button>
</form>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>