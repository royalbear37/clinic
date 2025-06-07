<?php
session_start();
include("../config/mysql_connect.inc.php");
include("../header.php"); // 加入 header

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $doctor_id = intval($_POST['doctor_id']);
    $week_start = $_POST['week_start'];
    $schedule = $_POST['schedule'] ?? [];
    $is_available = 1;

    $valid_shifts = ['morning', 'afternoon', 'evening'];

    $success = 0;
    $fail = 0;

    // 取得開始日期的時間戳
    $start_ts = strtotime($week_start);

    echo '<div class="dashboard" style="max-width:600px;margin:40px auto;">';

    for ($d = 0; $d < 7; $d++) {
        $date = date('Y-m-d', $start_ts + ($d * 86400));
        foreach ($valid_shifts as $shift) {
            if (isset($schedule[$shift][$d])) {
                // 避免重複：刪除同日同班別
                $stmt = $conn->prepare("DELETE FROM schedules WHERE doctor_id = ? AND schedule_date = ? AND shift = ?");
                $stmt->bind_param("iss", $doctor_id, $date, $shift);
                $stmt->execute();

                // 寫入排班
                $stmt = $conn->prepare("INSERT INTO schedules (doctor_id, schedule_date, shift, is_available) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $doctor_id, $date, $shift, $is_available);
                if ($stmt->execute()) {
                    $success++;
                } else {
                    $fail++;
                }
            }
        }
    }

    echo "<h2>排班結果</h2>";
    echo "<p>✅ 排班完成！成功 $success 筆，失敗 $fail 筆。</p>";
    echo "<p><a href='schedule_manage.php' class='button'>🔙 返回排班管理</a></p>";
    echo '</div>';
}

include("../footer.php"); // 加入 footer
?>
