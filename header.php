<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>診所系統</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/clinic/style.css">
</head>

<body>
    <header>
        <div class="container" style="display:flex;align-items:center;justify-content:flex-start;">
            <!-- 左側LOGO，請將logo.png放在/clinic/images/logo.png -->
            <a href="/clinic/index.php" style="display:flex;align-items:center;margin-right:18px;">
                <img src="/clinic/logo.png" alt="醫院LOGO" style="height:100px;vertical-align:middle;">
            </a>
            <h1 style="margin:0 24px 0 0;flex-shrink:0;">診所資訊系統</h1>
            <nav class="nav-bar" style="flex:1;">
                <a href="/clinic/index.php">🏠 首頁</a>
                <a href="/clinic/about.php">🏥 關於醫院</a>
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
                <a href="/clinic/users/logout.php" style="margin-left:24px;color:#fff;background:#d9534f;padding:8px 18px;border-radius:4px;text-decoration:none;display:inline-block;">
                    🚪 登出
                </a>
            <?php endif; ?>
        </div>
    </header>
    <main>
