<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: /clinic/users/login.php");
    exit();
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $src_date = $_POST['source_date'];
    $target_date = $_POST['target_date'];
    $force = isset($_POST['force']) ? true : false;

    // 計算目標週起始日
    $target_start = date("Y-m-d", strtotime("sunday last week", strtotime($target_date)));
    if (date("w", strtotime($target_date)) == 0) $target_start = $target_date;
    $target_end = date("Y-m-d", strtotime($target_start . " +6 days"));

    // 檢查目標週是否已有資料
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM schedules WHERE schedule_date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $target_start, $target_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['cnt'] > 0 && !$force) {
        // 有資料且未勾選覆蓋，顯示提示
        echo "<form method='post'>
            <input type='hidden' name='source_date' value='$src_date'>
            <input type='hidden' name='target_date' value='$target_date'>
            <input type='hidden' name='force' value='1'>
            <p style='color:red;font-weight:bold;'>目標週已有排班資料，是否要覆蓋？</p>
            <button type='submit'>確認覆蓋並複製</button>
            <a href='schedule_copy_week.php'>取消</a>
        </form>";
        exit();
    }

    // 若有 force，先刪除目標週資料
    if ($force) {
        $stmt = $conn->prepare("DELETE FROM schedules WHERE schedule_date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $target_start, $target_end);
        $stmt->execute();
    }

    // 自動計算週日為起點
    $src_start = date("Y-m-d", strtotime("sunday last week", strtotime($src_date)));
    if (date("w", strtotime($src_date)) == 0) $src_start = $src_date;

    $src_end = date("Y-m-d", strtotime($src_start . " +6 days"));

    for ($i = 0; $i < 7; $i++) {
        $src_day = date("Y-m-d", strtotime("+{$i} day", strtotime($src_start)));
        $tgt_day = date("Y-m-d", strtotime("+{$i} day", strtotime($target_start)));

        $stmt = $conn->prepare("SELECT doctor_id, shift, is_available, note FROM schedules WHERE schedule_date = ?");
        $stmt->bind_param("s", $src_day);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $stmt_insert = $conn->prepare("INSERT INTO schedules (doctor_id, schedule_date, shift, is_available, note) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("issis", $row['doctor_id'], $tgt_day, $row['shift'], $row['is_available'], $row['note']);
            $stmt_insert->execute();
        }
    }
    $msg = "✅ 複製完成：{$src_start} ~ {$src_end} → 起始於 {$target_start}";
}
?>

<h2>📆 複製一週排班（按班別）</h2>

<?php if ($msg) echo "<p style='color:green; font-weight:bold;'>$msg</p>"; ?>

<form method="post">
    來源週任一天：<input type="date" name="source_date" required><br>
    目標週任一天：<input type="date" name="target_date" required><br>
    <input type="checkbox" name="force" value="1"> 強制覆蓋目標週資料<br><br>
    <button type="submit">執行複製</button>
</form>

<p><a href="/clinic/admins/dashboard.php">🔙 回到主頁</a></p>