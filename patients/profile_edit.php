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
}
?>

<h2>🧑 我的個人資料</h2>

<?php if ($message): ?>
    <p style="color:green;"><?= $message ?></p>
<?php endif; ?>

<form method="post">
    姓名：<input type="text" name="name" value="<?= htmlspecialchars($info['name']) ?>" required><br>
    電子郵件：<input type="email" name="email" value="<?= htmlspecialchars($info['email']) ?>" required><br>
    手機號碼：<input type="text" name="phone" value="<?= htmlspecialchars($info['phone']) ?>"><br>
    性別：
    <select name="gender">
        <option value="male" <?= $info['gender'] === 'male' ? 'selected' : '' ?>>男</option>
        <option value="female" <?= $info['gender'] === 'female' ? 'selected' : '' ?>>女</option>
        <option value="other" <?= $info['gender'] === 'other' ? 'selected' : '' ?>>其他</option>
    </select><br>
    出生日期：<input type="date" name="birthdate" value="<?= $info['birthdate'] ?>"><br><br>
    <button type="submit">儲存修改</button>
</form>

<p><a href="/clinic/patients/dashboard.php">🔙 回到主頁</a></p>