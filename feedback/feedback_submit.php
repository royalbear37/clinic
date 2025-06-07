<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$appointment_id = $_POST['appointment_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = $_POST['comment'] ?? "";

$msg = "";
$msg_type = "";

if (!$appointment_id || !$rating) {
    $msg = "❌ 請填寫必要欄位（預約與評分）。";
    $msg_type = "error";
} else {
    // 寫入 feedback
    $stmt = $conn->prepare("INSERT INTO feedback (appointment_id, rating, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $appointment_id, $rating, $comment);

    if ($stmt->execute()) {
        $msg = "✅ 感謝您的回饋！";
        $msg_type = "success";
    } else {
        $msg = "❌ 寫入失敗：" . $stmt->error;
        $msg_type = "error";
    }
}
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:480px;margin:40px auto;text-align:center;">
    <h2>回饋結果</h2>
    <?php if ($msg): ?>
        <p class="<?= $msg_type ?>"><?= $msg ?></p>
    <?php endif; ?>

    <div style="margin:2em 0;">
        <a href="new_feedback.php" class="button" style="max-width:200px;">繼續填寫</a>
        <a href="/clinic/patients/dashboard.php" class="button" style="max-width:200px;">返回主頁</a>
    </div>
</div>
<?php include("../footer.php"); ?>