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
$doctor_sql = "
SELECT 
    d.doctor_id, 
    u.name AS doctor_name, 
    d.department_id,
    ROUND(AVG(f.rating), 1) AS avg_rating,
    GROUP_CONCAT(f.comment SEPARATOR '||') AS comments
FROM doctors d
JOIN users u ON d.user_id = u.id
LEFT JOIN appointments a ON d.doctor_id = a.doctor_id AND a.status = 'completed'
LEFT JOIN feedback f ON f.appointment_id = a.appointment_id
WHERE d.is_active = 'yes'
GROUP BY d.doctor_id, u.name, d.department_id
";

$result = $conn->query($doctor_sql);
while ($row = $result->fetch_assoc()) {
    $doctor_map[$row['department_id']][] = $row;
    $comments = array_filter(explode('||', $row['comments'] ?? ''));
    $row['avg_rating'] = is_null($row['avg_rating']) ? '尚無評價' : $row['avg_rating'];
    $row['comments_array'] = $comments ?: ['尚無留言'];
}

// 建立時間區間（09:00~21:00，每 30 分鐘）
function generateTimeSlots()
{
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
            <select name="service_type" id="service_type_select" required>
                <option value="consultation">一般諮詢</option>
                <option value="checkup">健檢</option>
                <option value="follow_up">回診</option>
                <option value="emergency">急診</option>
            </select>
        </div>
        <button type="submit" class="button">送出預約</button>
    </form>
    <!-- 顯示醫師評價區塊 -->
    <div id="doctor-info" style="margin-top: 15px; border: 1px solid #ccc; padding: 10px; display: none;">
        <h4 id="avg-rating">⭐ 平均評分：</h4>
        <div><strong>留言：</strong></div>
        <ul id="comment-list"></ul>
        <button id="toggle-comments-btn" style="display:none; margin-top: 8px;">查看更多留言 ▼</button>
    </div>


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
                    document.getElementById("doctor-info").style.display = "none"; // 加這行隱藏
                } else {
                    data.forEach(d => {
                        const opt = document.createElement("option");
                        opt.value = d.doctor_id;
                        opt.text = d.doctor_name;
                        select.appendChild(opt);
                    });

                    // ✅ 加這段顯示第一位醫師評價
                    showDoctorDetails(data[0]);
                }



            });
    }

    function showDoctorDetails(doctor) {
        document.getElementById('doctor-info').style.display = 'block';
        document.getElementById('avg-rating').textContent = `⭐ 平均評分：${doctor.avg_rating}`;

        const commentList = document.getElementById('comment-list');
        commentList.innerHTML = '';

        if (doctor.comments && doctor.comments.length > 0) {
            doctor.comments.forEach(comment => {
                const li = document.createElement('li');
                li.textContent = comment;
                commentList.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.textContent = '尚無留言';
            commentList.appendChild(li);
        }
    }



    // 服務類型選單動態切換
    const serviceTypeSelect = document.getElementById("service_type_select");
const deptSelect = document.getElementById("dept_select");
const doctorGroup = document.getElementById("doctor_select").closest(".form-group");
const doctorSelect = document.getElementById("doctor_select");
const defaultServiceOptions = [
    {value: "consultation", text: "一般諮詢"},
    {value: "checkup", text: "健檢"},
    {value: "follow_up", text: "回診"},
    {value: "emergency", text: "急診"}
];
const vaccineOption = {value: "vaccination", text: "疫苗注射"};
const otherDeptId = "106";

function updateServiceTypeAndDoctorField() {
    if (deptSelect.value === otherDeptId) {
        // 只顯示疫苗注射
        serviceTypeSelect.innerHTML = "";
        const option = document.createElement("option");
        option.value = vaccineOption.value;
        option.text = vaccineOption.text;
        serviceTypeSelect.appendChild(option);

        // 隱藏醫師欄位
        doctorGroup.style.display = "none";
        doctorSelect.required = false;
        doctorSelect.value = "";
    } else {
        // 顯示一般服務類型
        serviceTypeSelect.innerHTML = "";
        defaultServiceOptions.forEach(opt => {
            const option = document.createElement("option");
            option.value = opt.value;
            option.text = opt.text;
            serviceTypeSelect.appendChild(option);
        });

        // 顯示醫師欄位
        doctorGroup.style.display = "";
        doctorSelect.required = true;
    }
}

deptSelect.addEventListener("change", updateServiceTypeAndDoctorField);
window.addEventListener("DOMContentLoaded", updateServiceTypeAndDoctorField);
    document.getElementById("dept_select").addEventListener("change", fetchDoctors);
    document.querySelector("input[name='appointment_date']").addEventListener("change", fetchDoctors);
    document.getElementById("time_slot_select").addEventListener("change", fetchDoctors);
    window.addEventListener("load", fetchDoctors);

    function toggleDoctorField() {
        const serviceType = serviceTypeSelect.value;
        const doctorGroup = document.getElementById("doctor_select").closest(".form-group");
        const doctorSelect = document.getElementById("doctor_select");
        if (serviceType === "vaccination") {
            doctorGroup.style.display = "none";
            doctorSelect.required = false;
            doctorSelect.value = "";
        } else {
            doctorGroup.style.display = "";
            doctorSelect.required = true;
        }
    }

    // 監聽服務類型選擇變化
    serviceTypeSelect.addEventListener("change", toggleDoctorField);
    // 初始化時也執行一次
    window.addEventListener("DOMContentLoaded", toggleDoctorField);
</script>

<?php include("../footer.php"); ?>
