<?php
session_start();
include("../config/mysql_connect.inc.php");
include("../header.php");

// 指派代班醫師處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'], $_POST['substitute_doctor_id'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $substitute_doctor_id = intval($_POST['substitute_doctor_id']);

    // 取得該班表資訊
    $sql_info = "SELECT doctor_id, schedule_date, shift FROM schedules WHERE schedule_id = $schedule_id";
    $info = $conn->query($sql_info)->fetch_assoc();
    if ($info) {
        $ori_doctor_id = intval($info['doctor_id']);
        $date = $conn->real_escape_string($info['schedule_date']);
        $shift = $conn->real_escape_string($info['shift']);

        // 更新 schedules（指派代班醫師）
        $conn->query("UPDATE schedules SET substitute_doctor_id = $substitute_doctor_id WHERE schedule_id = $schedule_id");

        // 檢查代班醫師是否已有該時段班表
        $check_sql = "SELECT schedule_id FROM schedules WHERE doctor_id = $substitute_doctor_id AND schedule_date = '$date' AND shift = '$shift'";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows == 0) {
            // 自動新增一筆班表，is_available=1
            $conn->query("INSERT INTO schedules (doctor_id, schedule_date, shift, is_available) VALUES ($substitute_doctor_id, '$date', '$shift', 1)");
        }

        // 更新 appointments
        // shift 對應 time_slot 的陣列
        $shift_time_slots = [
            'morning'   => ['09:00-09:30', '09:30-10:00', '10:00-10:30', '10:30-11:00', '11:00-11:30', '11:30-12:00'],
            'afternoon' => ['13:00-13:30', '13:30-14:00', '14:00-14:30', '14:30-15:00', '15:00-15:30', '15:30-16:00', '16:00-16:30', '16:30-17:00'],
            'evening'   => ['18:00-18:30', '18:30-19:00', '19:00-19:30', '19:30-20:00', '20:00-20:30', '20:30-21:00']
        ];
        $time_slots = $shift_time_slots[$shift];
        $time_slots_str = "'" . implode("','", $time_slots) . "'";

        $sql_update_appointments = "UPDATE appointments 
            SET substitute_doctor_id = $substitute_doctor_id
            WHERE doctor_id = $ori_doctor_id 
              AND appointment_date = '$date' 
              AND time_slot IN ($time_slots_str)";
        $conn->query($sql_update_appointments);
        // Debug: 印出 SQL 與影響筆數
        error_log($sql_update_appointments);
        error_log('affected rows: ' . $conn->affected_rows);

        // 查詢代班醫師姓名
        $sub_sql = "SELECT u.name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.doctor_id = $substitute_doctor_id";
        $sub_res = $conn->query($sub_sql);
        $sub_name = ($sub_res && $row = $sub_res->fetch_assoc()) ? $row['name'] : '代班醫師';

        // 查詢所有受影響的預約（含 appointment_id 與 time_slot）
        $sql_appointments = "SELECT appointment_id, patient_id, time_slot FROM appointments
            WHERE doctor_id = $ori_doctor_id
              AND appointment_date = '$date'
              AND time_slot IN ($time_slots_str)";
        $res_appointments = $conn->query($sql_appointments);

        $notify_success = 0;
        while ($app_row = $res_appointments->fetch_assoc()) {
            $appointment_id = intval($app_row['appointment_id']);
            $patient_id = intval($app_row['patient_id']);
            $time_slot = $app_row['time_slot'];
            $msg = "您的 $date $time_slot 門診已由 $sub_name 醫師代班。";
            $msg = $conn->real_escape_string($msg);
            $type = 'substitute'; // 你可自訂通知類型
            if ($conn->query("INSERT INTO notifications (appointment_id, patient_id, type, message, sent_at) VALUES ($appointment_id, $patient_id, '$type', '$msg', NOW())")) {
                $notify_success++;
            }
        }

        if ($notify_success > 0) {
            echo "<script>alert('已指派代班醫師並發送通知給病患！');location.href='doctor_substitute.php';</script>";
        } else {
            echo "<script>alert('已指派代班醫師，但通知發送失敗！');location.href='doctor_substitute.php';</script>";
        }
        exit;
    }
}

