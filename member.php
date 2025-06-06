<?php session_start(); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
include("mysql_connect.inc.php");
echo '<a href="/clinic/logout.php">登出</a> <br><br>';
//此判斷為判定觀看此頁有沒有權限
//說不定是路人或不相關的使用者
//因此要給予排除
if($_SESSION['username'] != null)
{
	echo '<a href="/clinic/register.php">新增</a> ';
	echo '<a href="/clinic/update.php">修改</a> ';
	echo '<a href="/clinic/delete.php">刪除</a> <br><br>';
    //將資料庫裡的所有會員資料顯示在畫面上
	$sql = "SELECT * FROM susers";
	$result = mysqli_query($conn,$sql);
	while($row = mysqli_fetch_row($result))
	{
		echo "$row[0] - Username：$row[1], " . 
		"Email：$row[2]<br>";
	}
}
else
{
	echo '您無權限觀看此頁面!';
	echo '<meta http-equiv=REFRESH CONTENT=2;url=login.php>';
}
?>