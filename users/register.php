<?php include("../header.php"); ?>

<h2>註冊新帳號</h2>

<form method="post" action="register_finish.php" onsubmit="return validateIdNumber();">
    姓名：<input type="text" name="name" required><br>
    身分證號碼：<input type="text" name="id_number" id="id_number" required><br>
    <span id="id_msg" style="color:red;margin-left:0;"></span><br>
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
function validateIdNumber() {
    const id = document.getElementById('id_number').value.trim().toUpperCase();
    const msg = document.getElementById('id_msg');
    msg.textContent = '';

    // 統一驗證條件
    if (!/^[A-Z][12]\d{8}$/.test(id)) {
        msg.textContent = "身分證格式錯誤";
        return false;
    }

    const letters = {
        A:10,B:11,C:12,D:13,E:14,F:15,G:16,H:17,I:34,J:18,K:19,L:20,M:21,N:22,O:35,
        P:23,Q:24,R:25,S:26,T:27,U:28,V:29,W:32,X:30,Y:31,Z:33
    };
    const code = letters[id[0]];
    if (!code) {
        msg.textContent = "身分證格式錯誤";
        return false;
    }

    const idArray = [Math.floor(code/10), code%10];
    for(let i=1; i<id.length; i++) idArray.push(parseInt(id[i]));
    const weights = [1,9,8,7,6,5,4,3,2,1,1];
    let sum = 0;
    for(let i=0; i<11; i++) sum += idArray[i]*weights[i];

    if (sum % 10 !== 0) {
        msg.textContent = "身分證格式錯誤";
        return false;
    }
    return true;
}
function toggleDoctorFields() {
    const role = document.getElementById('role-select').value;
    document.getElementById('doctor-fields').style.display = (role === 'doctor') ? 'block' : 'none';
}
</script>

<?php include("../footer.php"); ?>
