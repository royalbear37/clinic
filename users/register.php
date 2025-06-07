<?php include("../header.php"); ?>

<h2>註冊新帳號</h2>

<form method="post" action="register_finish.php">
    姓名：<input type="text" name="name" required><br>
    身分證號碼：<input type="text" name="id_number" required><br>
    Email：<input type="email" name="email" required><br>
    密碼：<input type="password" name="pw" required><br>
    再次輸入密碼：<input type="password" name="pw2" required><br>

    角色：
    <select name="role" id="role-select" onchange="toggleDoctorFields()" required>
        <option value="patient">病患</option>
        <option value="doctor">醫師</option>
        <option value="admin">管理員</option>
    </select><br>

    <div id="doctor-fields" style="display:none; margin-top:10px;">
        <label>醫師科別：</label>
        <select name="department_id">
            <option value="101">眼科</option>
            <option value="102">耳鼻喉科</option>
            <option value="103">小兒科</option>
            <option value="104">皮膚科</option>
            <option value="105">骨科</option>
        </select><br>
    </div>

    <br><button type="submit">註冊</button>
</form>

<script>
function toggleDoctorFields() {
    const role = document.getElementById('role-select').value;
    document.getElementById('doctor-fields').style.display = (role === 'doctor') ? 'block' : 'none';
}
</script>

<?php include("../footer.php"); ?>
