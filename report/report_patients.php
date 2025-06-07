<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 取得選取月份
$selected_month = $_GET['month'] ?? date('Y-m');
$start_date = $selected_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// 查詢病患預約與回饋統計
$sql = "
SELECT u.name AS patient_name,
       COUNT(a.appointment_id) AS total_appointments,
       SUM(a.status = 'completed') AS completed_count,
       SUM(a.status = 'no-show') AS no_show_count,
       ROUND(AVG(f.rating), 2) AS avg_rating
FROM patients p
JOIN users u ON p.user_id = u.id
LEFT JOIN appointments a ON p.patient_id = a.patient_id
    AND a.appointment_date BETWEEN ? AND ?
LEFT JOIN feedback f ON a.appointment_id = f.appointment_id
GROUP BY u.name
ORDER BY total_appointments DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:900px;margin:40px auto;">
    <h2 style="text-align:center;">👥 病患預約使用報表（<?= $start_date ?> ~ <?= $end_date ?>）</h2>

    <form method="get" style="text-align:center;margin-bottom:1.5em;">
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

    <table style="width:100%;border-collapse:collapse;background:#fffdfa;">
        <tr style="background: #f7f5f2; color: #23272f;">
            <th>病患姓名</th>
            <th>預約總數</th>
            <th>完成看診</th>
            <th>未到</th>
            <th>平均滿意度</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr style="text-align:center;">
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><?= $row['total_appointments'] ?? 0 ?></td>
                <td><?= $row['completed_count'] ?? 0 ?></td>
                <td><?= $row['no_show_count'] ?? 0 ?></td>
                <td><?= $row['avg_rating'] ?? '—' ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/admins/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
    </div>
</div>
<?php include("../footer.php"); ?>
