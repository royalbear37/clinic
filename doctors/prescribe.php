<?php
session_start();
include("../config/mysql_connect.inc.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /clinic/users/login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? '';
if (!$appointment_id) {
    die("❌ 缺少 appointment_id");
}

$sql = "SELECT a.*, u.name AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.id
        WHERE a.appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();
$appointment = $res->fetch_assoc();
// 查詢現有處方（如果有）
$pres_sql = "SELECT medication, notes FROM prescriptions WHERE appointment_id = ?";
$stmt_pres = $conn->prepare($pres_sql);
$stmt_pres->bind_param("i", $appointment_id);
$stmt_pres->execute();
$pres_result = $stmt_pres->get_result();
$existing_prescription = $pres_result->fetch_assoc();

$previous_meds = [];
$previous_notes = '';

if ($existing_prescription) {
    $previous_meds = explode(', ', $existing_prescription['medication']);
    $previous_notes = $existing_prescription['notes'];
}


if (!$appointment) {
    die("❌ 找不到預約資料");
}

// ✅ 限制只能對「已完成」的預約開藥
if ($appointment['status'] !== 'completed') {
    echo "<script>
        alert('⚠️ 僅能對已完成的預約開立處方（目前狀態：" . addslashes($appointment['status']) . "）');
        history.back();
    </script>";
    exit();
}

?>
<style>
    input[type="checkbox"] {
        transform: scale(0.8);
        margin-left: 0.4em;
        margin-right: 0;
        accent-color: var(--accent);
        vertical-align: middle;
    }

    .med-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1em;
        margin: 0.5em 0;
        font-weight: 500;
        gap: 1em;
        padding: 0.2em 0;
        border-bottom: 1px dashed #ddd;
    }

    .med-label span {
        flex-grow: 1;
        text-align: left;
    }
</style>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>開立處方</title>
    <link rel="stylesheet" href="/clinic/style.css">
    <style>
        .test-box {
            background: red;
            color: white;
            padding: 1em;
            text-align: center;
        }
    </style>
</head>

<body>

    <main class="container">
        <div class="dashboard" style="max-width:600px; margin-top:60px;">
            <h2 style="text-align:center;">📝 為 <?= htmlspecialchars($appointment['patient_name']) ?> 開立處方</h2>
            <form method="POST" action="prescription_submit.php">
                <input type="hidden" name="appointment_id" value="<?= $appointment_id ?>">
                <input type="hidden" name="patient_id" value="<?= $appointment['patient_id'] ?>">

                <div class="form-group">
                    <label>選擇藥品：</label><br>

                    <table class="med-table">
                        <thead>
                            <tr>
                                <th style="width:60%;">藥品名稱</th>
                                <th style="width:40%;">選擇</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $med_sql = "SELECT med_id, name FROM medications";
                            $med_result = $conn->query($med_sql);
                            while ($med = $med_result->fetch_assoc()):
                                $checked = in_array($med['name'], $previous_meds) ? 'checked' : '';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($med['name']) ?></td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="medication[]" value="<?= htmlspecialchars($med['name']) ?>" <?= $checked ?>>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>



                <div class="form-group">
                    <label>備註：</label><br>
                    <textarea name="notes" rows="3" style="width:100%;"><?= htmlspecialchars($previous_notes) ?></textarea><br><br>

                </div>

                <div style="text-align:center;">
                    <button type="submit" class="button">✅ 提交處方</button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>