<?php

include("db.php");

$c = "";
$b = "";

echo "<pre>";
if (preg_match("/^[A-Z0-9\/]+$/i", $_GET['dx'])) { $c = $_GET['dx']; }
if (preg_match("/^[0-9]+$/", $_GET['band'])) { $b = " and band = ".$_GET['band']; }


$q = mysqli_query($con, "select * from spots where dxcall like '$c%' $b order by time desc");
    
while ($r = mysqli_fetch_row($q)) {
    printf("DX de %-8s  %10s  %-12s  %s\n", "$r[0]:", $r[1], $r[2], $r[5]);
}
