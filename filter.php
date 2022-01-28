<?php
#header("Content-type: application/json");

$c = $_GET['c'];
$c = preg_replace('/\-\d+$/', '', $c);  # remove SSID

# "valid" call?
if (!preg_match('/^[a-z0-9]{4,10}$/i', $c) or preg_match('/^GUEST/', $c)) {
    echo "{}";
    exit();
}

$cwo = file_get_contents("https://cwops.telegraphy.de/api.php?action=export_rbn&c=$c");
$foc = file_get_contents("https://foc.telegraphy.de/api.php?action=export_rbn&c=$c");

$cwo = json_decode($cwo, 1);
$foc = json_decode($foc, 1);

foreach ($foc as $k => $v) {
    if (array_key_exists($k, $cwo)) {   # merge
        foreach ($v as $b => $a) {
            if (array_key_exists($b, $cwo[$k])) {
                $cwo[$k][$b] = array_merge($cwo[$k][$b], $a);
            }
            else {
                $cwo[$k][$b] = $a;
            }
        }
    }
    else {  # new one
        $cwo[$k] = $v;
    }
}

if (array_key_exists($c, $cwo)) {
    unset($cwo[$c]);
}

echo json_encode($cwo, JSON_UNESCAPED_SLASHES);



?>
