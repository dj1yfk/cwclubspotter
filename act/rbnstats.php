<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="/bandmap.css" />
<script>
</script>
<title>RBN activity statistics</title>
</head>
<body>
<h1>RBN activity statistics</h1>

<p>Charts of RBN activity, active callsigns and (tbd) more stuff. A work in progress.</p>
<p>
<a href="mailto:fabian@fkurz.net">Comments? Write me!</a>
</p>


<h2>Histogram: Active hours of individual callsigns (last 365 days)</h2>

<?
    include_once('db.php');
	$db = mysqli_connect($mysql_host,$mysql_user,$mysql_pass,$mysql_dbname) or die ("<h1>Sorry: Could not connect to database.</h1>");

	# activity in 100 hour increments
	$q = mysqli_query($db, "SELECT ROUND(hours-50, -2) AS h, COUNT(*) AS COUNT FROM rbn_rank_nobeacon GROUP BY h; ");
?>
<table class="fancy">
<tr><th>Active hours</th><th>Count</th><th>Log scale</th><th>Calls</th></tr>
<?
	while ($r = mysqli_fetch_row($q)) {
		$row++;
		if ($row % 2 == 0) {
			$style = " class='oldspot'";
		}
		else {
			$style = "";
		}

		$calls = "";
		if ($r[1] > 35) {
			$calls = "-";
		}
		else {
			$qq = mysqli_query($db, "select callsign from rbn_rank_nobeacon where hours >= $r[0] and hours <= ".($r[0] + 100)." order by hours desc");
			while ($rr = mysqli_fetch_row($qq)) {
				$calls .= "<a href='/activity/$rr[0]'>$rr[0]</a> ";
			}
		}

        $callsum += $r[1];

        if ($r[0] == 0) {
            $r[0] = 10;
        }
		echo "<tr ".$style."><td>".$r[0]."+</td><td>".$r[1]."</td><td><img src='/act/img/2.png' height=8 width=".((20*log10($r[1])) > 1 ? (20*log10($r[1])) : 1)."></td><td>$calls</td></tr>\n";		
	}

?>
</table>
<p>Unique active calls: <?=$callsum;?>. Note that beacons and frequently "busted" callsigns like <a href="/activity/T2DE">T2DE</a> were not included here.</p>

<h2>How many hams generate how much activity?</h2>
<p>Does the <a href="https://en.wikipedia.org/wiki/Pareto_principle">Pareto principle ('80/20 rule')</a> hold true for RBN spots?</p>

<?
	$q = mysqli_query($db, "select count(*) from rbn_rank_nobeacon;");
	$r = mysqli_fetch_row($q);
	$numcalls = $r[0];
	$q = mysqli_query($db, "select sum(hours) from rbn_rank_nobeacon;");
	$r = mysqli_fetch_row($q);
	$acthr = $r[0];
?>


<p>As of today, the total number of activity hours (i.e. hours in which
individual callsigns were spotted, with a lower threshold of 10 hours over the
last year) registered by the RBN is: <b><?=$acthr;?></b> from <b><?=$numcalls;?></b>
individual calls.</p>
<p>In order to fulfil the rule, 80% of <?=$acthr;?> hours (= <?=round(0.8*$acthr);?>h) would have to be produced by 20% of <?=$numcalls;?> stations (= <?=round(0.2*$numcalls);?>).</p>

<?
	$q = mysqli_query($db, "select sum(hours) from rbn_rank_nobeacon where rank >= ".round(0.2*$numcalls));
	$r = mysqli_fetch_row($q);
?>

<p>As of today, those 20% generated <?=$r[0];?> hours of activity...

<?
	if ($r[0] > 0.8*$acthr) {
?>
	Pareto was damn right!
<?
	}
	else {
?>
	Sorry, Pareto!
<?
	}
?>
</p>
<p>
(Note: The minimum activity per callsign and year was 10 hours. If this threshold was increased to a more reasonable value, the 80/20 rule may hold true...)

</p>









<hr>
See also: 
<ul>
<li><a href="/activity/rank">RBN Ranking</a> - Find out the rank of individual calls in the world wide ranking</li>
<li><a href="/activity">Activity reports</a> - Detailled overview of a call's activity in the RBN</li>
<li><a href="/">CW Club RBN Spotter</a></li>
<li><a href="https://foc.dj1yfk.de/">FOC RBN</a></li>
</ul>

<hr>
<p>Last modified: <? echo date ("Y-m-d",  filemtime("active.php")); ?> - <a href="http://fkurz.net/">Fabian Kurz, DJ5CW</a> <a href="mailto:fabian@fkurz.net">&lt;fabian@fkurz.net&gt;</a>
<?
        if (!$_SERVER['HTTPS']) { ?> - <a rel="nofollow" href="https://rbn.telegraphy.de/activity/stats">Switch to https</a> <? }
				        else { ?> - <a rel="nofollow" href="http://rbn.telegraphy.de/activity/stats">Switch to http</a> <? }
?>
- <a href="/privacy">Impressum / Datenschutz / Privacy Policy</a>
<!-- Page rendered in  <? echo 1000*(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);  ?> ms -->


</body>
</html>
