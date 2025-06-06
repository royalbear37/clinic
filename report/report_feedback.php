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

// 查詢回饋資料
$sql = "
SELECT d.doctor_id, u.name AS doctor_name, dept.name AS department_name,
       COUNT(f.feedback_id) AS total_feedback,
       ROUND(AVG(f.rating), 2) AS avg_rating
FROM feedback f
JOIN appointments a ON f.appointment_id = a.appointment_id
JOIN doctors d ON a.doctor_id = d.doctor_id
JOIN users u ON d.user_id = u.id
JOIN departments dept ON d.department_id = dept.department_id
WHERE f.submitted_at BETWEEN ? AND ?
GROUP BY d.doctor_id
ORDER BY avg_rating DESC, total_feedback DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>⭐ 病患回饋報表（<?= $start_date ?> ~ <?= $end_date ?>）</h2>

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

<table border="1" cellpadding="6">
    <tr>
        <th>醫師</th>
        <th>科別</th>
        <th>回饋數</th>
        <th>平均評分</th>
        <th>查看留言</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['doctor_name']) ?></td>
            <td><?= htmlspecialchars($row['department_name']) ?></td>
            <td><?= $row['total_feedback'] ?></td>
            <td><?= $row['avg_rating'] ?? '—' ?></td>
            <td>
                <a href="report_feedback_detail.php?doctor_id=<?= $row['doctor_id'] ?>&month=<?= $selected_month ?>">📝</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>
