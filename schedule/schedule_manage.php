<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 撈出所有醫師
$doctors = $conn->query("SELECT d.doctor_id, u.name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.name");

// 班別 shift 選項
$shifts = [
    'morning' => '早班（09:00～12:00）',
    'afternoon' => '中班（13:00～17:00）',
    'evening' => '晚班（18:00～21:00）'
];
?>

<?php include("../header.php"); ?>

<h2>🗓️ 醫師排班管理（依班別）</h2>

<form method="post" action="schedule_submit.php">
    醫師：
    <select name="doctor_id" required>
        <?php $doctors->data_seek(0); while ($doc = $doctors->fetch_assoc()): ?>
            <option value="<?= $doc['doctor_id'] ?>"><?= htmlspecialchars($doc['name']) ?></option>
        <?php endwhile; ?>
    </select><br>
    開始日期：<input type="date" name="week_start" id="week_start" required><br><br>

    <div style="overflow-x:auto;">
        <table border="1" cellpadding="6" style="text-align:center; min-width:700px;">
            <tr>
                <th style="white-space:nowrap;">班別＼星期</th>
                <?php for ($d=0; $d<7; $d++): ?>
                    <th id="day<?= $d ?>" style="white-space:normal;font-size:0.95em;"></th>
                <?php endfor; ?>
            </tr>
            <?php foreach ($shifts as $shift_key => $shift_label): ?>
                <tr>
                    <td><?= $shift_label ?></td>
                    <?php for ($d=0; $d<7; $d++): ?>
                        <td>
                            <input type="checkbox" name="schedule[<?= $shift_key ?>][<?= $d ?>]" value="1">
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <br>
    <button type="submit">儲存本週排班</button>
</form>

<script>
// 根據開始日期動態顯示每一欄的日期
function updateTableHeaders() {
    const weekStart = document.getElementById('week_start').value;
    if (!weekStart) return;
    const weekDays = ['日','一','二','三','四','五','六'];
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(date.getDate() + i);
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        const day = weekDays[date.getDay()];
        document.getElementById('day'+i).textContent = `星期${day} (${mm}/${dd})`;
    }
}
document.getElementById('week_start').addEventListener('change', updateTableHeaders);
window.addEventListener('DOMContentLoaded', updateTableHeaders);
</script>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>

<?php include("../footer.php"); ?>
