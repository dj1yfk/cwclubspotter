<?php
# RBN / DX Cluster Spotter view 
#
# Fabian Kurz, DJ1YFK <fabian@fkurz.net>
# 2012-12-22
#
# Frank R. Oppedijk, PA4N <pa4n@xs4all.nl>
# 2013-04-26
#
# Original sources from: http://fkurz.net/ham/stuff.html?rbnbandmap
#
# This code is in the public domain.

# DB config
$mysql_host   = "localhost";
$mysql_user   = "spotfilter";
$mysql_pass   = "spotfilter";
$mysql_dbname = "spotfilter";

if (intval(phpversion())>=5) {
  $con=mysqli_connect($mysql_host,$mysql_user,$mysql_pass);
  if (!$con)  die("<h1>Sorry: Could not connect to database.</h1>");
  mysqli_select_db($con, $mysql_dbname);
  }
else {
  mysql_connect($mysql_host,$mysql_user,$mysql_pass) or die("<h1>Sorry: Could not connect to database.</h1>");
  mysql_select_db($mysql_dbname);
  }

if (!isset($_GET['req']))
	return; # Stop if called w/o arguments (like: called by a robot)

$visitor = $_SERVER['REMOTE_ADDR'];
$ownCall=$_GET['ownCall'];
if (intval(phpversion())>=5) 
  mysqli_query($con, "insert into users values ('$visitor', NOW(), '$ownCall');");
else
  mysql_query("insert into users values ('$visitor', NOW(), '$ownCall');");

$allconts = array('EU', 'NA', 'AS', 'SA', 'AF', 'OC');
$queryconts = array();
foreach ($allconts as $c) {
	if ($_GET[$c] == 'true') {
		array_push($queryconts, "'".$c."'");
	}
}
if (sizeof($queryconts)>0) {
   $queryconts_string = "AND fromcont in (";
   $queryconts_string.= implode(',', $queryconts);
   $queryconts_string.= ")";
} else {
   $queryconts_string = "AND (false)";
}
#syslog(LOG_ERR, $queryconts_string);
	
$allbands = array('160', '80', '60', '40', '30', '20', '17', '15', '12', '10', '6');
$querybands = array();
foreach ($allbands as $c) {
	if ($_GET[$c] == 'true') {
		array_push($querybands, $c);
	}
}
if (sizeof($querybands)>0) {
   $querybands_string = "AND band in (";
   $querybands_string.= implode(',', $querybands);
   $querybands_string.= ")";
} else {
   $querybands_string = "AND (false)";
}
	
$allclubs = array('CWops', 'FISTS', 'FOC', 'HSC', 'VHSC', 'SHSC', 'EHSC', 'SKCC');
$queryclub_string = "";
$first=true;
foreach ($allclubs as $c) {
	if ($_GET[$c] == 'true') {
		if ($first==true) 
			{ 
			$queryclub_string.="AND (";
			$first=false;
			}
		else
			{ 
			$queryclub_string.=" OR ";
			}
		$queryclub_string.="memberof like '%(".$c.")%'";
	}
}
if ($first==false)
	{ # We had at least one entry. Add closing parentesis
	$queryclub_string.=")";
	}
else
	{ # We had no entries. We must return empty result set. This makes it so
	$queryclub_string.="AND (0)";
	}
	
$allspeeds = array('<20', '20-24', '25-29', '30-34', '35-39', '>39');
$queryspeed_string = "";
$first=true;
foreach ($allspeeds as $c) {
	if ($_GET[$c] == 'true') {
		if ($first==true) 
			{ 
			$queryspeed_string.="AND (";
			$first=false;
			}
		else
			{ 
			$queryspeed_string.=" OR ";
			}
		switch ($c) {
		   case '<20':
			$queryspeed_string.="wpm < 20";
			break;
		   case '20-24':
			$queryspeed_string.="(wpm >= 20 AND wpm <= 24)";
			break;
		   case '25-29':
			$queryspeed_string.="(wpm >= 25 AND wpm <= 29)";
			break;
		   case '30-34':
			$queryspeed_string.="(wpm >= 30 AND wpm <= 34)";
			break;
		   case '35-39':
			$queryspeed_string.="(wpm >= 35 AND wpm <= 39)";
			break;
		   case '>39':
			$queryspeed_string.="wpm > 39";
			break;
			
		}
	}
}
if ($first==false)
	{ # We had at least one entry. Add closing parentesis
	$queryspeed_string.=")";
	}
else
	{ # We had no entries. We must return empty result set. This makes it so
	$queryspeed_string.="AND (0)";
	}
	
$maxAge=$_GET['maxAge'];
$sort=$_GET['sort'];

$callFilter=$_GET['callFilter'];
$callFilter=str_replace("?","_", $callFilter);
$callFilter=str_replace("*","%", $callFilter);
#syslog(LOG_ERR, $callFilter);

$time_string="(timestampdiff(minute, time, UTC_TIMESTAMP()) <= $maxAge)";

# Include self-spots if user has so selected:
$ownCall=$_GET['ownCall'];
$selfSpotStr=($_GET['selfSpots']==="true" ? "OR ($time_string $querybands_string AND (dxcall like '$ownCall'))" : "");
#syslog(LOG_ERR, $selfSpotStr);

$blacklistStr="AND (dxcall not like 'R6AF')";

$json_a = array();

if (intval(phpversion())>=5) {
  mysqli_query($con, "set timezone = '+00:00'");
  if (rand(10) < 1) {
     mysqli_query($con, "delete from spots where time < (UTC_TIMESTAMP() - INTERVAL 60 MINUTE) or time > (UTC_TIMESTAMP() + INTERVAL 30 MINUTE);");
  }
    # Delete spots older than 60 minutes, or spots that were made (over 30 minutes) in the future
  } 
