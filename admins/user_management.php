<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 刪除帳號處理
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        if ($user['role'] === 'patient') $conn->query("DELETE FROM patients WHERE user_id = $delete_id");
        elseif ($user['role'] === 'doctor') $conn->query("DELETE FROM doctors WHERE user_id = $delete_id");
        elseif ($user['role'] === 'admin') $conn->query("DELETE FROM admins WHERE user_id = $delete_id");
        $conn->query("DELETE FROM users WHERE id = $delete_id");
    }
}


// 更新醫師 profile
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_doctor_id'], $_POST['new_profile'])) {
    $doctor_uid = intval($_POST['update_doctor_id']);
    $new_profile = trim($_POST['new_profile']);
    $stmt = $conn->prepare("UPDATE doctors SET profile = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_profile, $doctor_uid);
    $stmt->execute();
}

// 搜尋 + 篩選
$search = $_GET['search'] ?? '';
$filter_role = $_GET['role'] ?? '';
$conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $conditions[] = "(name LIKE ? OR id_number LIKE ? OR user_id LIKE ?)";
    $like = "%{$search}%";
    $params[] = &$like;
    $params[] = &$like;
    $params[] = &$like;
    $types .= "sss";
}
if (!empty($filter_role)) {
    $conditions[] = "role = ?";
    $params[] = &$filter_role;
    $types .= "s";
}
$where = "";
if ($conditions) {
    $where = "WHERE " . implode(" AND ", $conditions);
}

$sql = "SELECT id, user_id, name, id_number, role, created_at FROM users $where ORDER BY role, created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>👤 使用者管理（含醫師簡介編輯）</h2>

<form method="get" style="margin-bottom: 15px;">
    搜尋：<input type="text" name="search" value="<?= htmlspecialchars($search) ?>">
    角色：
    <select name="role">
        <option value="">全部</option>
        <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>管理員</option>
        <option value="doctor" <?= $filter_role === 'doctor' ? 'selected' : '' ?>>醫師</option>
        <option value="patient" <?= $filter_role === 'patient' ? 'selected' : '' ?>>病患</option>
    </select>
    <button type="submit">🔍 查詢</button>
</form>

<table border="1" cellpadding="6">
    <tr>
        <th>姓名</th>
        <th>帳號 ID</th>
        <th>身份證號</th>
        <th>角色</th>
        <th>註冊時間</th>
        <th>編輯</th>
        <th>刪除</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <form method="post">
                <td><?= htmlspecialchars($row["name"]) ?></td>
                <td><?= $row['user_id'] ?></td>
                <td><?= $row['id_number'] ?></td>
                <td><?= $row['role'] ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                    —
                </td>
            </form>
            <td>
                <?php if ($row['id'] != $_SESSION['uid']): ?>
                    <form method="post" onsubmit="return confirm('確定要刪除此使用者？');">
                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                        <button type="submit">🗑️ 刪除</button>
                    </form>
                <?php else: ?>
                    本人
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($row['role'] === 'doctor'):
            $doctor_sql = "SELECT profile FROM doctors WHERE user_id = ?";
            $stmt = $conn->prepare($doctor_sql);
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $res = $stmt->get_result();
            $doc = $res->fetch_assoc();
        ?>
        <tr>
            <td colspan="7">
                <form method="post">
                    醫師簡介：<br>
                    <textarea name="new_profile" rows="2" cols="100"><?= htmlspecialchars($doc['profile'] ?? '') ?></textarea><br>
                    <input type="hidden" name="update_doctor_id" value="<?= $row['id'] ?>">
                    <button type="submit">✏️ 更新簡介</button>
                </form>
            </td>
        </tr>
        <?php endif; ?>
    <?php endwhile; ?>
</table>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>