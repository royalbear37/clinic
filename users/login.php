<?php
session_start();
include("../config/mysql_connect.inc.php");
include("../header.php"); // 新增這行

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_number = $_POST['id_number'];
    $password = $_POST['pw'];
    $role = $_POST['role']; // 應為 'patient' / 'doctor' / 'admin'

    // 先查該身份證是否存在
    $checkStmt = $conn->prepare("SELECT id, user_id, password, name, role FROM users WHERE id_number = ? AND role = ?");
    $checkStmt->bind_param("ss", $id_number, $role);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkRow = $checkResult->fetch_assoc()) {
        // 身分證存在 → 檢查角色是否符合
        if ($checkRow['role'] === $role) {
            // 再檢查密碼
            if (password_verify($password, $checkRow['password'])) {
                $_SESSION['uid'] = $checkRow['id'];
                $_SESSION['user_id'] = $checkRow['user_id'];
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $checkRow['name'];

                header("Location: /clinic/{$role}s/dashboard.php");
                exit();
            } else {
                $error = "❌ 密碼錯誤，請重新輸入。";
            }
        } else {
            $error = "❌ 此身份證號對應的角色不是「{$role}」。";
        }
    } else {
        $error = "❌ 尚未註冊此身份證號碼。";
    }
}
?>

<link rel="stylesheet" href="/clinic/style.css"> 

<h2>診所系統登入</h2>

<?php if (!empty($error)) echo "<p style='color:red; font-weight:bold;'>$error</p>"; ?>

<form method="post" action="login.php">
    身分證號碼：<input type="text" name="id_number" required><br>
    密碼：<input type="password" name="pw" required><br>
    登入身份：
    <select name="role" required>
        <option value="patient">病患</option>
        <option value="doctor">醫師</option>
        <option value="admin">管理員</option>
    </select><br><br>
    <button type="submit">登入</button>
</form>

<p>還沒有帳號？<a href="register.php">註冊</a></p>

<?php include("../footer.php"); // 新增這行 ?>
