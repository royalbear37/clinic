<?php
// 測試輸入的原始密碼
$plain_password = 'password10'; // ← 你可以自行改成任意密碼

// 使用預設演算法 (目前是 bcrypt) 加密
$hash = password_hash($plain_password, PASSWORD_DEFAULT);

// 輸出原始密碼與加密後的哈希
echo "原始密碼：$plain_password<br>";
echo "加密後：$hash";
