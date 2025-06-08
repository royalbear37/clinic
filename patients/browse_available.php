<?php
date_default_timezone_set('Asia/Taipei');
include("../header.php");
include("../config/mysql_connect.inc.php");

// 取得所有科別
$departments = [];
$res = $conn->query("SELECT department_id, name FROM departments ORDER BY department_id");
while ($row = $res->fetch_assoc()) {
    $departments[$row['department_id']] = $row['name'];
}

// 取得選擇的科別
$selected_dept = isset($_GET['department_id']) ? $_GET['department_id'] : '';

// 取得今天日期
$today = date('Y-m-d');

// 取得今天起連續 7 天的日期
$dates = [];
for ($d = 0; $d < 7; $d++) {
    $dates[] = date('Y-m-d', strtotime("+$d days", strtotime($today)));
}

// 取得這 7 天的班表（可依科別過濾）
$sql = "SELECT s.schedule_date, s.shift, d.department_id, d.name AS department, u.name AS doctor_name, doc.doctor_id, s.note
        FROM schedules s
        JOIN doctors doc ON s.doctor_id = doc.doctor_id
        JOIN users u ON doc.user_id = u.id
        JOIN departments d ON doc.department_id = d.department_id
        WHERE s.schedule_date BETWEEN ? AND ?"
        . ($selected_dept ? " AND d.department_id = ?" : "") . "
        ORDER BY s.shift, d.department_id, s.schedule_date, u.name";
$startDate = $dates[0];
$endDate = $dates[6];
$stmt = $conn->prepare($sql);
if ($selected_dept) {
    $stmt->bind_param("ssi", $startDate, $endDate, $selected_dept);
} else {
    $stmt->bind_param("ss", $startDate, $endDate);
}
$stmt->execute();
$result = $stmt->get_result();

// 整理班表資料
$schedule = [];
while ($row = $result->fetch_assoc()) {
    $schedule[$row['shift']][$row['department_id']][$row['schedule_date']][] = [
        'department' => $row['department'],
        'doctor_name' => $row['doctor_name'],
        'doctor_id' => $row['doctor_id'],
        'note' => $row['note']
    ];
}

// 設定每班最大可預約人數（每slot 3人，每班 6 slot）
$slots_per_shift = [
    'morning' => [
        "09:00-09:30", "09:30-10:00", "10:00-10:30", "10:30-11:00", "11:00-11:30", "11:30-12:00"
    ],
    'afternoon' => [
        "13:00-13:30", "13:30-14:00", "14:00-14:30", "14:30-15:00", "15:00-15:30", "15:30-16:00", "16:00-16:30", "16:30-17:00"
    ],
    'evening' => [
        "18:00-18:30", "18:30-19:00", "19:00-19:30", "19:30-20:00", "20:00-20:30", "20:30-21:00"
    ]
];
$limit_per_slot = 3;

// 判斷每班是否額滿
$status = [];
foreach ($schedule as $shift => $depts) {
    foreach ($depts as $dept_id => $datesArr) {
        foreach ($datesArr as $date => $doctors) {
            foreach ($doctors as $doc) {
                $all_slots = $slots_per_shift[$shift];
                $total_slots = count($all_slots);
                $full_count = 0;
                foreach ($all_slots as $slot) {
                    $sql3 = "SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND time_slot = ? AND status != 'cancelled'";
                    $stmt3 = $conn->prepare($sql3);
                    $stmt3->bind_param("iss", $doc['doctor_id'], $date, $slot);
                    $stmt3->execute();
                    $res3 = $stmt3->get_result();
                    $row3 = $res3->fetch_assoc();
                    if ($row3['cnt'] >= $limit_per_slot) {
                        $full_count++;
                    }
                    $stmt3->close();
                }
                // 若所有 slot 都滿，則為預約已滿
                if ($full_count == $total_slots) {
                    $status[$shift][$dept_id][$date][$doc['doctor_id']] = "預約已滿";
                } else {
                    $status[$shift][$dept_id][$date][$doc['doctor_id']] = "尚有空檔";
                }
            }
        }
    }
}

$shift_names = [
    'morning' => '上午 09:00~12:00',
    'afternoon' => '下午 13:00~17:00',
    'evening' => '晚上 18:00~21:00'
];
?>

<div class="dashboard" style="max-width:900px;margin:40px auto;">
    <h2 style="text-align:center;">🔍 查看一周醫師空檔</h2>
    <form method="get" style="margin-bottom:2em;text-align:center;">
        <label style="font-size:1.1em;">科別：</label>
        <select name="department_id" style="font-size:1.1em;padding:8px 16px;border-radius:8px;border:1px solid #ccc;" onchange="this.form.submit()">
            <option value="">全部</option>
            <?php foreach ($departments as $id => $name): ?>
                <option value="<?= $id ?>" <?= $selected_dept == $id ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div style="overflow-x:auto;">
        <table class="table" style="width:100%;border-collapse:collapse;background:#fffdfa;">
            <thead>
                <tr style="background: #f7f5f2; color: #23272f;">
                    <th>班別</th>
                    <th>科別及名稱</th>
                    <?php foreach ($dates as $date): ?>
                        <th><?= $date ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($shift_names as $shift_key => $shift_label): ?>
                <?php
                $has_row = false;
                if (isset($schedule[$shift_key])) {
                    foreach ($schedule[$shift_key] as $dept_id => $datesArr) {
                        $has_row = true;
                        echo "<tr style='text-align:center;'>";
                        echo "<td>" . $shift_label . "</td>";
                        echo "<td>" . htmlspecialchars($departments[$dept_id]) . "</td>";
                        foreach ($dates as $date) {
                            echo "<td>";
                            if (isset($schedule[$shift_key][$dept_id][$date])) {
                                foreach ($schedule[$shift_key][$dept_id][$date] as $doc) {
                                    // intro.php 需要 dep=科別id&doctor_id=醫師id
                                    $intro_url = "/clinic/doctors/intro.php?dep={$dept_id}}";
                                    echo "<b><a href='{$intro_url}' style='color:#337ab7;text-decoration:underline;' target='_blank'>" . htmlspecialchars($doc['doctor_name']) . "</a></b><br>";
                                    if (!empty($doc['note'])) {
                                        echo "<span style='color:#888;font-size:0.9em;'>" . htmlspecialchars($doc['note']) . "</span><br>";
                                    }
                                    $show = $status[$shift_key][$dept_id][$date][$doc['doctor_id']] ?? "尚有空檔";
                                    if ($show === "預約已滿") {
                                        echo "<span style='color:#d33;'>" . $show . "</span><br>";
                                    } else {
                                        echo "<span style='color:#227d3b;'>" . $show . "</span><br>";
                                    }
                                }
                            } else {
                                echo "—";
                            }
                            echo "</td>";
                        }
                        echo "</tr>";
                    }
                }
                if (!$has_row) {
                    echo "<tr><td>{$shift_label}</td><td colspan='" . (count($dates)+1) . "'>—</td></tr>";
                }
                ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/patients/dashboard.php" class="button" style="max-width:200px;">
            🔙 回到主頁
        </a>
    </div>
</div>
<?php include("../footer.php"); ?>