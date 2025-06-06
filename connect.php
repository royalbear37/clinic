<?php session_start(); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
include("mysql_connect.inc.php");

$id = $_POST['id'];
$pw = $_POST['pw'];

// 搜尋帳號
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && password_verify($pw, $row['password'])) {
    $_SESSION['user_id'] = $row['user_id'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['name'] = $row['name'];

    echo '登入成功！';

    // 根據角色導向
    if ($row['role'] == 'admin') {
        echo '<meta http-equiv=REFRESH CONTENT=1;url=admin/dashboard.php>';
    } elseif ($row['role'] == 'doctor') {
        echo '<meta http-equiv=REFRESH CONTENT=1;url=doctors/dashboard.php>';
    } elseif ($row['role'] == 'patient') {
        echo '<meta http-equiv=REFRESH CONTENT=1;url=patients/dashboard.php>';
    } else {
        echo '<meta http-equiv=REFRESH CONTENT=2;url=login.php>';
    }
} else {
    echo '登入失敗！';
    echo '<meta http-equiv=REFRESH CONTENT=1;url=login.php>';
}
?>
