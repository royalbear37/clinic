<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 報表類型
$type = $_GET['type'] ?? 'daily';

// 日期選擇
if ($type === 'weekly') {
    $selected_week = $_GET['week'] ?? date('o-\WW');
    // 取得本週的週一和週日
    $dt = new DateTime();
    $dt->setISODate(substr($selected_week, 0, 4), substr($selected_week, 6, 2));
    $start_date = $dt->format('Y-m-d');
    $dt->modify('+6 days');
    $end_date = $dt->format('Y-m-d');
} else {
    $selected_date = $_GET['date'] ?? date('Y-m-d');
    $start_date = $end_date = $selected_date;
}

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
    <h2 style="text-align:center;">📅 預約統計報表（每日/每週）</h2>

    <div style="background:#fff;padding:32px 32px 24px 32px;border-radius:16px;box-shadow:0 2px 12px #eee;max-width:500px;margin:0 auto 2em auto;">
        <form method="get" style="display:flex;flex-direction:column;align-items:center;gap:18px;">
            <div style="display:flex;align-items:center;gap:12px;width:100%;">
                <label style="white-space:nowrap;">報表類型：</label>
                <select name="type" onchange="this.form.submit()" style="flex:1;padding:8px 12px;">
                    <option value="daily" <?= $type === 'daily' ? 'selected' : '' ?>>每日</option>
                    <option value="weekly" <?= $type === 'weekly' ? 'selected' : '' ?>>每週</option>
                </select>
            </div>
            <div style="display:flex;align-items:center;gap:12px;width:100%;">
                <?php if ($type === 'weekly'): ?>
                    <label style="white-space:nowrap;">選擇週：</label>
                    <input type="week" name="week" value="<?= htmlspecialchars($selected_week) ?>" onchange="this.form.submit()" style="flex:1;padding:8px 12px;">
                <?php else: ?>
                    <label style="white-space:nowrap;">選擇日期：</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()" style="flex:1;padding:8px 12px;">
                <?php endif; ?>
            </div>
        </form>
    </div>

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
