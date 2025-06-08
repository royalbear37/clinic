<?php
session_start();
include("../config/mysql_connect.inc.php");
include("../header.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

// 取得所有科別
$dept_res = $conn->query("SELECT department_id, name FROM departments ORDER BY department_id");
$departments = [];
while ($row = $dept_res->fetch_assoc()) {
    $departments[$row['department_id']] = $row['name'];
}

// 篩選條件
$filter_dept = isset($_GET['department_id']) ? $_GET['department_id'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// 查詢所有預約資料（加上條件）
$where = [];
$params = [];
$types = '';

if ($filter_dept !== '') {
    $where[] = "d.department_id = ?";
    $params[] = $filter_dept;
    $types .= 'i';
}
if ($filter_date !== '') {
    $where[] = "a.appointment_date = ?";
    $params[] = $filter_date;
    $types .= 's';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT a.*, u.name AS doctor_name, d.name AS department, p.name AS patient_name
        FROM appointments a
        JOIN doctors doc ON a.doctor_id = doc.doctor_id
        JOIN users u ON doc.user_id = u.id
        JOIN departments d ON doc.department_id = d.department_id
        JOIN patients pa ON a.patient_id = pa.patient_id
        JOIN users p ON pa.user_id = p.id
        $where_sql
        ORDER BY a.appointment_date DESC, a.time_slot";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 取得所有有預約的日期（供下拉選單用）
$date_res = $conn->query("SELECT DISTINCT appointment_date FROM appointments ORDER BY appointment_date DESC");
$dates = [];
while ($row = $date_res->fetch_assoc()) {
    $dates[] = $row['appointment_date'];
}
?>

<div class="dashboard" style="max-width:1100px;margin:40px auto;">
    <h2 style="text-align:center;">📋 所有預約紀錄</h2>
    <div style="background:#fff;padding:32px 32px 24px 32px;border-radius:16px;box-shadow:0 2px 12px #eee;">
        <!-- 篩選表單 -->
        <form method="get" style="margin-bottom:24px;display:flex;flex-direction:column;align-items:center;gap:18px;">
            <div style="display:flex;align-items:center;gap:18px;">
                <label>科別：</label>
                <select name="department_id" style="padding:6px 16px;font-size:1em;">
                    <option value="">全部</option>
                    <?php foreach ($departments as $id => $name): ?>
                        <option value="<?= $id ?>" <?= $filter_dept == $id ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <label style="margin-left:16px;">日期：</label>
                <select name="date" style="padding:6px 16px;font-size:1em;">
                    <option value="">全部</option>
                    <?php foreach ($dates as $date): ?>
                        <option value="<?= $date ?>" <?= $filter_date == $date ? 'selected' : '' ?>><?= $date ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="button" style="width:180px;font-size:1.08em;">查詢</button>
        </form>
        <div style="overflow-x:auto;">
            <table class="table" style="width:100%;border-collapse:collapse;background:#fffdfa;">
                <thead>
                    <tr style="background: #f7f5f2; color: #23272f;">
                        <th>日期</th>
                        <th>時段</th>
                        <th>醫師</th>
                        <th>科別</th>
                        <th>病患</th>
                        <th>服務類型</th>
                        <th>狀態</th>
                        <th>看診序號</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="text-align:center;">
                            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                            <td><?= htmlspecialchars($row['time_slot']) ?></td>
                            <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= htmlspecialchars($row['service_type']) ?></td>
                            <td>
                                <?php
                                if ($row['status'] === 'scheduled') echo '<span style="color:#227d3b;">預約中</span>';
                                elseif ($row['status'] === 'checked_in') echo '<span style="color:#2b6cb0;">已報到</span>';
                                elseif ($row['status'] === 'completed') echo '<span style="color:#555;">已完成</span>';
                                elseif ($row['status'] === 'no-show') echo '<span style="color:#a94442;">未到</span>';
                                elseif ($row['status'] === 'cancelled') echo '<span style="color:#a94442;">已取消</span>';
                                else echo htmlspecialchars($row['status']);
                                ?>
                            </td>
                            <td><?= htmlspecialchars($row['visit_number']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align:center; margin-top:2em;">
            <a href="/clinic/admins/dashboard.php" class="button" style="max-width:200px;">🔙 回到管理首頁</a>
        </div>
    </div>
</div>
<?php include("../footer.php"); ?>