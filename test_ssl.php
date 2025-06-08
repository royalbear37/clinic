<?php
echo "cainfo 設定為：<br>" . ini_get("curl.cainfo") . "<br>";

echo file_exists(ini_get("curl.cainfo"))
    ? "✅ 憑證檔存在，可使用"
    : "❌ 憑證檔不存在或路徑錯誤";
