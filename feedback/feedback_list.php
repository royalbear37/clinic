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

    // 查詢有 feedback 的紀錄
    $sql = "SELECT f.rating, f.comment, f.submitted_at, a.appointment_date, a.time_slot, u.name AS doctor_name,
                   (SELECT GROUP_CONCAT(medication SEPARATOR ', ') FROM prescriptions WHERE appointment_id = a.appointment_id) AS medication,
                   (SELECT notes FROM prescriptions WHERE appointment_id = a.appointment_id LIMIT 1) AS notes
            FROM feedback f
            JOIN appointments a ON f.appointment_id = a.appointment_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE a.patient_id = ?
            ORDER BY f.submitted_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        $row['has_feedback'] = 1;
        $feedbacks[] = $row;
    }

    // 查詢有開藥但沒 feedback 的紀錄
    $sql2 = "SELECT a.appointment_date, a.time_slot, u.name AS doctor_name,
                    GROUP_CONCAT(pres.medication SEPARATOR ', ') AS medication,
                    pres.notes, NULL AS rating, NULL AS comment, NULL AS submitted_at
            FROM prescriptions pres
            JOIN appointments a ON pres.appointment_id = a.appointment_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE a.patient_id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM feedback f WHERE f.appointment_id = a.appointment_id
              )
            GROUP BY a.appointment_id";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $row['has_feedback'] = 0;
        $feedbacks[] = $row;
    }

    // 依日期排序（最新在前）
    usort($feedbacks, function($a, $b) {
        $dateA = $a['submitted_at'] ?? $a['appointment_date'];
        $dateB = $b['submitted_at'] ?? $b['appointment_date'];
        return strcmp($dateB, $dateA);
    });

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

    // 查詢有 feedback 的紀錄
    $sql = "SELECT f.rating, f.comment, f.submitted_at, a.appointment_date, a.time_slot, u.name AS patient_name,
                   (SELECT GROUP_CONCAT(medication SEPARATOR ', ') FROM prescriptions WHERE appointment_id = a.appointment_id) AS medication,
                   (SELECT notes FROM prescriptions WHERE appointment_id = a.appointment_id LIMIT 1) AS notes,
                   1 AS has_feedback
            FROM feedback f
            JOIN appointments a ON f.appointment_id = a.appointment_id
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN users u ON p.user_id = u.id
            WHERE a.doctor_id = ?
            ORDER BY f.submitted_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }

    // 查詢有開藥但沒 feedback 的紀錄
    $sql2 = "SELECT a.appointment_date, a.time_slot, u.name AS patient_name,
                    GROUP_CONCAT(pres.medication SEPARATOR ', ') AS medication,
                    pres.notes, NULL AS rating, NULL AS comment, NULL AS submitted_at,
                    0 AS has_feedback
            FROM prescriptions pres
            JOIN appointments a ON pres.appointment_id = a.appointment_id
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN users u ON p.user_id = u.id
            WHERE a.doctor_id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM feedback f WHERE f.appointment_id = a.appointment_id
              )
            GROUP BY a.appointment_id";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $feedbacks[] = $row;
    }

    // 依日期排序（最新在前）
    usort($feedbacks, function($a, $b) {
        $dateA = $a['submitted_at'] ?? $a['appointment_date'];
        $dateB = $b['submitted_at'] ?? $b['appointment_date'];
        return strcmp($dateB, $dateA);
    });
}
?>

<?php include("../header.php"); ?>
<style>
    .dashboard {
        max-width: 950px;
        margin: 40px auto;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 2px 16px #e6e6e6;
        padding: 36px 36px 28px 36px;
    }
    .feedback-table {
        width: 100%;
        border-collapse: collapse;
        background: #fffdfa;
        margin: 0 auto;
        font-size: 1.05em;
    }
    .feedback-table th, .feedback-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #ececec;
        text-align: center;
    }
    .feedback-table th {
        background: #f7f5f2;
        color: #23272f;
        font-weight: 600;
        letter-spacing: 1px;
    }
    .feedback-table tr:last-child td {
        border-bottom: none;
    }
    .feedback-table td {
        vertical-align: middle;
    }
    .feedback-table td span {
        color: #888;
        font-size: 0.98em;
    }
    .feedback-table tr:hover {
        background: #f5faff;
    }
    @media (max-width: 900px) {
        .dashboard { padding: 16px 2vw; }
        .feedback-table th, .feedback-table td { padding: 8px 4px; font-size: 0.98em; }
    }
</style>
<div class="dashboard">
    <h2 style="text-align:center;letter-spacing:2px;margin-bottom:1.5em;">回饋紀錄</h2>
    <?php if ($role === 'patient'): ?>
        <?php if (empty($feedbacks)): ?>
            <p style="text-align:center;color:#888;">目前沒有回饋或開藥資料。</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>看診日期</th>
                        <th>時段</th>
                        <th>醫師</th>
                        <th>評分</th>
                        <th>留言</th>
                        <th>開藥內容</th>
                        <th>備註</th>
                        <th>填寫時間</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($feedbacks as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                        <td><?= htmlspecialchars($row['time_slot']) ?></td>
                        <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                        <td>
                            <?php if ($row['has_feedback']): ?>
                                <span style="color:#e6b800;font-weight:bold;"><?= htmlspecialchars($row['rating']) ?></span>
                            <?php else: ?>
                                <span>—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:180px;word-break:break-all;">
                            <?php if ($row['has_feedback']): ?>
                                <?= nl2br(htmlspecialchars($row['comment'])) ?>
                            <?php else: ?>
                                <span>—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:180px;word-break:break-all;">
                            <?= $row['medication'] ? htmlspecialchars($row['medication']) : '<span>—</span>' ?>
                        </td>
                        <td style="max-width:180px;word-break:break-all;">
                            <?= $row['notes'] ? nl2br(htmlspecialchars($row['notes'])) : '<span>—</span>' ?>
                        </td>
                        <td>
                            <?= $row['has_feedback'] ? htmlspecialchars($row['submitted_at']) : '<span>—</span>' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (empty($feedbacks)): ?>
            <p style="text-align:center;color:#888;">目前沒有回饋或開藥資料。</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>看診日期</th>
                        <th>時段</th>
                        <th>病患</th>
                        <th>評分</th>
                        <th>留言</th>
                        <th>開藥內容</th>
                        <th>備註</th>
                        <th>填寫時間</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($feedbacks as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                        <td><?= htmlspecialchars($row['time_slot']) ?></td>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td>
                            <?php if ($row['has_feedback']): ?>
                                <span style="color:#e6b800;font-weight:bold;"><?= htmlspecialchars($row['rating']) ?></span>
                            <?php else: ?>
                                <span>—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:180px;word-break:break-all;">
                            <?php if ($row['has_feedback']): ?>
                                <?= nl2br(htmlspecialchars($row['comment'])) ?>
                            <?php else: ?>
                                <span>—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:180px;word-break:break-all;">
                            <?= $row['medication'] ? htmlspecialchars($row['medication']) : '<span>—</span>' ?>
                        </td>
                        <td style="max-width:180px;word-break:break-all;">
                            <?= $row['notes'] ? nl2br(htmlspecialchars($row['notes'])) : '<span>—</span>' ?>
                        </td>
                        <td>
                            <?= $row['has_feedback'] ? htmlspecialchars($row['submitted_at']) : '<span>—</span>' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div style="text-align:center; margin-top:2em;">
        <a href="/clinic/<?= $role ?>s/dashboard.php" class="button" style="max-width:200px;">🔙 回到主頁</a>
    </div>
</div>
<?php include("../footer.php"); ?>