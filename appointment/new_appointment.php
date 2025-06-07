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

// 建立時間區間（09:00~21:00，每 30 分鐘）
function generateTimeSlots() {
    $ranges = [
        ["09:00", "12:00"],  // 早班
        ["13:00", "17:00"],  // 中班
        ["18:00", "21:00"]   // 晚班
    ];

    $interval = 30; // 每 30 分鐘
    $slots = [];

    foreach ($ranges as [$start, $end]) {
        $startTime = strtotime($start);
        $endTime = strtotime($end);

        while ($startTime < $endTime) {
            $nextTime = $startTime + $interval * 60;
            $slots[] = date("H:i", $startTime) . "-" . date("H:i", $nextTime);
            $startTime = $nextTime;
        }
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
            <label>預約日期：</label>
            <input type="date" name="appointment_date" required>
        </div>
        <div class="form-group">
            <label>時段：</label>
            <select name="time_slot" id="time_slot_select" required>
                <?php foreach ($time_slots as $slot): ?>
                    <option value="<?= $slot ?>"><?= $slot ?></option>
                <?php endforeach; ?>
            </select>
        </div>
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
// 根據時段取得 shift
function getShiftByTimeSlot(slot) {
    // slot 格式 "09:00-09:30"
    const start = slot.split('-')[0];
    if (start >= "09:00" && start < "12:00") return "morning";
    if (start >= "13:00" && start < "17:00") return "afternoon";
    if (start >= "18:00" && start < "21:00") return "evening";
    return "";
}

function fetchDoctors() {
    const dept = document.getElementById("dept_select").value;
    const date = document.querySelector("input[name='appointment_date']").value;
    const slot = document.getElementById("time_slot_select").value;
    const shift = getShiftByTimeSlot(slot);
    const select = document.getElementById("doctor_select");
    select.innerHTML = "<option disabled>載入中...</option>";

    if (!date || !shift) {
        select.innerHTML = "<option disabled selected>請先選擇日期與時段</option>";
        return;
    }

    fetch(`get_doctors_by_schedule.php?department_id=${dept}&appointment_date=${date}&shift=${shift}`)
        .then(res => res.json())
        .then(data => {
            select.innerHTML = "";
            if (data.length === 0) {
                const opt = document.createElement("option");
                opt.disabled = true;
                opt.selected = true;
                opt.text = "此日無醫師排班";
                select.appendChild(opt);
            } else {
                data.forEach(d => {
                    const opt = document.createElement("option");
                    opt.value = d.doctor_id;
                    opt.text = d.doctor_name;
                    select.appendChild(opt);
                });
            }
        });
}

document.getElementById("dept_select").addEventListener("change", fetchDoctors);
document.querySelector("input[name='appointment_date']").addEventListener("change", fetchDoctors);
document.getElementById("time_slot_select").addEventListener("change", fetchDoctors);
window.addEventListener("load", fetchDoctors);
</script>

<?php include("../footer.php"); ?>
