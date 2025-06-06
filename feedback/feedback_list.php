<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], ['patient', 'doctor'])) {
    header("Location: /clinic/users/login.php");
    exit();
}

$uid = $_SESSION['uid'];
$role = $_SESSION['role'];

if ($role === 'patient') {
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!$row) {
        echo "❌ 找不到病患資料。";
        exit();
    }
    $id = $row['patient_id'];

    $sql = "SELECT f.rating, f.comment, f.submitted_at, a.appointment_date, a.time_slot, u.name AS doctor_name
            FROM feedback f
            JOIN appointments a ON f.appointment_id = a.appointment_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE a.patient_id = ?
            ORDER BY f.submitted_at DESC";

} elseif ($role === 'doctor') {
    $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!$row) {
        echo "❌ 找不到醫師資料。";
        exit();
    }
    $id = $row['doctor_id'];

    $sql = "SELECT f.rating, f.comment, f.submitted_at, a.appointment_date, a.time_slot, u.name AS patient_name
            FROM feedback f
            JOIN appointments a ON f.appointment_id = a.appointment_id
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN users u ON p.user_id = u.id
            WHERE a.doctor_id = ?
            ORDER BY f.submitted_at DESC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>回饋紀錄</h2>

<?php if ($result->num_rows === 0): ?>
    <p>目前沒有回饋資料。</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>看診日期</th>
            <th>時段</th>
            <th><?= $role === 'patient' ? '醫師' : '病患' ?></th>
            <th>評分</th>
            <th>留言</th>
            <th>填寫時間</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['appointment_date'] ?></td>
                <td><?= $row['time_slot'] ?></td>
                <td><?= $role === 'patient' ? $row['doctor_name'] : $row['patient_name'] ?></td>
                <td><?= $row['rating'] ?></td>
                <td><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
                <td><?= $row['submitted_at'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

<p><a href="/clinic/<?= $role ?>s/dashboard.php">🔙 回到主頁</a></p>