<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 撈出所有科別
$departments = $conn->query("SELECT * FROM departments");
$selected_dept = $_GET['department_id'] ?? '';

// 撈出該科別醫師
$doctors = [];
if ($selected_dept) {
    $stmt = $conn->prepare("SELECT d.doctor_id, u.name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.department_id = ? ORDER BY u.name");
    $stmt->bind_param("i", $selected_dept);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
} else {
    // 若未選科別，預設不顯示醫師
    $result = [];
}
?>

<?php include("../header.php"); ?>

<h2>🗓️ 醫師排班管理（依日期）</h2>

<!-- 科別選單 -->
<form method="get" id="deptForm" style="margin-bottom:1em;">
    科別：
    <select name="department_id" id="department_id" required onchange="document.getElementById('deptForm').submit();">
        <option value="">請選擇</option>
        <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['department_id'] ?>" <?= $selected_dept == $dept['department_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($dept['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($selected_dept): ?>
<form method="post" action="schedule_submit.php">
    <input type="hidden" name="department_id" value="<?= htmlspecialchars($selected_dept) ?>">
    醫師：
    <select name="doctor_id" required>
        <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['doctor_id'] ?>"><?= htmlspecialchars($doc['name']) ?></option>
        <?php endforeach; ?>
    </select><br>
    開始日期：<input type="date" name="week_start" id="week_start" required><br><br>

    <div style="overflow-x:auto;">
        <table border="1" cellpadding="6" style="text-align:center; width:100%; max-width:500px;">
            <tr>
                <th>日期</th>
                <th>早班</th>
                <th>中班</th>
                <th>晚班</th>
            </tr>
            <?php for ($d=0; $d<7; $d++): ?>
                <tr>
                    <td id="day<?= $d ?>"></td>
                    <?php foreach (['morning','afternoon','evening'] as $shift_key): ?>
                        <td>
                            <input type="checkbox" name="schedule[<?= $shift_key ?>][<?= $d ?>]" value="1">
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>
    <br>
    <button type="submit">儲存本週排班</button>
</form>
<?php else: ?>
    <p style="color:#888;">請先選擇科別</p>
<?php endif; ?>

<script>
// 根據開始日期動態顯示每一欄的日期
function updateTableHeaders() {
    const weekStart = document.getElementById('week_start');
    if (!weekStart || !weekStart.value) return;
    const weekDays = ['日','一','二','三','四','五','六'];
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart.value);
        date.setDate(date.getDate() + i);
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        const day = weekDays[date.getDay()];
        const th = document.getElementById('day'+i);
        if (th) th.textContent = `星期${day} (${mm}/${dd})`;
    }
}
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'week_start') updateTableHeaders();
});
window.addEventListener('DOMContentLoaded', updateTableHeaders);
</script>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>

<?php include("../footer.php"); ?>
