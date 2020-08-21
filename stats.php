<!DOCTYPE html>
<html>
<head><title>CW Club RBN Spotter - Statistics</title>
<style>
table {
    border-collapse: collapse;
}
td {
    vertical-align: bottom;
}
img {
display: block;
}
</style>


</head>



<table >
<tr>
<?
    $data = file("spotcount.txt");

    # last 30 days = 30 * 24 lines (max)

    $cnt = count($data);
    foreach ($data as $d) {
        list($ts, $c, $u) = preg_split("/\s+/", $d);

        $scale = 80;
        $height_count = ($c - $u) / $scale;
        $height_unique = $u / $scale; 

        echo "<td title='".gmstrftime('%A %d-%b-%y %T %Z', $ts)." - $c / $u'><img src='act/img/2.png' width=4 height=$height_count><img src='act/img/5.png' width=4 height=$height_unique></td>";
    }

?>
</tr>
</table>

<br>
Data shown: Number of spots on the bandmap, number of unique calls, for the past 60 minutes. Hover over graph to see the time stamps.


</html>
