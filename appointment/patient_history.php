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

<?php include("../header.php"); ?>
<link rel="stylesheet" href="/clinic/style.css">

<div class="dashboard" style="max-width:700px;margin:40px auto;">
    <h2 style="text-align:center;">🧾 <?= htmlspecialchars($patient_name) ?> 的歷史紀錄</h2>

    <?php if ($result->num_rows === 0): ?>
        <p>目前尚無任何紀錄。</p>
    <?php else: ?>
        <table class="table" style="width:100%;border-collapse:collapse;background:#fffdfa;">
            <tr style="background: #f7f5f2; color: #23272f;">
                <th>日期</th>
                <th>時段</th>
                <th>服務類型</th>
                <th>狀態</th>
                <th>評價</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr style="text-align:center;">
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['time_slot']) ?></td>
                    <td>
                        <?php
                        switch ($row['service_type']) {
                            case 'consultation': echo '一般諮詢'; break;
                            case 'checkup': echo '健檢'; break;
                            case 'follow_up': echo '回診'; break;
                            case 'emergency': echo '急診'; break;
                            case 'vaccination': echo '疫苗注射'; break;
                            default: echo htmlspecialchars($row['service_type']);
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['status'] === 'completed') echo '<span style="color:#555;">已完成</span>';
                        elseif ($row['status'] === 'checked_in') echo '<span style="color:#2b6cb0;">已報到</span>';
                        elseif ($row['status'] === 'scheduled') echo '<span style="color:#227d3b;">預約中</span>';
                        elseif ($row['status'] === 'no-show') echo '<span style="color:#a94442;">未到</span>';
                        elseif ($row['status'] === 'cancelled') echo '<span style="color:#a94442;">已取消</span>';
                        else echo htmlspecialchars($row['status']);
                        ?>
                    </td>
                    <td><?= $row['rating'] ?? '—' ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>

    <p style="text-align:center;margin-top:24px;">
        <a class="button" href="../appointment/appointments_upcoming.php">🔙 回到預約列表</a>
    </p>
</div>

<?php include("../footer.php"); ?>