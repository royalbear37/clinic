<?php
// 顯示錯誤訊息，方便除錯
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
include("../config/mysql_connect.inc.php");
include("../header.php"); // 加在最前面

$pw = $_POST['pw'];
$pw2 = $_POST['pw2'];
$email = $_POST['email'];
$name = $_POST['name'];
$id_number = $_POST['id_number'];
$role = $_POST['role']; // 'admin', 'doctor', 'patient'

if ($pw && $pw2 && $pw == $pw2) {
    // 自動產生 user_id
    $prefix = '';
    switch ($role) {
        case 'patient': $prefix = 'PAT'; break;
        case 'doctor':  $prefix = 'DOC'; break;
        case 'admin':   $prefix = 'ADM'; break;
        default:
            die('錯誤：角色無效');
    }

    // 查詢目前最大 user_id
    $like = $prefix . '%';
    $sql = "SELECT user_id FROM users WHERE role = ? AND user_id LIKE ? ORDER BY user_id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("prepare 錯誤：" . $conn->error);
    $stmt->bind_param("ss", $role, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest = $result->fetch_assoc();
    $num = $latest ? intval(substr($latest['user_id'], 3)) + 1 : 1;
    $user_id = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);

    // 密碼雜湊
    $hash_pw = password_hash($pw, PASSWORD_DEFAULT);

    // 寫入 users 表
    $stmt = $conn->prepare("INSERT INTO users (user_id, id_number, password, role, name, email) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) die("prepare users 錯誤：" . $conn->error);
    $stmt->bind_param("ssssss", $user_id, $id_number, $hash_pw, $role, $name, $email);

    if ($stmt->execute()) {
        $uid = $stmt->insert_id; // 這是 users.id (int)

        if ($role == 'patient') {
            $insert = $conn->prepare("INSERT INTO patients (user_id, id_number) VALUES (?, ?)");
            if (!$insert) die("prepare patients 錯誤：" . $conn->error);
            $insert->bind_param("is", $uid, $id_number);
            $insert->execute();
        }

        if ($role == 'doctor') {
            $department_id = $_POST['department_id'] ?? 101;
            $profile = '暫無簡介';
            $photo_url = 'default.jpg';
            $is_active = 'yes';
            $insert = $conn->prepare("INSERT INTO doctors (user_id, department_id, profile, is_active, photo_url) VALUES (?, ?, ?, ?, ?)");
            if (!$insert) die("prepare doctors 錯誤：" . $conn->error);
            $insert->bind_param("iisss", $uid, $department_id, $profile, $is_active, $photo_url);
            $insert->execute();
        }

        if ($role == 'admin') {
            $insert = $conn->prepare("INSERT INTO admins (user_id) VALUES (?)");
            if (!$insert) die("prepare admins 錯誤：" . $conn->error);
            $insert->bind_param("i", $uid);
            $insert->execute();
        }

        echo "<div class='success'>✅ 註冊成功！系統帳號為：<b>$user_id</b></div>";
        echo '<meta http-equiv=REFRESH CONTENT=3;url=login.php>';
    } else {
        echo "<div class='error'>❌ 註冊失敗：" . $stmt->error . "</div>";
        echo '<meta http-equiv=REFRESH CONTENT=3;url=register.php>';
    }
} else {
    echo "<div class='error'>⚠️ 密碼不一致或欄位缺漏</div>";
    echo '<meta http-equiv=REFRESH CONTENT=3;url=register.php>';
}

include("../footer.php"); // 加在最後面
?>