else {
  mysql_query("set timezone = '+00:00'");
  mysql_query("delete from spots where time < (UTC_TIMESTAMP() - INTERVAL 60 MINUTE) or time > (UTC_TIMESTAMP() + INTERVAL 30 MINUTE);");
    # Delete spots older than 60 minutes, or spots that were made (over 30 minutes) in the future
  }

#$queryStr = "select freq, dxcall, `call`, timestampdiff(minute, time, UTC_TIMESTAMP()) as age, `memberof`, substring(`comment`, 9,2) as snr, wpm from spots where ( $time_string $queryconts_string $querybands_string AND dxcall like '$callFilter' $queryclub_string $queryspeed_string ) $selfSpotStr order by ";
$queryStr = "select freq, dxcall, `call`, timestampdiff(minute, time, UTC_TIMESTAMP()) as age, `memberof`, snr, wpm from spots where ( $time_string $queryconts_string $querybands_string AND dxcall like '$callFilter' $queryclub_string $queryspeed_string $blacklistStr) $selfSpotStr order by ";

$aggregateSpotters=true; # Merge spots for 1 dxcall and ~1 frequency by different spotters into one row
$aggregateSpeeds=true; # Merge spots for 1 dxcall and ~1 frequency with different speeds into one row

switch ($sort) {
	case 1:
	default:
		$queryStr.="freq, dxcall;";
		break;
	case 2:
		$queryStr.="dxcall, freq;";
		break;
	case 3:
		$queryStr.="age, freq, dxcall;";
		break;
	case 4:
		$queryStr.="memberof, freq, dxcall;";
		break;
	case 5:
		$queryStr.="wpm, freq, dxcall;";
		$aggregateSpeeds=false; # Each speed will be on separate row
		break;
	case 6:
		$queryStr.="`call`, freq, dxcall;";
		$aggregateSpotters=false; # Each spotter will be on separate row
		break;
	}
#syslog(LOG_ERR, $queryStr);

if (intval(phpversion())>=5)
  $q = mysqli_query($con, $queryStr);
else
  $q = mysql_query($queryStr);

# create JSON, aggregate all rows with same freq and dxcall into one entry.

$dxc = "";			# DX call of current entry
$freq = "";			# freq
$age = 20;			# age of youngest spot
$minwpm = 0;
$maxwpm = 0;
$memberof = "";
$spotters = array(); # Hash keys = spotter calls, values = snrs
$spotters_old = "";

if (intval(phpversion())>=5) 
  $r = mysqli_fetch_object($q);
else
  $r = mysql_fetch_object($q);
while ($r) {
		if ($r->dxcall != $dxc or abs($r->freq - $freq) > 0.5 or !$aggregateSpotters or (!$aggregateSpeeds and ($r->wpm!=$minwpm or $r->wpm!=$maxwpm))) { # Start new entry
			if ($dxc != "") { 
			# Output previous entry if it exists
			array_push($json_a, build_json($dxc, $freq, $age, ($minwpm<$maxwpm ? "$minwpm-$maxwpm" : "$minwpm"), $memberof, $spotters, $spotters_old));
			}

			$dxc = $r->dxcall;
			$freq = sprintf("%.1f", $r->freq);
			$age = $r->age;
			$minwpm=$r->wpm;
			$maxwpm=$r->wpm;
			$memberof = $r->memberof;
			$memberof=str_replace("(","", $memberof);
			$memberof=str_replace(")","", $memberof);
			$spotters = array();
			$spotters[$r->call] = $r->snr;
			$spotters_old = '"'.$r->call.'"';
		}
		else {	# Append new spotter to existing entry
			if ($r->age < $age) {
				$age = $r->age;
			}
			if ($r->wpm<$minwpm) $minwpm=$r->wpm;
			if ($r->wpm>$maxwpm) $maxwpm=$r->wpm;
			$memberof = $r->memberof;
			$memberof=str_replace("(","", $memberof);
			$memberof=str_replace(")","", $memberof);
			if (!array_key_exists($r->call, $spotters) or $spotters[$r->call] < $r->snr) {
				$spotters[$r->call] = $r->snr;
			}
			$spotters_old .= ' "'.$r->call.'"';
		}

	if (intval(phpversion())>=5) 
	  $r = mysqli_fetch_object($q);
	else
 	  $r = mysql_fetch_object($q);
}

# get rid of last entry
array_push($json_a, build_json($dxc, $freq, $age, ($minwpm<$maxwpm ? "$minwpm-$maxwpm" : "$minwpm"), $memberof, $spotters, $spotters_old));

echo "[".implode(",", $json_a)."]";
if (intval(phpversion())>=5)
  mysqli_close($con);
else
  mysql_close();

function build_json ($dxc, $freq, $age, $wpm, $memberof, $spotters, $spotters_old) {
	$ret = "";
	$ret .= "{\n\"freq\":\"$freq\",\n";
	$ret .= "\"dxcall\":\"$dxc\",\n";
	$ret .= "\"memberof\":\"$memberof\",\n";
	$ret .= "\"age\":\"$age\",\n";
	$ret .= "\"wpm\":\"$wpm\",\n";

	# Entries like this are created from spotters array 
	# { "call":"DR1A", "snr":"12" } 

	$tmp = array();
	arsort($spotters);
	foreach ($spotters as $c => $s) {
		array_push($tmp, "{ \"call\":\"$c\", \"snr\":\"$s\" }");
	}
	$ret1 = implode(",", $tmp);
	$ret .= "\"snr_spotters\": [ $ret1 ]\n}";

	return $ret;
}
?>
