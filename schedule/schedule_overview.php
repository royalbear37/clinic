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
$shift_map = ['morning' => '早', 'afternoon' => '中', 'evening' => '晚'];
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:1100px;margin:40px auto;">
    <h2 style="text-align:center;">📆 醫師班表（<?= $days[0] ?> ~ <?= $days[6] ?>）</h2>
    <form method="get" style="text-align:center;margin-bottom:1.5em;">
        <input type="date" name="date" value="<?= $date ?>" required>
        <button type="submit" class="button">切換週</button>
    </form>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <button onclick="document.getElementById('copy_form').style.display='block'; this.style.display='none';" class="button" style="margin-bottom:1em;">
            ➕ 複製班表
        </button>
        <div id="copy_form" style="display:none; margin-top: 1em;">
            <form method="post" action="schedule_copy_week.php">
                複製來源週（任意一天）：
                <input type="date" name="source_date" required>
                ➡️ 複製到目標週（任意一天）：
                <input type="date" name="target_date" required>
                <button type="submit" class="button">執行複製</button>
            </form>
        </div>
    <?php endif; ?>

    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;background:#fffdfa;">
        <tr style="background: #f7f5f2; color: #23272f;">
            <th>醫師</th>
            <?php foreach ($days as $d) echo "<th>{$d}</th>"; ?>
        </tr>
        <?php while ($doc = $doctors->fetch_assoc()): ?>
            <tr style="text-align:center;">
                <td><?= htmlspecialchars($doc['name']) ?></td>
                <?php foreach ($days as $d): ?>
                    <?php
                    $stmt = $conn->prepare("SELECT schedule_id, shift, is_available FROM schedules WHERE doctor_id = ? AND schedule_date = ?");
                    $stmt->bind_param("is", $doc['doctor_id'], $d);
                    $stmt->execute();
                    $rs = $stmt->get_result();

                    if ($rs->num_rows === 0) {
                        echo "<td>❌</td>";
                    } else {
                        $cell = "";
                        while ($s = $rs->fetch_assoc()) {
                            $icon = $s['is_available'] ? "✅" : "🚫";
                            $label = $shift_map[$s['shift']] ?? $s['shift'];
                            $cell .= "{$icon}{$label}班";
                            if ($can_delete) {
                                $cell .= " <a href='schedule_delete.php?schedule_id={$s['schedule_id']}&date={$date}' onclick='return confirm(\"確定要刪除這筆排班嗎？\")'>🗑️</a>";
                            }
                            $cell .= "<br>";
                        }
                        echo "<td>{$cell}</td>";
                    }
                    ?>
                <?php endforeach; ?>
            </tr>
        <?php endwhile; ?>
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
