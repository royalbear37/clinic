<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include("../../config/mysql_connect.inc.php");

$input = $_POST['question'] ?? '';

$keywords = json_decode(file_get_contents("../data/department_keywords.json"), true);
if (!$keywords) {
    echo json_encode(["message" => "❌ 無法讀取科別關鍵字對照表"]);
    exit;
}

// 尋找對應科別
$matchedDept = null;
foreach ($keywords as $dept => $words) {
    foreach ($words as $word) {
        if (mb_strpos($input, $word) !== false) {
            $matchedDept = $dept;
            break 2;
        }
    }
}

if (!$matchedDept) {
    echo json_encode(["message" => "❌ 無法判斷問題所屬科別，請稍微描述詳細一點。"]);
    exit;
}

// 找到對應科別 ID
$stmt = $conn->prepare("SELECT department_id FROM departments WHERE name = ?");
$stmt->bind_param("s", $matchedDept);
$stmt->execute();
$stmt->bind_result($dept_id);
$stmt->fetch();
$stmt->close();

if (!$dept_id) {
    echo json_encode(["message" => "❌ 找不到科別 ID"]);
    exit;
}

// 查找醫師名稱
$sql = "SELECT u.name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$res = $stmt->get_result();

$names = [];
while ($row = $res->fetch_assoc()) {
    $names[] = $row['name'];
}

if (empty($names)) {
    echo json_encode(["message" => "目前「$matchedDept」沒有可推薦的醫師"]);
} else {
    echo json_encode(["message" => "建議掛號「$matchedDept」，推薦醫師：" . implode("、", $names)]);
}
