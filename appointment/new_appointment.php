<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../users/login.php");
    exit();
}

// 撈出所有科別
$departments = $conn->query("SELECT * FROM departments");

// 撈所有啟用醫師（附上科別）
$doctor_map = [];
$doctor_sql = "SELECT doctor_id, users.name AS doctor_name, doctors.department_id 
               FROM doctors 
               JOIN users ON doctors.user_id = users.id 
               WHERE is_active = 'yes'";
$result = $conn->query($doctor_sql);
while ($row = $result->fetch_assoc()) {
    $doctor_map[$row['department_id']][] = $row;
}

// 建立時間區間（09:00~17:30，每 30 分鐘）
function generateTimeSlots($start = "09:00", $end = "17:30", $interval = 30) {
    $slots = [];
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    while ($startTime < $endTime) {
        $nextTime = $startTime + $interval * 60;
        $slots[] = date("H:i", $startTime) . "-" . date("H:i", $nextTime);
        $startTime = $nextTime;
    }
    return $slots;
}
$time_slots = generateTimeSlots();
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:480px;margin:40px auto;">
    <h2 style="text-align:center;">📅 新增預約</h2>
    <form method="post" action="appointment_submit.php">
        <div class="form-group">
            <label>科別：</label>
            <select id="dept_select" required>
                <?php
                $departments->data_seek(0);
                while ($dept = $departments->fetch_assoc()): ?>
                    <option value="<?= $dept['department_id'] ?>"><?= $dept['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>醫師：</label>
            <select name="doctor_id" id="doctor_select" required></select>
        </div>
        <div class="form-group">
            <label>預約日期：</label>
            <input type="date" name="appointment_date" required>
        </div>
        <div class="form-group">
            <label>時段：</label>
            <select name="time_slot" required>
                <?php foreach ($time_slots as $slot): ?>
                    <option value="<?= $slot ?>"><?= $slot ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>服務類型：</label>
            <select name="service_type" required>
                <option value="consultation">一般諮詢</option>
                <option value="checkup">健檢</option>
                <option value="follow_up">回診</option>
                <option value="emergency">急診</option>
            </select>
        </div>
        <button type="submit" class="button">送出預約</button>
    </form>
    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/schedule/schedule_overview.php" class="button" style="max-width:200px;">📅 查看醫師班表</a>
        <?php if (isset($_SESSION['role'])): ?>
            <a href="/clinic/<?= $_SESSION['role'] ?>s/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
        <?php endif; ?>
    </div>
</div>
<script>
const doctorsByDept = <?= json_encode($doctor_map) ?>;

function updateDoctors(dept_id) {
    const select = document.getElementById("doctor_select");
    select.innerHTML = "";
    (doctorsByDept[dept_id] || []).forEach(d => {
        const opt = document.createElement("option");
        opt.value = d.doctor_id;
        opt.text = d.doctor_name;
        select.appendChild(opt);
    });
}

document.getElementById("dept_select").addEventListener("change", function() {
    updateDoctors(this.value);
});

// 初始化載入
updateDoctors(document.getElementById("dept_select").value);
</script>
<?php include("../footer.php"); ?>
