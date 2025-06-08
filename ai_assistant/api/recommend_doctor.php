<?php
include("mysql_connect.inc.php");

$input = $_GET['q'] ?? '';

$specialty_keywords = [
    '眼' => 101,
    '視力' => 101,
    '鼻' => 102,
    '耳' => 102,
    '喉嚨' => 102,
    '小孩' => 103,
    '發燒' => 103,
    '皮膚' => 104,
    '濕疹' => 104,
    '關節' => 105,
    '骨折' => 105,
];

function recommend_department($input, $keywords)
{
    foreach ($keywords as $k => $v) {
        if (mb_strpos($input, $k) !== false) return $v;
    }
    return null;
}

$dept_id = recommend_department($input, $specialty_keywords);

if ($dept_id === null) {
    echo json_encode(["message" => "❌ 無法根據輸入判斷對應科別"]);
    exit;
}

// 查詢該科別的醫師
$sql = "SELECT d.doctor_id, u.name AS doctor_name, d.profile, d.photo_url
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        WHERE d.department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$result = $stmt->get_result();

$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

echo json_encode([
    "department_id" => $dept_id,
    "doctors" => $doctors
]);
