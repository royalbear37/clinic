<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /clinic/users/login.php");
    exit();
}

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if (!$patient_id) {
    echo "❌ 錯誤：缺少病患代碼。";
    exit();
}

// 撈出病患基本資料
$stmt = $conn->prepare("SELECT u.name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();
$info = $res->fetch_assoc();

if (!$info) {
    echo "❌ 查無此病患。";
    exit();
}
$patient_name = $info['name'];

// 撈該病患所有歷史預約紀錄
$sql = "SELECT a.appointment_date, a.time_slot, a.service_type, a.status, f.rating
        FROM appointments a
        LEFT JOIN feedback f ON a.appointment_id = f.appointment_id
        WHERE a.patient_id = ? AND a.status = 'completed'
        ORDER BY a.appointment_date DESC, a.time_slot DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>🧾 <?= htmlspecialchars($patient_name) ?> 的歷史紀錄</h2>

<?php if ($result->num_rows === 0): ?>
    <p>目前尚無任何紀錄。</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>日期</th>
            <th>時段</th>
            <th>服務類型</th>
            <th>狀態</th>
            <th>評價</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['appointment_date'] ?></td>
                <td><?= $row['time_slot'] ?></td>
                <td><?= $row['service_type'] ?></td>
                <td><?= $row['status'] ?></td>
                <td><?= $row['rating'] ?? '—' ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

<p><a href="../appointment/appointments_upcoming.php">🔙 回到預約列表</a></p>