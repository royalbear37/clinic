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
    $duplicate = 0;

    // 取得開始日期的時間戳
    $start_ts = strtotime($week_start);

    echo '<div class="dashboard" style="max-width:600px;margin:40px auto;">';

    // 過濾同一醫師同一天同一班別重複
    $already = [];
    for ($d = 0; $d < 7; $d++) {
        $date = date('Y-m-d', $start_ts + ($d * 86400));
        foreach ($valid_shifts as $shift) {
            if (isset($schedule[$shift][$d])) {
                $key = $doctor_id . '_' . $date . '_' . $shift;
                if (isset($already[$key])) {
                    $duplicate++;
                    continue; // 跳過同一表單重複
                }
                $already[$key] = true;

                // 先檢查資料庫是否已存在
                $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM schedules WHERE doctor_id = ? AND schedule_date = ? AND shift = ?");
                $stmt->bind_param("iss", $doctor_id, $date, $shift);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if ($row['cnt'] > 0) {
                    $duplicate++;
                    continue; // 跳過資料庫重複
                }

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
    echo "<p>✅ 排班完成！成功 $success 筆，失敗 $fail 筆，重複 $duplicate 筆未新增。</p>";
    echo "<p><a href='schedule_manage.php' class='button'>🔙 返回排班管理</a></p>";

    // 查詢本週班表
    $dates = [];
    for ($d = 0; $d < 7; $d++) {
        $dates[] = date('Y-m-d', $start_ts + ($d * 86400));
    }
    $show_schedule = [];
    $in = implode(',', array_fill(0, count($dates), '?'));
    $types = str_repeat('s', count($dates));
    $params = $dates;
    array_unshift($params, $doctor_id);
    $stmt = $conn->prepare("SELECT schedule_date, shift FROM schedules WHERE doctor_id = ? AND schedule_date IN ($in)");
    $stmt->bind_param('i'.$types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $show_schedule[$row['schedule_date']][$row['shift']] = true;
    }

    // 顯示本週班表
    echo "<h3>本週班表總覽</h3>";
    echo "<table border='1' cellpadding='4' style='font-size:0.95em;'>";
    echo "<tr><th>日期</th><th>早班</th><th>中班</th><th>晚班</th></tr>";
    foreach ($dates as $d) {
        echo "<tr><td>$d</td>";
        foreach (['morning','afternoon','evening'] as $shift) {
            echo "<td>" . (isset($show_schedule[$d][$shift]) ? '✅' : '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    echo '</div>';
}

include("../footer.php"); // 加入 footer
?>
