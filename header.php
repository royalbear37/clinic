<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__ . "/config/mysql_connect.inc.php");

// 取得所有科別與醫師
$departments = [];
$dept_rs = $conn->query("SELECT * FROM departments ORDER BY department_id");
while ($dept = $dept_rs->fetch_assoc()) {
    $departments[$dept['department_id']] = [
        'name' => $dept['name'],
        'doctors' => []
    ];
}
$doc_rs = $conn->query("SELECT d.doctor_id, d.department_id, u.name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY d.department_id, u.name");
while ($doc = $doc_rs->fetch_assoc()) {
    $departments[$doc['department_id']]['doctors'][] = [
        'doctor_id' => $doc['doctor_id'],
        'name' => $doc['name']
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>診所系統</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/clinic/style.css">
    <style>
    /* 多層下拉選單樣式 */
    .nav-bar { position: relative; }
    .dropdown, .dropdown-sub { position: relative; display: inline-block; }
    .dropdown-content, .dropdown-sub-content {
        display: none;
        position: absolute;
        left: 0;
        top: 100%;
        background: #fff;
        min-width: 160px;
        box-shadow: 0 2px 8px #ccc;
        z-index: 99;
        padding: 0;
        margin: 0;
    }
    .dropdown:hover > .dropdown-content { display: block; }
    .dropdown-content > li { list-style: none; }
    .dropdown-sub:hover > .dropdown-sub-content { display: block; left: 100%; top: 0; }
    .dropdown-content a, .dropdown-sub-content a {
        display: block;
        padding: 8px 16px;
        color: #222;
        text-decoration: none;
        white-space: nowrap;
    }
    .dropdown-content a:hover, .dropdown-sub-content a:hover { background: #f0f0f0; }
    </style>
</head>
<body>
    <header>
        <div class="container" style="display:flex;align-items:center;">
            <!-- LOGO，請將 logo.png 放在 /clinic/images/logo.png -->
            <a href="/clinic/index.php" style="display:inline-block;margin-right:18px;">
                <img src="/clinic/images/logo.jpg" alt="醫院LOGO" style="height:48px;vertical-align:middle;">
            </a>
            <h1 style="margin:0 24px 0 0;flex-shrink:0;">診所資訊系統</h1>
            <nav class="nav-bar" style="flex:1;">
                <a href="/clinic/index.php">🏠 首頁</a>
                <a href="/clinic/front_page/about.php">🏥 關於醫院</a>
                <a href="/clinic/doctors/intro.php">👨‍⚕️ 醫師簡介</a>
                <?php if (isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="/clinic/admins/dashboard.php">🛠️ 管理員首頁</a>
                    <?php elseif ($_SESSION['role'] === 'doctor'): ?>
                        <a href="/clinic/doctors/dashboard.php">🩺 醫師首頁</a>
                    <?php elseif ($_SESSION['role'] === 'patient'): ?>
                        <a href="/clinic/patients/dashboard.php">👤 病患首頁</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
            <?php if (isset($_SESSION['uid'])): ?>
                <a href="/clinic/users/logout.php" style="margin-left:auto;color:#fff;background:#d9534f;padding:8px 18px;border-radius:4px;text-decoration:none;display:inline-block;">
                    🚪 登出
                </a>
            <?php endif; ?>
        </div>
    </header>
    <main>
