<?php
include("../config/mysql_connect.inc.php");
include("../header.php");
?>
<style>
.dept-list {
    display: flex;
    flex-wrap: wrap;
    gap: 2em;
    justify-content: center;
    margin: 40px 0 30px 0;
}
.dept-item {
    background: #f8f6f2;
    border-radius: 10px;
    padding: 2em 2.5em;
    font-size: 1.3em;
    box-shadow: 0 1px 6px #eee;
    text-align: center;
    min-width: 140px;
    transition: box-shadow 0.2s;
}
.dept-item a {
    text-decoration: none;
    color: #3a6ea5;
    font-weight: bold;
    display: block;
}
.dept-item a:hover {
    color: #d4af37;
}
.doctor-list-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto 30px auto;
    background: #fff;
    box-shadow: 0 2px 12px #eee;
    font-size: 1.08em;
}
.doctor-list-table th, .doctor-list-table td {
    border: 1px solid #e5e1d8;
    padding: 12px 8px;
}
.doctor-list-table th {
    background: #f8f6f2;
    color: #7a7a85;
    font-weight: bold;
}
.doctor-list-table tr:nth-child(even) {
    background: #faf9f7;
}
.doctor-list-table tr:hover {
    background: #f0f7ff;
}
.doctor-profile {
    text-align: left;
    color: #555;
    line-height: 1.7;
    max-width: 350px;
    margin: 0 auto;
}
.dashboard h2 {
    color: #3a6ea5;
    margin-bottom: 1em;
}
</style>
<div class="dashboard" style="max-width:900px;margin:40px auto;">
    <h2>👨‍⚕️ 醫師資訊查詢</h2>
<?php
$dep_id = isset($_GET['dep']) ? intval($_GET['dep']) : 0;

if (!$dep_id) {
    // 顯示五個科別按鈕
    $deps = $conn->query("SELECT department_id, name FROM departments ORDER BY department_id");
    echo '<div class="dept-list">';
    while ($dep = $deps->fetch_assoc()) {
        echo '<div class="dept-item"><a href="?dep=' . $dep['department_id'] . '">' . htmlspecialchars($dep['name']) . '</a></div>';
    }
    echo '</div>';
    echo '<div style="text-align:center;color:#888;">請點選科別以瀏覽該科醫師</div>';
} else {
    // 顯示該科別的醫師
    $dep_stmt = $conn->prepare("SELECT name FROM departments WHERE department_id=?");
    $dep_stmt->bind_param("i", $dep_id);
    $dep_stmt->execute();
    $dep_stmt->bind_result($dep_name);
    $dep_stmt->fetch();
    $dep_stmt->close();

    echo '<h3 style="text-align:center;">科別：' . htmlspecialchars($dep_name) . '</h3>';

    $sql = "SELECT d.doctor_id, u.name, d.profile
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dep_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<table class="doctor-list-table">';
    echo '<tr><th>姓名</th><th>簡介</th></tr>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td class="doctor-profile">' . nl2br(htmlspecialchars($row['profile'] ?? '尚無簡介')) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<div style="text-align:center;margin-top:2em;"><a href="intro.php" style="color:#3a6ea5;">← 返回科別列表</a></div>';
}
?>
</div>
<?php include("../footer.php"); ?>