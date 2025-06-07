<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 若透過月份選單切換
$selected_month = $_GET['month'] ?? date('Y-m');
$start_date = $selected_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// 查詢資料
$sql = "SELECT d.department_id, dep.name AS department_name, u.name AS doctor_name, COUNT(*) AS count
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.id
        JOIN departments dep ON d.department_id = dep.department_id
        WHERE a.appointment_date BETWEEN ? AND ?
        GROUP BY d.department_id, doctor_name
        ORDER BY dep.name, doctor_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$stats = [];
while ($row = $result->fetch_assoc()) {
    $dept = $row['department_name'];
    $stats[$dept][] = [
        'doctor' => $row['doctor_name'],
        'count' => $row['count']
    ];
}
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:900px;margin:40px auto;">
    <h2 style="text-align:center;">📅 預約統計報表（依月份）</h2>

    <form method="get" style="text-align:center;margin-bottom:1.5em;">
        <label>選擇月份：</label>
        <select name="month" onchange="this.form.submit()">
            <?php
            $current_year = date('Y');
            for ($m = 1; $m <= 12; $m++):
                $month_val = $current_year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            ?>
                <option value="<?= $month_val ?>" <?= $month_val == $selected_month ? 'selected' : '' ?>>
                    <?= $month_val ?>
                </option>
            <?php endfor; ?>
        </select>
    </form>

    <?php foreach ($stats as $dept => $list): ?>
        <h3 style="margin-top:2em;"><?= htmlspecialchars($dept) ?></h3>
        <table style="width:100%;border-collapse:collapse;background:#fffdfa;">
            <tr style="background: #f7f5f2; color: #23272f;">
                <th style="width:60%;">醫師</th>
                <th style="width:40%;">預約數</th>
            </tr>
            <?php foreach ($list as $row): ?>
                <tr style="text-align:center;">
                    <td><?= htmlspecialchars($row['doctor']) ?></td>
                    <td><?= $row['count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>

    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/admins/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
    </div>
</div>
<?php include("../footer.php"); ?>
