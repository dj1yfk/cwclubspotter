<?php
?>

<!DOCTYPE html>
<html>
<head>
<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=iso-8859-1">
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<link rel="shortcut icon" type="image/x-icon" href="/pa4n.ico">
<link rel="stylesheet" type="text/css" href="/bandmap.css">
<title>CW Club RBN Spotter users</title>
</head>
<h1>CW Club RBN Spotter users</h1>

<p> Current users:
<?php
# DB config
$mysql_host   = "localhost";
$mysql_user   = "spotfilter";
$mysql_pass   = "spotfilter";
$mysql_dbname = "spotfilter";

$con=mysqli_connect($mysql_host,$mysql_user,$mysql_pass);
if (!$con)  die("<h1>Sorry: Could not connect to database.</h1>");
mysqli_select_db($con, $mysql_dbname);
$q=mysqli_query($con, "delete from users where time < (NOW() - INTERVAL 1 DAY);");
$q=mysqli_query($con, "select count(distinct(ipaddress)) from users where time > (NOW() - INTERVAL 5 MINUTE);");
mysqli_data_seek($q, 0);
$resrow = mysqli_fetch_row($q);
echo $resrow[0];
echo "<br/>";
echo "<br/>\n";
echo "Call signs (non-GUESTs): <ul>";
  $q=mysqli_query($con, "select distinct(`call`) from users where time > (NOW() - INTERVAL 5 MINUTE) and `call` not like '' order by 1;");
  $numrows = mysqli_num_rows($q); 
  $row=0;
  while ($row<$numrows) {
    mysqli_data_seek($q, $row);
    $resrow=mysqli_fetch_assoc($q);
    if (!preg_match('/GUEST/', $resrow['call'])) {
        echo "<li><a href='https://www.qrz.com/db/".$resrow['call']."'>".$resrow['call']."</a> - <a href='https://rbn.telegraphy.de/activity/".$resrow['call']."'>RBN</a></li>\n";
    }
    else {
###        echo "<li>".$resrow['call']."</li>\n";
    }  
    $row++;
  }
?>
</ul>
</p>

</body>
</html>
