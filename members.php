<?php
header("Content-type: application/javascript");
$mysql_host = "localhost";
$mysql_user = "fabian";
$mysql_pass = "dj1yfkn16";
$mysql_dbname = "focrbn";
$db = mysqli_connect($mysql_host,$mysql_user,$mysql_pass,$mysql_dbname);
?>
var members = { 
<?
$q = mysqli_query($db, "select `call`, `nr`, `nick` from foc_members where `left` is null;");
while ($f = mysqli_fetch_row($q)) {
    if ($f[1] == 0) {   # clubcalls
        $f[1] = 'Clubcall';
        $f[2] = "-";
    }
    if ($f[1] > 9900) { # nominee
        $f[1] = 'Nominee';
    }
    echo "'".$f[0]."':"."'FOC ".$f[1]." (".$f[2].")', ";
}
$q = mysqli_query($db, "select distinct foc_calls.call, foc_members.nr, foc_members.nick, foc_members.call from foc_calls JOIN foc_members ON foc_calls.nr = foc_members.nr where foc_members.left is null;");
while ($f = mysqli_fetch_row($q)) {
    if ($f[0] == $f[3]) {   # main call used
        echo "'".$f[0]."':"."'FOC ".$f[1]." (".$f[2].")', ";
    }
    else {
        echo "'".$f[0]."':"."'FOC ".$f[1]." (".$f[2]." - ".$f[3].")', ";
    }
}
echo "dummy:0";

?>
};
