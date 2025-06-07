<?php include("header.php"); ?>
<div class="dashboard" style="text-align:center;">
    <h2>🏥 歡迎來到診所資訊系統</h2>
    <p>請選擇您的身份進行操作：</p>
    <div style="display:flex; flex-direction:column; gap:1.5em; align-items:center; margin:2em 0;">
        <a href="/clinic/users/login.php" class="dashboard-menu" style="width:220px;">
            <span style="font-size:1.5em;">🔑</span> 會員登入
        </a>
        <a href="/clinic/users/register.php" class="dashboard-menu" style="width:220px;">
            <span style="font-size:1.5em;">📝</span> 註冊新帳號
        </a>
    </div>
    <p style="color:#6e6e73; margin-top:2em;">© 2025 診所資訊系統</p>
</div>
<?php include("footer.php"); ?>