// 取得請假班表
$sql = "SELECT 
            s.schedule_id,
            u.name AS doctor_name, 
            s.schedule_date, 
            s.shift,
            d.doctor_id
        FROM schedules s
        JOIN doctors d ON s.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.id
        WHERE s.is_available = 0
        ORDER BY s.schedule_date, s.shift";
$result = $conn->query($sql);
?>

<div class="dashboard" style="max-width:900px;margin:40px auto;">
    <h2>🏥 請假醫師與代班指派</h2>
    <table class="table" style="width:100%;background:#fffdfa;">
        <tr style="background:#f7f5f2;">
            <th>日期</th>
            <th>時段</th>
            <th>請假醫師</th>
            <th>指派代班醫師</th>
        </tr>
        <?php
        $hasRow = false;
        while($row = $result->fetch_assoc()):
            $hasRow = true;
            // 查詢該班表時段有無 appointment 已指派代班醫師
            $doctor_id = intval($row['doctor_id']);
            $date = $conn->real_escape_string($row['schedule_date']);
            $shift = $conn->real_escape_string($row['shift']);
            // 取得該時段所有 time_slot
            $shift_time_slots = [
                'morning'   => ['09:00-09:30', '09:30-10:00', '10:00-10:30', '10:30-11:00', '11:00-11:30', '11:30-12:00'],
                'afternoon' => ['13:00-13:30', '13:30-14:00', '14:00-14:30', '14:30-15:00', '15:00-15:30', '15:30-16:00', '16:00-16:30', '16:30-17:00'],
                'evening'   => ['18:00-18:30', '18:30-19:00', '19:00-19:30', '19:30-20:00', '20:00-20:30', '20:30-21:00']
            ];
            $time_slots = $shift_time_slots[$shift];
            $time_slots_str = "'" . implode("','", $time_slots) . "'";
            // 查詢該時段有無 appointment 已指派代班醫師
            $sql_app = "SELECT a.substitute_doctor_id, u.name AS sub_name
                        FROM appointments a
                        LEFT JOIN doctors d ON a.substitute_doctor_id = d.doctor_id
                        LEFT JOIN users u ON d.user_id = u.id
                        WHERE a.doctor_id = $doctor_id
                          AND a.appointment_date = '$date'
                          AND a.time_slot IN ($time_slots_str)
                          AND a.substitute_doctor_id IS NOT NULL
                        LIMIT 1";
            $app_res = $conn->query($sql_app);
            $sub_name = null;
            if ($app_res && $app_row = $app_res->fetch_assoc()) {
                $sub_name = $app_row['sub_name'];
            }
        ?>
        <tr>
            <td><?= htmlspecialchars($row['schedule_date']) ?></td>
            <td><?= htmlspecialchars($row['shift']) ?></td>
            <td><?= htmlspecialchars($row['doctor_name']) ?></td>
            <td>
                <?php if ($sub_name): ?>
                    <span style="color:green;">代班醫師：<?= htmlspecialchars($sub_name) ?></span>
                <?php else: ?>
                    <form method="post" action="" style="margin:0;">
                        <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                        <select name="substitute_doctor_id" required>
                            <option value="">請選擇</option>
                            <?php
                            // 查詢同科別、不是自己、今天沒請假的醫師
                            $dep_sql = "SELECT department_id FROM doctors WHERE doctor_id = $doctor_id";
                            $dep_row = $conn->query($dep_sql)->fetch_assoc();
                            $department_id = intval($dep_row['department_id']);
                            $sql_sub = "
                                SELECT d.doctor_id, u.name
                                FROM doctors d
                                JOIN users u ON d.user_id = u.id
                                WHERE d.department_id = $department_id
                                  AND d.doctor_id != $doctor_id
                                  AND d.doctor_id NOT IN (
                                      SELECT doctor_id FROM schedules
                                      WHERE schedule_date = '$date'
                                        AND shift = '$shift'
                                        AND is_available = 0
                                  )
                            ";
                            $sub_result = $conn->query($sql_sub);
                            while($sub = $sub_result->fetch_assoc()):
                            ?>
                            <option value="<?= $sub['doctor_id'] ?>"><?= htmlspecialchars($sub['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit">指派</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$hasRow): ?>
        <tr><td colspan="4" style="text-align:center;">目前沒有請假醫師</td></tr>
        <?php endif; ?>
    </table>
<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>
</div>
<?php include("../footer.php"); ?>
