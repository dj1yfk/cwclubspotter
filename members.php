<?php
header("Content-type: application/javascript");

# we return a members array with the following structure:
#
# var members = {
#   "DJ5CW": "Fabian, FOC#1796, CWops#1566",            /* primary call */
#   "SO5CW": "Fabian (DJ5CW), FOC#1796, CWops#1566"     /* secondary call */
# }

# for this, we first fill in two arrays:
#   CALL => Name
#   CALL => array("FOC#...", "CWops#...") 

$names = array();
$members = array();

# FOC - get primary and secondary calls from two tables.

$mysql_host = "localhost";
$mysql_user = "fabian";
$mysql_pass = "dj1yfkn16";  # this PW is not used for anything else, and only works locally
$mysql_dbname = "focrbn";
$db = mysqli_connect($mysql_host,$mysql_user,$mysql_pass,$mysql_dbname);

$q = mysqli_query($db, "select `call`, `nr`, `nick` from foc_members where `left` is null;");
while ($f = mysqli_fetch_row($q)) {
    if ($f[1] > 9900) { # nominee
        $f[1] = 'Nominee';
    }
    $names[$f[0]] = $f[2];
    $members[$f[0]] = array('FOC#'.$f[1]);
}

# Additional calls from FOC - add "maincall" to name
$q = mysqli_query($db, "select distinct foc_calls.call, foc_members.nr, foc_members.nick, foc_members.call from foc_calls JOIN foc_members ON foc_calls.nr = foc_members.nr where foc_members.left is null;");
while ($f = mysqli_fetch_row($q)) {
   if ($f[0] == $f[3]) {   # main call used
   }
    else {
        $names[$f[0]] = $f[2].' ('.$f[3].')';
        $members[$f[0]] = array('FOC#'.$f[1]);
    }
}

# CWops
$db = mysqli_connect('localhost','cwops', 'cwops', 'CWops');
$q = mysqli_query($db, "select `callsign`, `nr`, `name` from cwops_members where `left` = '2099-01-01';");
while ($f = mysqli_fetch_row($q)) {
    if (!array_key_exists($f[0], $names)) {
        $names[$f[0]] = $f[2];
    }
    if (array_key_exists($f[0], $members)) {
        array_push($members[$f[0]], 'CWops#'.$f[1]);
    }
    else {
        $members[$f[0]] = array('CWops#'.$f[1]);
    }
}

$out = array();
foreach ($names as $c => $n) {
    $out[$c] = "$n - ".implode(', ', $members[$c]);
}

echo "var members = ".json_encode($out).";";
?>
