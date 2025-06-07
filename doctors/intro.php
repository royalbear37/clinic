
<?php
include("../config/mysql_connect.inc.php");
include("../header.php");

$doctor_id = intval($_GET['doctor_id'] ?? 0);

$stmt = $conn->prepare("SELECT d.doctor_id, u.name, d.department_id, d.intro, dep.name AS dept_name
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    JOIN departments dep ON d.department_id = dep.department_id
    WHERE d.doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if (!$doc) {
    echo "<div class='dashboard'><h2>查無此醫師</h2></div>";
} else {
?>
<div class="dashboard" style="max-width:600px;margin:40px auto;">
    <h2>👨‍⚕️ 醫師簡介</h2>
    <h3><?= htmlspecialchars($doc['name']) ?>（<?= htmlspecialchars($doc['dept_name']) ?>）</h3>
    <div style="margin:1em 0;">
        <?= nl2br(htmlspecialchars($doc['intro'] ?? '尚無簡介')) ?>
    </div>
    <a href="/clinic/doctors/intro.php" class="button">🔙 返回醫師列表</a>
</div>
<?php
}
include("../footer.php");
?>