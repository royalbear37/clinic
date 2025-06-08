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
                session_start();
                if ($_SERVER["REQUEST_METHOD"] === "POST") {
                    if (!isset($_POST['captcha_input']) || $_POST['captcha_input'] !== $_SESSION['captcha']) {
                        die("❌ 驗證碼錯誤，請重新輸入。");
                    }

                    // ...這裡才是驗證帳號密碼的邏輯
                }

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
<div class="login-wrapper">
    <form method="post" action="login.php">
        <div class="form-group">
            <label for="id_number">身分證號碼：</label>
            <input type="text" name="id_number" id="id_number" required>
        </div>

        <div class="form-group">
            <label for="pw">密碼：</label>
            <input type="password" name="pw" id="pw" required>
        </div>

        <div class="form-group">
            <label for="role">登入身份：</label>
            <select name="role" id="role" required>
                <option value="patient">病患</option>
                <option value="doctor">醫師</option>
                <option value="admin">管理員</option>
            </select>
        </div>

        <div class="form-group">
            <label for="captcha_input">驗證碼：</label>
            <div class="verification-wrapper">
                <img src="captcha.php" alt="驗證碼圖片">
                <input type="text" name="captcha_input" id="captcha_input" required placeholder="請輸入上方驗證碼">
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="button" id="login-button">登入</button>
        </div>
    </form>
</div>


<p>還沒有帳號？<a href="register.php">註冊</a></p>

<?php include("../footer.php"); // 新增這行 
?>