<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 報表類型
$type = $_GET['type'] ?? 'monthly';

// 日期選擇
if ($type === 'weekly') {
    $selected_week = $_GET['week'] ?? date('o-\WW');
    $dt = new DateTime();
    $dt->setISODate(substr($selected_week, 0, 4), substr($selected_week, 6, 2));
    $start_date = $dt->format('Y-m-d');
    $dt->modify('+6 days');
    $end_date = $dt->format('Y-m-d');
    $period_label = $start_date . " ~ " . $end_date;
} elseif ($type === 'daily') {
    $selected_date = $_GET['date'] ?? date('Y-m-d');
    $start_date = $end_date = $selected_date;
    $period_label = $selected_date;
} else { // monthly
    $selected_month = $_GET['month'] ?? date('Y-m');
    $start_date = $selected_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $period_label = $start_date . " ~ " . $end_date;
}

// 查詢醫師統計 + 使用率（分母為該醫師該區間所有上班時段數*3*2，並顯示可預約時段總數與可預約人數）
$sql = "
SELECT 
    u.name AS doctor_name,
    dep.name AS department_name,
    COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.appointment_id END) AS completed_appointments,
    COUNT(DISTINCT s.schedule_id) AS total_shifts,
    (COUNT(DISTINCT s.schedule_id) * 6 * 3) AS total_slots,
    ROUND(
        IFNULL(COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.appointment_id END) / NULLIF((COUNT(DISTINCT s.schedule_id) * 6 * 3), 0), 0) * 100,
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
        'appointments' => $row['completed_appointments'],
        'shifts' => $row['total_shifts'],
        'slots' => $row['total_slots'],
        'utilization' => $row['utilization_rate']
    ];
}
include("../header.php");
?>

<div class="dashboard" style="max-width:900px;margin:40px auto;">
    <h2 style="text-align:center;">📅 醫師預約統計報表<br><span style="font-size:0.7em;color:#888;"><?= $period_label ?></span></h2>

    <div style="background:#fff;padding:32px 32px 24px 32px;border-radius:16px;box-shadow:0 2px 12px #eee;max-width:500px;margin:0 auto 2em auto;">
        <form method="get" style="display:flex;flex-direction:column;align-items:center;gap:18px;">
            <div style="display:flex;align-items:center;gap:12px;width:100%;">
                <label style="white-space:nowrap;">報表類型：</label>
                <select name="type" onchange="this.form.submit()" style="flex:1;padding:8px 12px;">
                    <option value="monthly" <?= $type === 'monthly' ? 'selected' : '' ?>>每月</option>
                    <option value="weekly" <?= $type === 'weekly' ? 'selected' : '' ?>>每週</option>
                    <option value="daily" <?= $type === 'daily' ? 'selected' : '' ?>>每日</option>
                </select>
            </div>
            <div style="display:flex;align-items:center;gap:12px;width:100%;">
                <?php if ($type === 'weekly'): ?>
                    <label style="white-space:nowrap;">選擇週：</label>
                    <input type="week" name="week" value="<?= htmlspecialchars($selected_week) ?>" onchange="this.form.submit()" style="flex:1;padding:8px 12px;">
                <?php elseif ($type === 'daily'): ?>
                    <label style="white-space:nowrap;">選擇日期：</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()" style="flex:1;padding:8px 12px;">
                <?php else: ?>
                    <label style="white-space:nowrap;">選擇月份：</label>
                    <select name="month" onchange="this.form.submit()" style="flex:1;padding:8px 12px;">
                        <?php
                        $this_year = date('Y');
                        for ($m = 1; $m <= 12; $m++):
                            $month_val = $this_year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                        ?>
                            <option value="<?= $month_val ?>" <?= $month_val == ($selected_month ?? date('Y-m')) ? 'selected' : '' ?>>
                                <?= $month_val ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php foreach ($stats as $dept => $rows): ?>
        <h3 style="margin-top:2em;"><?= htmlspecialchars($dept) ?></h3>
        <table class="table" style="width:100%;border-collapse:collapse;background:#fffdfa;">
            <thead>
                <tr style="background: #f7f5f2; color: #23272f;">
                    <th>醫師</th>
                    <th>完成預約數</th>
                    <th>可預約時段總數</th>
                    <th>可預約人數</th>
                    <th>使用率 (%)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr style="text-align:center;">
                    <td><?= htmlspecialchars($row['doctor']) ?></td>
                    <td><?= $row['appointments'] ?></td>
                    <td><?= $row['shifts'] ?></td>
                    <td><?= $row['slots'] ?></td>
                    <td><?= $row['utilization'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/admins/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
    </div>
</div>
<?php include("../footer.php"); ?>
