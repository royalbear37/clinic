<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$user_id = $_SESSION['uid'];  // users.id

// 先找出該使用者對應的病患 ID
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patientRow = $result->fetch_assoc();

if (!$patientRow) {
    $msg = "❌ 錯誤：找不到對應的病患資料。";
    $msg_type = "error";
} else {
    $patient_id = $patientRow['patient_id'];

    // 接收預約資料
    $doctor_id = $_POST['doctor_id'] ?? null;
    $appointment_date = $_POST['appointment_date'] ?? null;
    $time_slot = $_POST['time_slot'] ?? null;
    $service_type = $_POST['service_type'] ?? null;

    // 如果是疫苗注射，不檢查 doctor_id
    if ($service_type === 'vaccination') {
        if (!$appointment_date || !$time_slot || !$service_type) {
            $msg = "❌ 請完整填寫所有欄位。";
            $msg_type = "error";
        } else {
            // 寫入資料（doctor_id 設為 NULL，service_type 寫 'vaccination'）
            $insert = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, service_type, status) 
                                  VALUES (?, NULL, ?, ?, 'vaccination', 'scheduled')");
            $insert->bind_param("iss", $patient_id, $appointment_date, $time_slot);

            if ($insert->execute()) {
                $msg = "✅ 預約成功！";
                $msg_type = "success";
            } else {
                $msg = "❌ 寫入失敗：" . $insert->error;
                $msg_type = "error";
            }
        }
    } else {
        // 其他服務需檢查 doctor_id
        if (!$doctor_id || !$appointment_date || !$time_slot || !$service_type) {
            $msg = "❌ 請完整填寫所有欄位。";
            $msg_type = "error";
        } else {
            // 設定每 slot 上限
            $limit = 3;

            // 取得該醫師當天所有 time_slot（不含已取消）
            $stmt = $conn->prepare("SELECT DISTINCT time_slot FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled' ORDER BY STR_TO_DATE(SUBSTRING_INDEX(time_slot, '-', 1), '%H:%i') ASC");
            $stmt->bind_param("is", $doctor_id, $appointment_date);
            $stmt->execute();
            $time_slots_result = $stmt->get_result();

            $time_slots = [];
            while ($ts_row = $time_slots_result->fetch_assoc()) {
                $time_slots[] = $ts_row['time_slot'];
            }

            // 如果這個時段還沒有人預約，補進去排序
            if (!in_array($time_slot, $time_slots)) {
                $time_slots[] = $time_slot;
                // 重新排序
                usort($time_slots, function ($a, $b) {
                    $a_time = strtotime(explode('-', $a)[0]);
                    $b_time = strtotime(explode('-', $b)[0]);
                    return $a_time - $b_time;
                });
            }

            // 你的所有時段（依實際情況調整）
            $all_slots = [
                "09:00-09:30",
                "09:30-10:00",
                "10:00-10:30",
                "10:30-11:00",
                "11:00-11:30",
                "11:30-12:00",
                "13:00-13:30",
                "13:30-14:00",
                "14:00-14:30",
                "14:30-15:00",
                "15:00-15:30",
                "15:30-16:00",
                "16:00-16:30",
                "16:30-17:00",
                "18:00-18:30",
                "18:30-19:00",
                "19:00-19:30",
                "19:30-20:00",
                "20:00-20:30",
                "20:30-21:00"
            ];

            // 找出目前 time_slot 是第幾個（n）
            $n = array_search($time_slot, $all_slots) + 1;

            // 查詢該醫師該 time_slot 已有幾筆預約（不含已取消）
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND time_slot = ? AND status != 'cancelled'");
            $stmt->bind_param("iss", $doctor_id, $appointment_date, $time_slot);
            $stmt->execute();
            $count_result = $stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            $m = $count_row ? $count_row['count'] : 0;

            // 計算 visit_number
            $visit_number = ($n - 1) * $limit + $m + 1;

            // 檢查同一病患同一天同一時段是否已預約
            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ? AND time_slot = ? AND status != 'cancelled'");
            $stmt->bind_param("iiss", $patient_id, $doctor_id, $appointment_date, $time_slot);
            $stmt->execute();
            $dup_result = $stmt->get_result();
            $dup_row = $dup_result->fetch_assoc();
            if ($dup_row['cnt'] > 0) {
                $msg = "❌ 您已預約此時段，請勿重複預約。";
                $msg_type = "error";
                // 跳出，不再往下執行
            } else {
                // 寫入資料時一併存入 visit_number
                $insert = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, service_type, status, visit_number) 
                                  VALUES (?, ?, ?, ?, ?, 'scheduled', ?)");
                $insert->bind_param("iisssi", $patient_id, $doctor_id, $appointment_date, $time_slot, $service_type, $visit_number);

                if ($insert->execute()) {
                    $msg = "✅ 預約成功！您的看診序號為：<strong style='color:#227d3b;font-size:1.2em;'>$visit_number</strong>";
                    $msg_type = "success";
                } else {
                    $msg = "❌ 寫入失敗：" . $insert->error;
                    $msg_type = "error";
                }
            }
        }
    }
}
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:480px;margin:40px auto;text-align:center;">
    <h2>預約結果</h2>
    <?php if (isset($msg)): ?>
        <p class="<?= $msg_type ?>"><?= $msg ?></p>
    <?php endif; ?>

    <?php if (isset($msg_type) && $msg_type === "success"): ?>
        <div style="margin:2em 0;">
            <a href="new_appointment.php" class="button" style="max-width:200px;">再預約一筆</a>
            <a href="my_appointment.php" class="button" style="max-width:200px;">查看預約紀錄</a>
        </div>
    <?php elseif (isset($msg_type) && $msg_type === "error"): ?>
        <div style="margin:2em 0;">
            <a href="new_appointment.php" class="button" style="max-width:200px;">返回預約頁面</a>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['role'])): ?>
        <div style="margin-top:2em;">
            <a href="/clinic/<?= $_SESSION['role'] ?>s/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
        </div>
    <?php endif; ?>
</div>
<?php include("../footer.php"); ?>