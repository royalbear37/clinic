<?php
session_start();
include("../config/mysql_connect.inc.php");
include("../header.php");

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

// 更新醫師 profile 與圖片檔名（只存檔名，不處理檔案本身）
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_doctor_id'], $_POST['new_profile'])) {
    $doctor_uid = intval($_POST['update_doctor_id']);
    $new_profile = trim($_POST['new_profile']);
    $photo_url = trim($_POST['photo_url'] ?? "");

    if ($photo_url !== "") {
        $stmt_update = $conn->prepare("UPDATE doctors SET profile = ?, photo_url = ? WHERE user_id = ?");
        $stmt_update->bind_param("ssi", $new_profile, $photo_url, $doctor_uid);
    } else {
        $stmt_update = $conn->prepare("UPDATE doctors SET profile = ?, photo_url = NULL WHERE user_id = ?");
        $stmt_update->bind_param("si", $new_profile, $doctor_uid);
    }
    $stmt_update->execute();
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

<div class="dashboard" style="max-width:1000px;margin:40px auto;">
<h2>👤 使用者管理（含醫師簡介編輯）</h2>

<form method="get" style="margin-bottom: 15px;">
    <div style="margin-bottom:8px;">
        搜尋：<input type="text" name="search" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div style="margin-bottom:8px;">
        用戶：
        <select name="role">
            <option value="">全部</option>
            <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>管理員</option>
            <option value="doctor" <?= $filter_role === 'doctor' ? 'selected' : '' ?>>醫師</option>
            <option value="patient" <?= $filter_role === 'patient' ? 'selected' : '' ?>>病患</option>
        </select>
    </div>
    <button type="submit">🔍 查詢</button>
</form>

<table border="1" cellpadding="6" style="width:100%;text-align:center;">
    <tr>
        <th>姓名</th>
        <th>帳號 ID</th>
        <th>身份證號</th>
        <th>角色</th>
        <th>註冊時間</th>
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
            $doctor_sql = "SELECT profile, photo_url FROM doctors WHERE user_id = ?";
            $stmt_doc = $conn->prepare($doctor_sql);
            $stmt_doc->bind_param("i", $row['id']);
            $stmt_doc->execute();
            $res_doc = $stmt_doc->get_result();
            $doc = $res_doc->fetch_assoc();
            // 動態產生唯一 id
            $fid = 'photo_file_' . $row['id'];
            $tid = 'photo_url_' . $row['id'];
        ?>
        <tr>
            <td colspan="7" style="background:#f9f9f9;">
                <div style="max-width:600px;margin:32px auto 16px auto;padding:36px 32px 32px 32px;border-radius:18px;box-shadow:0 4px 24px #e0e0e0;background:#fff;">
                    <form method="post" id="doctor_edit_form_<?= $row['id'] ?>">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:18px;">
                            <div style="text-align:center;">
                                <?php if (!empty($doc['photo_url'])): ?>
                                    <img src="/clinic/uploads/<?= htmlspecialchars($doc['photo_url']) ?>" alt="醫師照片" style="width:180px;height:180px;object-fit:cover;border-radius:14px;border:2px solid #ddd;">
                                <?php else: ?>
                                    <div style="width:180px;height:180px;background:#eee;border-radius:14px;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:1.3em;">無照片</div>
                                <?php endif; ?>
                                <div style="margin-top:10px;">
                                    <input type="file" id="<?= $fid ?>" style="display:none;">
                                    <input type="text" name="photo_url" id="<?= $tid ?>" value="<?= htmlspecialchars($doc['photo_url'] ?? '') ?>" placeholder="doctor_xxx.jpg" style="width:180px;padding:8px 12px;border-radius:8px;border:1px solid #ccc;">
                                    <button type="button" onclick="document.getElementById('<?= $fid ?>').click();" style="margin-left:8px;background:#222;color:#fff;padding:8px 16px;border-radius:8px;border:none;font-size:1em;">選擇檔案</button>
                                </div>
                            </div>
                            <div style="width:100%;margin-top:18px;">
                                <label style="font-weight:bold;font-size:1.13em;letter-spacing:1px;display:block;margin-bottom:8px;">醫師簡介</label>
                                <textarea name="new_profile" rows="6" style="width:100%;padding:14px 12px;font-size:1.13em;border-radius:8px;border:1.5px solid #bfc9d1;background:#fff;resize:vertical;box-sizing:border-box;" placeholder="請輸入醫師簡介"><?= htmlspecialchars($doc['profile'] ?? '') ?></textarea>
                            </div>
                            <input type="hidden" name="update_doctor_id" value="<?= $row['id'] ?>">
                            <button type="submit" style="background:#2563eb;color:#fff;padding:12px 38px;border-radius:8px;border:none;font-size:1.13em;letter-spacing:1px;box-shadow:0 2px 8px #c7d2fe;transition:background 0.2s;">
                                更新
                            </button>
                        </div>
                    </form>
                    <script>
                    document.getElementById('<?= $fid ?>').addEventListener('change', function() {
                        if (this.files.length > 0) {
                            document.getElementById('<?= $tid ?>').value = this.files[0].name;
                        }
                    });
                    </script>
                </div>
            </td>
        </tr>
        <?php endif; ?>
    <?php endwhile; ?>
</table>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>
</div>
<?php include("../footer.php"); ?>
