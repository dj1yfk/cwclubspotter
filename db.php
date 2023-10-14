<?php

# DB config
$mysql_host   = "localhost";
$mysql_user   = "spotfilter";
$mysql_pass   = "spotfilter";
$mysql_dbname = "spotfilter";

$con=mysqli_connect($mysql_host,$mysql_user,$mysql_pass);
if (!$con)  die("<h1>Sorry: Could not connect to database.</h1>");
mysqli_select_db($con, $mysql_dbname);

?>
