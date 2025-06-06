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
$base_date = date('Y-m-d', strtotime('sunday last week', strtotime($date)));
if (date('w', strtotime($date)) == 0) {
    $base_date = $date;
}

$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = date('Y-m-d', strtotime("+{$i} day", strtotime($base_date)));
}

$doctors = $conn->query("SELECT d.doctor_id, u.name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.name");

echo "<h2>📆 醫師班表（{$days[0]} ~ {$days[6]}）</h2>";
echo "<form method='get'><input type='date' name='date' value='{$date}' required><button type='submit'>切換週</button></form><br>";
?>

<?php if ($_SESSION['role'] === 'admin'): ?>
    <button onclick="document.getElementById('copy_form').style.display='block'; this.style.display='none';">
        ➕ 複製班表
    </button>

    <div id="copy_form" style="display:none; margin-top: 1em;">
        <form method="post" action="schedule_copy_week.php">
            複製來源週（任意一天）：
            <input type="date" name="source_date" required>

            ➡️ 複製到目標週（任意一天）：
            <input type="date" name="target_date" required>

            <button type="submit">執行複製</button>
        </form>
    </div>
<?php endif; ?>
<?php

echo "<table border='1' cellpadding='6'><tr><th>醫師</th>";
foreach ($days as $d) echo "<th>{$d}</th>";
echo "</tr>";

while ($doc = $doctors->fetch_assoc()) {
    echo "<tr><td>" . htmlspecialchars($doc['name']) . "</td>";
    foreach ($days as $d) {
        $stmt = $conn->prepare("SELECT schedule_id, start_time, end_time, is_available FROM schedules WHERE doctor_id = ? AND schedule_date = ?");
        $stmt->bind_param("is", $doc['doctor_id'], $d);
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs->num_rows === 0) {
            echo "<td>❌</td>";
        } else {
            $cell = "";
            while ($s = $rs->fetch_assoc()) {
                $icon = $s['is_available'] ? "✅" : "🚫";
                $cell .= "{$icon}{$s['start_time']}~{$s['end_time']}";
                if ($can_delete) {
                    $cell .= " <a href='schedule_delete.php?schedule_id={$s['schedule_id']}&date={$date}' onclick='return confirm(\"確定要刪除這筆排班嗎？\")'>🗑️</a>";
                }
                $cell .= "<br>";
            }
            echo "<td>{$cell}</td>";
        }
    }
    echo "</tr>";
}
echo "</table>";

switch ($role) {
    case 'admin':
        echo "<p><a href='/clinic/admins/dashboard.php'>🔙 回到主頁</a></p>";
        break;
    case 'doctor':
        echo "<p><a href='/clinic/doctors/dashboard.php'>🔙 回到主頁</a></p>";
        break;
    case 'patient':
        echo "<p><a href='/clinic/patients/dashboard.php'>🔙 回到主頁</a></p>";
        break;
}
?>