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
    $valid_statuses = ['checked-in', 'completed', 'no-show', 'cancelled'];

    if (in_array($new_status, $valid_statuses)) {
        if ($new_status === 'checked-in') {
            $stmt = $conn->prepare("UPDATE appointments SET status = ?, checkin_time = NOW() WHERE appointment_id = ? AND doctor_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        }
        $stmt->bind_param("sii", $new_status, $appointment_id, $doctor_id);
        $stmt->execute();
        echo "<script>window.location.href='appointments_upcoming.php';</script>";
    } else {
        echo "❌ 無效狀態。";
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

<h2>未來一個月內的預約紀錄</h2>

<?php if ($result->num_rows === 0): ?>
    <p>目前沒有預約。</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>日期</th>
            <th>時段</th>
            <th>病患</th>
            <th>服務類型</th>
            <th>狀態</th>
            <th>操作</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['appointment_date'] ?></td>
                <td><?= $row['time_slot'] ?></td>
                <td>
                    <a href="patient_history.php?patient_id=<?= $row['patient_id'] ?>">
                        <?= htmlspecialchars($row['patient_name']) ?>
                    </a>
                </td>
                <td><?= $row['service_type'] ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                        <select name="new_status">
                            <option value="checked-in">✔️ 報到</option>
                            <option value="completed">✅ 完成</option>
                            <option value="no-show">❌ 病患未到</option>
                            <option value="cancelled">❎ 取消</option>
                        </select>
                        <button type="submit">更新</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

<p><a href="/clinic/doctors/dashboard.php">🔙 回到主頁</a></p>