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

    if (!$doctor_id || !$appointment_date || !$time_slot || !$service_type) {
        $msg = "❌ 請完整填寫所有欄位。";
        $msg_type = "error";
    } else {
        // 設定每時段上限 3 人
        $limit = 3;
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments 
                                WHERE doctor_id = ? AND appointment_date = ? AND time_slot = ?");
        $stmt->bind_param("iss", $doctor_id, $appointment_date, $time_slot);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] >= $limit) {
            $msg = "❌ 該時段已達人數上限，請選擇其他時段。";
            $msg_type = "error";
        } else {
            // 檢查是否已有相同病患、日期、時段的預約
            $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appointment_date = ? AND time_slot = ?");
            $stmt->bind_param("iss", $patient_id, $appointment_date, $time_slot);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                // 已有預約，導回並顯示錯誤
                echo "<script>alert('同一時段不可重複預約！');history.back();</script>";
                exit();
            } else {
                // 寫入資料
                $insert = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, service_type, status) 
                                      VALUES (?, ?, ?, ?, ?, 'scheduled')");
                $insert->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $time_slot, $service_type);

                if ($insert->execute()) {
                    $msg = "✅ 預約成功！";
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