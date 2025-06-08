<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
include("../config/mysql_connect.inc.php");

$API_KEY = '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'; // ✅ 你自己的金鑰
$data = json_decode(file_get_contents("php://input"), true);
$user_input = $data['message'] ?? '';

// ✅ 對應關鍵字到科別
$specialty_keywords = [
    // 眼科
    '眼' => 101,
    '視力' => 101,
    '模糊' => 101,
    '流淚' => 101,
    '乾澀' => 101,
    '紅血絲' => 101,
    // 耳鼻喉科
    '鼻' => 102,
    '鼻塞' => 102,
    '鼻涕' => 102,
    '打噴嚏' => 102,
    '喉嚨' => 102,
    '耳' => 102,
    '咳嗽' => 102,
    '感冒' => 102,
    '發燒' => 102,
    // 小兒科
    '小孩' => 103,
    '兒子' => 103,
    '女兒' => 103,
    '兒童' => 103,
    // 皮膚科
    '皮膚' => 104,
    '濕疹' => 104,
    '癢' => 104,
    '疹子' => 104,
    '痘痘' => 104,
    // 骨科
    '關節' => 105,
    '骨折' => 105,
    '背痛' => 105,
    '腰痛' => 105,
    '膝蓋痛' => 105,
    '腳痛' => 105,
];

// ✅ 比對症狀關鍵字對應科別
$department_id = null;
foreach ($specialty_keywords as $keyword => $dept_id) {
    if (mb_strpos($user_input, $keyword) !== false) {
        $department_id = $dept_id;
        break;
    }
}

// ✅ 若有命中關鍵字，就推薦科別與醫師
if ($department_id !== null) {
    $stmt = $conn->prepare("
        SELECT u.name AS doctor_name, dp.name AS department_name, d.profile
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        JOIN departments dp ON d.department_id = dp.department_id
        WHERE d.department_id = ?
    ");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $reply = "根據您的症狀，建議就診以下科別與醫師：\n";
    $first = true;
    while ($row = $res->fetch_assoc()) {
        if (!$first) {
            $reply .= "-----------------------------------------\n";
        }
        $reply .= "👨‍⚕️ 醫師姓名：{$row['doctor_name']}\n";
        $reply .= "🏥 科別：{$row['department_name']}\n";
        $reply .= "📌 {$row['profile']}\n";
        $first = false;
    }

    sleep(2); // ⏳ 模擬 AI 思考延遲
    echo json_encode(["reply" => nl2br($reply)]);
    exit;
}

// ❌ 沒命中關鍵字 → 再進 GPT 處理
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => '你是醫院小幫手，告訴使用者剛剛提出需要的服務我們醫院目前沒有適合的醫生，但你還是給於一些建議。'],
        ['role' => 'user', 'content' => $user_input]
    ]
]));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $API_KEY"
]);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["reply" => "❌ cURL 錯誤：" . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$json = json_decode($response, true);
if (!isset($json['choices'][0]['message']['content'])) {
    $error = $json['error']['message'] ?? '未知錯誤';
    echo json_encode(["reply" => "⚠️ OpenAI API 錯誤：" . $error]);
    exit;
}

$reply = $json['choices'][0]['message']['content'];
echo json_encode(["reply" => nl2br($reply)]);
