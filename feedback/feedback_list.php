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

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:900px;margin:40px auto;">
    <h2 style="text-align:center;">回饋紀錄</h2>
    <?php if ($result->num_rows === 0): ?>
        <p>目前沒有回饋資料。</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;background:#fffdfa;">
            <thead>
                <tr style="background: #f7f5f2; color: #23272f;">
                    <th>看診日期</th>
                    <th>時段</th>
                    <th><?= $role === 'patient' ? '醫師' : '病患' ?></th>
                    <th>評分</th>
                    <th>留言</th>
                    <th>填寫時間</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr style="text-align:center;">
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['time_slot']) ?></td>
                    <td><?= $role === 'patient' ? htmlspecialchars($row['doctor_name']) : htmlspecialchars($row['patient_name']) ?></td>
                    <td><?= htmlspecialchars($row['rating']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
                    <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>

    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/<?= $role ?>s/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
    </div>
</div>
<?php include("../footer.php"); ?>