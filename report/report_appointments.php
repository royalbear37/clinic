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

<h2>📅 預約統計報表（依月份）</h2>

<form method="get">
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
    <h3>科別：<?= htmlspecialchars($dept) ?></h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>醫師</th>
            <th>預約數</th>
        </tr>
        <?php foreach ($list as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['doctor']) ?></td>
                <td><?= $row['count'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endforeach; ?>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>
