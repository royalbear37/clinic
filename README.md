##  專案建置步驟
clone到之前作業用的AppServ/www資料夾裡面  
****
在瀏覽器輸入localhost/clinic/users/login.php進到登入頁面  
****
先用config裡的sql檔建立database  
****
請將 config 裡的 mysql_connect.sample.php 複製一份(要保留sample)，並重新命名為 mysql_connect.inc.php，並且把裡面的密碼改成自己的  
****
一開始db裡面沒有任何使用者，要先自己建  
****
##  AI 機器人功能設定

打開你的：C:\AppServ\php7\php.ini
****
搜尋：curl.cainfo
請將curl.cainfo這一行修改為：curl.cainfo = "C:\AppServ\www\clinic\ai_assistant\cacert.pem"(最前面冒號是註解，要刪掉)
WIN+R搜尋services.msc，重啟appserv

