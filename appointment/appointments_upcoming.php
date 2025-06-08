<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /clinic/users/login.php");
    exit();
}

$uid = $_SESSION['uid'];

// 查出對應的 doctor_id
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$doctor = $res->fetch_assoc();

if (!$doctor) {
    echo "❌ 找不到醫師資料。";
    exit();
}
$doctor_id = $doctor['doctor_id'];

// 若有表單送出更新狀態
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['appointment_id'], $_POST['new_status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $_POST['new_status'];
    $valid_statuses = ['checked_in', 'completed', 'no-show', 'cancelled'];

    if (in_array($new_status, $valid_statuses)) {
        if ($new_status === 'checked_in') {
            $stmt = $conn->prepare("UPDATE appointments SET status = ?, checkin_time = NOW() WHERE appointment_id = ? AND doctor_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        }
        $stmt->bind_param("sii", $new_status, $appointment_id, $doctor_id);
        $stmt->execute();
        echo "<script>window.location.href='appointments_upcoming.php';</script>";
        exit();
    } else {
        $msg = "❌ 無效狀態。";
        $msg_type = "error";
    }
}

// 撈出未來 30 天內該醫師的所有預約，並加上 patient_id
$sql = "SELECT a.appointment_id, a.appointment_date, a.time_slot, a.service_type, a.status,
               p.patient_id, u.name AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.id
        WHERE a.doctor_id = ? AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY a.appointment_date, a.time_slot";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include("../header.php"); ?>
<div class="dashboard">

    <h2 style="text-align:center;">🗓 未來一個月內的預約紀錄</h2>
    <?php if (isset($msg)): ?>
        <p class="<?= $msg_type ?>"><?= $msg ?></p>
    <?php endif; ?>
    <div style="overflow-x:auto;">
        <?php if ($result->num_rows === 0): ?>
            <p>目前沒有預約。</p>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;background:#fffdfa;">
                <thead>
                    <tr style="background: #f7f5f2; color: #23272f;">
                        <th>日期</th>
                        <th>時段</th>
                        <th>病患</th>
                        <th>服務類型</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="text-align:center;">
                            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                            <td><?= htmlspecialchars($row['time_slot']) ?></td>
                            <td>
                                <a href="patient_history.php?patient_id=<?= $row['patient_id'] ?>">
                                    <?= htmlspecialchars($row['patient_name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($row['service_type']) ?></td>
                            <td>
                                <?php
                                if ($row['status'] === 'scheduled') echo '<span style="color:#227d3b;">預約中</span>';
                                elseif ($row['status'] === 'checked_in') echo '<span style="color:#2b6cb0;">已報到</span>';
                                elseif ($row['status'] === 'completed') echo '<span style="color:#555;">已完成</span>';
                                elseif ($row['status'] === 'no-show') echo '<span style="color:#a94442;">未到</span>';
                                elseif ($row['status'] === 'cancelled') echo '<span style="color:#a94442;">已取消</span>';
                                else echo htmlspecialchars($row['status']);
                                ?>
                            </td>
                            <td>
                                <div class="appointment-actions">
                                    <form method="post" class="appointment-action-form">
                                        <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                        <select name="new_status">
                                            <option value="checked_in" <?= $row['status'] === 'checked_in' ? 'selected' : '' ?>>✔️ 報到</option>
                                            <option value="completed" <?= $row['status'] === 'completed' ? 'selected' : '' ?>>✅ 完成</option>
                                            <option value="no-show" <?= $row['status'] === 'no-show' ? 'selected' : '' ?>>❌ 未到</option>
                                            <option value="cancelled" <?= $row['status'] === 'cancelled' ? 'selected' : '' ?>>❎ 取消</option>
                                        </select>
                                        <button type="submit" class="button">更新</button>
                                        <button type="button"
                                            class="prescribe-button"
                                            onclick="window.location.href='/clinic/doctors/prescribe.php?id=<?= $row['appointment_id'] ?>'">
                                            開藥
                                        </button>

                                    </form>
                                </div>
                            </td>


                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/doctors/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
    </div>
</div>
<?php include("../footer.php"); ?>