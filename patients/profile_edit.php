<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'patient') {
    header("Location: /clinic/users/login.php");
    exit();
}

$uid = $_SESSION['uid'];
$message = "";

// 取得資料
$sql = "SELECT u.name, u.email, p.phone, p.gender, p.birthdate
        FROM users u
        JOIN patients p ON u.id = p.user_id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$info = $result->fetch_assoc();

if (!$info) {
    echo "❌ 找不到病患資料。";
    exit();
}

// 若表單送出
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';

    // 更新 users
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $uid);
    $stmt->execute();

    // 更新 patients
    $stmt = $conn->prepare("UPDATE patients SET phone = ?, gender = ?, birthdate = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $phone, $gender, $birthdate, $uid);
    $stmt->execute();

    $message = "✅ 資料更新成功！";

    // 重新查詢最新資料
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
}
?>

<?php include("../header.php"); ?>
<div class="dashboard" style="max-width:480px;margin:40px auto;">
    <h2 style="text-align:center;">🧑 我的個人資料</h2>

    <?php if ($message): ?>
        <p class="success"><?= $message ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>姓名：</label>
            <input type="text" name="name" value="<?= htmlspecialchars($info['name']) ?>" required>
        </div>
        <div class="form-group">
            <label>電子郵件：</label>
            <input type="email" name="email" value="<?= htmlspecialchars($info['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>手機號碼：</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($info['phone']) ?>">
        </div>
        <div class="form-group">
            <label>性別：</label>
            <select name="gender">
                <option value="male" <?= $info['gender'] === 'male' ? 'selected' : '' ?>>男</option>
                <option value="female" <?= $info['gender'] === 'female' ? 'selected' : '' ?>>女</option>
                <option value="other" <?= $info['gender'] === 'other' ? 'selected' : '' ?>>其他</option>
            </select>
        </div>
        <div class="form-group">
            <label>出生日期：</label>
            <input type="date" name="birthdate" value="<?= $info['birthdate'] ?>">
        </div>
        <button type="submit" class="button">儲存修改</button>
    </form>

    <p style="text-align:center; margin-top:2em;">
        <a href="/clinic/patients/dashboard.php" class="button" style="max-width:180px;">🔙 回到主頁</a>
    </p>
</div>
<?php include("../footer.php"); ?>