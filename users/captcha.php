<?php
session_start();
header("Content-type: image/png");

// 產生驗證碼
$code = strval(rand(1000, 9999));
$_SESSION["captcha"] = $code;

// 建立圖片
$image = imagecreate(120, 48);
$bg = imagecolorallocate($image, 255, 255, 255); // 白底
$text_color = imagecolorallocate($image, 0, 0, 0); // 黑字

imagestring($image, 5, 36, 16, $code, $text_color);
imagepng($image);
imagedestroy($image);
