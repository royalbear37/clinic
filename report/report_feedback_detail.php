<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

$doctor_id = $_GET['doctor_id'] ?? 0;
$month = $_GET['month'] ?? date('Y-m');
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// 取得醫師姓名
$stmt = $conn->prepare("SELECT u.name FROM doctors d JOIN users u ON d.user_id = u.id WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$res = $stmt->get_result();
$doc = $res->fetch_assoc();
$doctor_name = $doc['name'] ?? '未知醫師';

// 撈留言
$sql = "
SELECT f.rating, f.comment, f.submitted_at, u.name AS patient_name
FROM feedback f
JOIN appointments a ON f.appointment_id = a.appointment_id
JOIN patients p ON a.patient_id = p.patient_id
JOIN users u ON p.user_id = u.id
WHERE a.doctor_id = ? AND f.submitted_at BETWEEN ? AND ?
ORDER BY f.submitted_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $doctor_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>📝 <?= htmlspecialchars($doctor_name) ?> 的回饋留言（<?= $month ?>）</h2>

<?php if ($result->num_rows === 0): ?>
    <p>此月份無回饋留言。</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>病患</th>
            <th>評分</th>
            <th>留言</th>
            <th>時間</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><?= $row['rating'] ?></td>
                <td><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
                <td><?= $row['submitted_at'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

<p><a href="report_feedback.php?month=<?= $month ?>">🔙 回上頁</a></p>
