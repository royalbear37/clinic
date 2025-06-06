<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$user_id = $_SESSION['uid'];

// 查對應的病患 ID
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) {
    echo "❌ 找不到病患資料。";
    exit();
}
$patient_id = $row['patient_id'];

$sql = "SELECT a.*, u.name AS doctor_name, d.name AS department
        FROM appointments a
        JOIN doctors doc ON a.doctor_id = doc.doctor_id
        JOIN users u ON doc.user_id = u.id
        JOIN departments d ON doc.department_id = d.department_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.time_slot";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>我的預約紀錄</h2>
<table border="1" cellpadding="8">
    <tr>
        <th>日期</th>
        <th>時段</th>
        <th>醫師</th>
        <th>科別</th>
        <th>服務類型</th>
        <th>狀態</th>
        <th>操作</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
        <td><?= htmlspecialchars($row['time_slot']) ?></td>
        <td><?= htmlspecialchars($row['doctor_name']) ?></td>
        <td><?= htmlspecialchars($row['department']) ?></td>
        <td><?= htmlspecialchars($row['service_type']) ?></td>
        <td><?= htmlspecialchars($row['status']) ?></td>
        <td>
            <?php if ($row['status'] === 'scheduled'): ?>
                <a href="appointment_cancel.php?id=<?= $row['appointment_id'] ?>" onclick="return confirm('確定要取消這筆預約嗎？');">取消</a>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<?php
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    echo "<p><a href='/clinic/{$role}s/dashboard.php'>🔙 回到主頁</a></p>";
}
?>