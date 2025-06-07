<?php
header("Content-Type: application/json");

$API_KEY = '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';

$data = json_decode(file_get_contents("php://input"), true);
$user_input = $data['message'] ?? '';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => '你是醫院小幫手，幫助使用者了解門診、預約與科別。'],
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
