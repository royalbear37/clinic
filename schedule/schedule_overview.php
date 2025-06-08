<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'patient'])) {
    header("Location: /clinic/users/login.php");
    exit();
}

$role = $_SESSION['role'];
$can_delete = ($role === 'admin');

$date = $_GET['date'] ?? date('Y-m-d');
$department_id = $_GET['department_id'] ?? ''; // 新增

// 取得所有科別
$departments = $conn->query("SELECT * FROM departments");

// 計算本週日期
$base_date = date('Y-m-d', strtotime('sunday last week', strtotime($date)));
if (date('w', strtotime($date)) == 0) {
    $base_date = $date;
}
$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = date('Y-m-d', strtotime("+{$i} day", strtotime($base_date)));
}

$shift_map = ['morning' => '早班', 'afternoon' => '中班', 'evening' => '晚班'];
$shifts = ['morning', 'afternoon', 'evening'];

// 取得所有醫師（可依科別過濾）
$doctor_sql = "SELECT d.doctor_id, u.name FROM doctors d JOIN users u ON d.user_id = u.id";
if ($department_id) {
    $doctor_sql .= " WHERE d.department_id = ?";
    $stmt = $conn->prepare($doctor_sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $doctors = $stmt->get_result();
} else {
    $doctor_sql .= " ORDER BY u.name";
    $doctors = $conn->query($doctor_sql);
}

// 取得該週所有班表
$schedule_data = [];
$schedule_rs = $conn->query(
    "SELECT s.schedule_date, s.shift, u.name 
     FROM schedules s 
     JOIN doctors d ON s.doctor_id = d.doctor_id 
     JOIN users u ON d.user_id = u.id
     WHERE s.schedule_date BETWEEN '{$days[0]}' AND '{$days[6]}'"
    . ($department_id ? " AND d.department_id = " . intval($department_id) : "")
);
while ($row = $schedule_rs->fetch_assoc()) {
    $schedule_data[$row['shift']][$row['schedule_date']][] = $row['name'];
}
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:1100px;margin:40px auto;">
    <h2 style="text-align:center;">📆 醫師班表（<?= $days[0] ?> ~ <?= $days[6] ?>）</h2>
    <form method="get" style="text-align:center;margin-bottom:1.5em;">
        <input type="date" name="date" value="<?= $date ?>" required>
        <select name="department_id" onchange="this.form.submit()" style="margin-left:1em;">
            <option value="">全部科別</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['department_id'] ?>" <?= $department_id == $dept['department_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button">切換週</button>
    </form>

    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;background:#fffdfa; border:2px solid #bbb;">
        <tr style="background: #f7f5f2; color: #23272f;">
            <th style="border:1px solid #bbb;padding:8px;">班別＼日期</th>
            <?php foreach ($days as $d) echo "<th style='border:1px solid #bbb;padding:8px;'>{$d}</th>"; ?>
        </tr>
        <?php foreach ($shifts as $shift): ?>
            <tr style="text-align:center;">
                <td style="font-weight:bold;border:1px solid #bbb;padding:8px;"><?= $shift_map[$shift] ?></td>
                <?php foreach ($days as $d): ?>
                    <td style="border:1px solid #bbb;padding:8px;">
                        <?php
                        if (!empty($schedule_data[$shift][$d])) {
                            echo implode('<br>', array_map('htmlspecialchars', $schedule_data[$shift][$d]));
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>

    <div style="text-align:center; margin-top:2em;">
        <?php
        switch ($role) {
            case 'admin':
                echo "<a href='/clinic/admins/dashboard.php' class='button' style='max-width:200px;'>🔙 回到主頁</a>";
                break;
            case 'doctor':
                echo "<a href='/clinic/doctors/dashboard.php' class='button' style='max-width:200px;'>🔙 回到主頁</a>";
                break;
            case 'patient':
                echo "<a href='/clinic/patients/dashboard.php' class='button' style='max-width:200px;'>🔙 回到主頁</a>";
                break;
        }
        ?>
    </div>
</div>
<?php include("../footer.php"); ?>
