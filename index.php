<?php include("header.php"); ?>

<div class="dashboard" style="text-align:center;">

    <div style="font-size:3.5em; margin-bottom:0.5em;">🩺</div>

    <h2>歡迎來到診所資訊系統</h2>

    <p style="font-size:1.15em; color:#bfae8a; margin-bottom:1.5em;">
        用心守護您的健康
    </p>

    <div style="max-width:520px; margin:0 auto 2em auto; color:#7a7a85; font-size:1.08em; line-height:1.8;">
        本系統提供線上預約掛號、就診紀錄查詢與個人健康管理等功能，<br>
        讓您輕鬆掌握醫療資訊，節省現場等候時間。 <br>
        <b style="color:#d4af37;">註冊會員</b>後即可享有完整服務，<b style="color:#d4af37;">登入</b>後可查詢您的預約與歷史紀錄。
    </div>

    <hr style="border: none; border-top: 1.5px solid #e5e1d8; margin:2em 0;">

    <div style="display:flex; flex-direction:column; gap:1.5em; align-items:center; margin:2em 0;">
        <a href="/clinic/users/login.php" class="button" style="max-width:220px;">
            <span style="font-size:1.5em;">🔑</span> 會員登入
        </a>
        <a href="/clinic/users/register.php" class="button" style="max-width:220px;">
            <span style="font-size:1.5em;">📝</span> 註冊新帳號
        </a>
    </div>

    <!-- YouTube 影片嵌入區 -->
    <div style="margin-top: 2em;">
        <iframe width="360" height="215" src="https://www.youtube.com/embed/djUkO0I37j8"
            title="YouTube video player" frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen></iframe>
    </div>

    <p style="color:#bfae8a; margin-top:2em;">© 2025 診所資訊系統</p>

</div>

<?php include("footer.php"); ?>