<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 處理月份參數
$selected_month = $_GET['month'] ?? date('Y-m');
$start_date = $selected_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// 查詢醫師統計 + 使用率
$sql = "
SELECT 
    u.name AS doctor_name,
    dep.name AS department_name,
    COUNT(DISTINCT a.appointment_id) AS total_appointments,
    COUNT(DISTINCT s.schedule_id) AS total_scheduled_slots,
    ROUND(
        IFNULL(COUNT(DISTINCT a.appointment_id) / NULLIF(COUNT(DISTINCT s.schedule_id) * 6 , 0), 0) * 100,
        2
    ) AS utilization_rate
FROM doctors d
JOIN users u ON d.user_id = u.id
JOIN departments dep ON d.department_id = dep.department_id
LEFT JOIN appointments a 
    ON d.doctor_id = a.doctor_id 
    AND a.appointment_date BETWEEN ? AND ?
LEFT JOIN schedules s 
    ON d.doctor_id = s.doctor_id 
    AND s.schedule_date BETWEEN ? AND ?
    AND s.is_available = 1
GROUP BY d.doctor_id
ORDER BY dep.name, doctor_name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// 整理結果分組顯示
$stats = [];
while ($row = $result->fetch_assoc()) {
    $dept = $row['department_name'];
    $stats[$dept][] = [
        'doctor' => $row['doctor_name'],
        'appointments' => $row['total_appointments'],
        'schedules' => $row['total_scheduled_slots'],
        'utilization' => $row['utilization_rate']
    ];
}
?>

<h2>📅 醫師預約統計報表（<?= $start_date ?> ~ <?= $end_date ?>）</h2>

<form method="get">
    <label>選擇月份：</label>
    <select name="month" onchange="this.form.submit()">
        <?php
        $this_year = date('Y');
        for ($m = 1; $m <= 12; $m++):
            $month_val = $this_year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        ?>
            <option value="<?= $month_val ?>" <?= $month_val == $selected_month ? 'selected' : '' ?>>
                <?= $month_val ?>
            </option>
        <?php endfor; ?>
    </select>
</form>

<?php foreach ($stats as $dept => $rows): ?>
    <h3>科別：<?= htmlspecialchars($dept) ?></h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>醫師</th>
            <th>預約數</th>
            <th>排班時段</th>
            <th>使用率 (%)</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['doctor']) ?></td>
                <td><?= $row['appointments'] ?></td>
                <td><?= $row['schedules'] ?></td>
                <td><?= $row['utilization'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endforeach; ?>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>
