<?php
error_reporting(0);
header("Access-Control-Allow-Origin: *");
# RBN / DX Cluster Spotter view 
#
# Fabian Kurz, DJ5CW (ex DJ1YFK) <fabian@fkurz.net>
# 2012-12-22
#
# Frank R. Oppedijk, PA4N <pa4n@xs4all.nl>
# 2013-04-26
#
# Original sources from: http://fkurz.net/ham/stuff.html?rbnbandmap
#
# This code is in the public domain.

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

include_once("clubs.php");

# XXX temporarily accept both CWOPS and CWops
if (array_key_exists('CWops', $_GET) and $_GET['CWops'] == 'true') {
    $_GET['CWOPS'] = 'true';
}

include_once("db.php");

if (!isset($_GET['req']))
	return; # Stop if called w/o arguments (like: called by a robot)

$visitor = $_SERVER['REMOTE_ADDR'];
$visitor = preg_replace('/\d+$/', '?', $visitor);   # anonymize

$ownCall = $_GET['ownCall'];
$ownCall = preg_replace("/[^A-Z0-9\/\-]/", "", $ownCall);
$ownCall=mysqli_real_escape_string($con, $ownCall);
mysqli_query($con, "delete from users where time < (NOW() - INTERVAL 20 MINUTE);");
mysqli_query($con, "insert into users values ('$visitor', NOW(), '$ownCall');");

// CS = custom filter, take only skimmers from Redis "rbnskimmers" array
$bm_conts_a = array( 'CS' => 0x02, 'OC' => 0x04, 'AF' => 0x08, 'SA' => 0x10, 'AS' => 0x20, 'NA' => 0x40, 'EU' => 0x80 );
$bm_conts = 0;
$allconts = array('CS', 'EU', 'NA', 'AS', 'SA', 'AF', 'OC');
$queryconts = array();
foreach ($allconts as $c) {
	if ($_GET[$c] == 'true') {
		array_push($queryconts, "'".$c."'");
		$bm_conts |= $bm_conts_a[$c];
	}
}

if ($bm_conts == $bm_conts_a["CS"]) {   // custom polygon filter, no continent filter
    $queryconts_string = "";
    $sk = $redis->hget("rbnskimmers", $ownCall);
    $arr = array();
    if (strlen($sk) > 3) {
        $arr = explode(" ", $sk);
    }
    for ($i = 0; $i < count($arr); $i++) {
        $arr[$i] = '"'.$arr[$i].'"';
    }
    $customSkimmers = " (`call` in (".implode(",", $arr).")) and  ";
}
elseif (sizeof($queryconts)>0) {
   $queryconts_string = "AND fromcont in (";
   $queryconts_string.= implode(',', $queryconts);
   $queryconts_string.= ")";
} else {
   $queryconts_string = "AND (false)";
}

$bm_bands = 0;
$allbands = array('160', '80', '60', '40', '30', '20', '17', '15', '12', '10', '6');
$querybands = array();
$bc = 0;
foreach ($allbands as $c) {
	if ($_GET[$c] == 'true') {
		$bm_bands |= 1 << $bc;
		array_push($querybands, $c);
	}
    $bc++;
}
if (sizeof($querybands)>0) {
   $querybands_string = "AND band in (";
   $querybands_string.= implode(',', $querybands);
   $querybands_string.= ")";
} else {
   $querybands_string = "AND (false)";
}


# Club selection 
$queryclub_string = "";
$mask = 0;
# new club selection
for  ($i = 0; $i < count($clubs); $i++) {
    if ($_GET[$clubs[$i]] == 'true') {
        $mask |= 1 << $i;
    }
}

if ($mask) {
    $queryclub_string = " AND member & $mask ";
}
else {
    $queryclub_string = " ";
}


$allspeeds = array('<10', '10-14', '15-19', '<20', '20-24', '25-29', '30-34', '35-39', '>39');
$bm_speeds = 0;
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
		   case '<10':
			$queryspeed_string.="wpm < 10";
			$bm_speeds |= 0x01;
			break;
		   case '10-14':
			$queryspeed_string.="(wpm >= 10 AND wpm <= 14)";
			$bm_speeds |= 0x02;
			break;
		   case '15-19':
			$queryspeed_string.="(wpm >= 15 AND wpm <= 19)";
			$bm_speeds |= 0x04;
			break;
		   case '<20':  # legacy
			$bm_speeds |= 0x07; # => 0b111
			$queryspeed_string.="wpm < 20";
			break;
		   case '20-24':
			$queryspeed_string.="(wpm >= 20 AND wpm <= 24)";
			$bm_speeds |= 0x08;
			break;
		   case '25-29':
			$queryspeed_string.="(wpm >= 25 AND wpm <= 29)";
			$bm_speeds |= 0x10;
			break;
		   case '30-34':
			$queryspeed_string.="(wpm >= 30 AND wpm <= 34)";
			$bm_speeds |= 0x20;
			break;
		   case '35-39':
			$queryspeed_string.="(wpm >= 35 AND wpm <= 39)";
			$bm_speeds |= 0x40;
			break;
		   case '>39':
			$queryspeed_string.="wpm > 39";
			$bm_speeds |= 0x80;
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
	

$redis->hset("rbnprefs", $ownCall, pack('C', $bm_conts).pack('Q', $mask).pack('C', $bm_speeds).pack('v', $bm_bands));

$maxAge=$_GET['maxAge'];
$sort=$_GET['sort'];

$callFilter=$_GET['callFilter'];
$callFilter=str_replace("?","_", $callFilter);
$callFilter=str_replace("*","%", $callFilter);
$callFilter = mysqli_real_escape_string($con, $callFilter);

$time_string="(timestampdiff(minute, time, UTC_TIMESTAMP()) <= $maxAge)";

# Include self-spots if user has so selected:
$selfSpotStr=($_GET['selfSpots']==="true" ? "OR ($time_string $querybands_string AND (dxcall like '$ownCall'))" : "");

# Include alert callsigns in *any* case, even if they're not part of the current filter.
# For calls prepended with a ~ (blocked), set the rbnblock value accordingly in
# Redis so the Telnet server can block them
$alertCalls = "";
if (array_key_exists('alerts', $_COOKIE)) {
    $block = array();
    $alerts = preg_replace('/[^\!\~A-Z0-9\/\s]/', '', $_COOKIE['alerts']); # clean alert list
    $alerts = preg_split('/\s+/', $alerts);
    for ($i = 0; $i < count($alerts); $i++) {
        if (substr($alerts[$i],0,1) == "~") {
            $block[] = substr($alerts[$i],1);
        }
        $alerts[$i] = "'".$alerts[$i]."'";
    }
    if (count($alerts)) {
        $alertCalls = "OR ($time_string $queryconts_string $querybands_string AND dxcall in (".join(",", $alerts).")) ";
    }
    $redis->hset("rbnblock", $ownCall, implode(" ", $block));
}

# for embedded RBN on Danish CW site
if ($ownCall == "CWMEDGNISTER") {
    $ozFilter = "(dxcall like 'OU%' or dxcall like 'OV%' or dxcall like 'OX%' or dxcall like 'OY%' or dxcall like 'OZ%' or dxcall like '5P%' or dxcall like '5Q%') and "; 
}
else {
    $ozFilter = "";
}

$json_a = array();

# Delete spots older than 60 minutes, or spots that were made (over 30 minutes) in the future
mysqli_query($con, "set timezone = '+00:00'");
if (rand(0,10) < 1) {
    mysqli_query($con, "delete from spots where time < (UTC_TIMESTAMP() - INTERVAL 60 MINUTE) or time > (UTC_TIMESTAMP() + INTERVAL 30 MINUTE);");
}

$queryStr = "select freq, band, dxcall, `call`, timestampdiff(minute, time, UTC_TIMESTAMP()) as age, `memberof`, snr, wpm from spots where $customSkimmers $ozFilter ( $time_string $queryconts_string $querybands_string AND dxcall like '$callFilter' $queryclub_string $queryspeed_string) $selfSpotStr $alertCalls order by ";


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

$q = mysqli_query($con, $queryStr);
#error_log($queryStr);

# create JSON, aggregate all rows with same freq and dxcall into one entry.

$dxc = "";			# DX call of current entry
$freq = "";			# freq
$age = 20;			# age of youngest spot
$minwpm = 0;
$maxwpm = 0;
$memberof = "";
$spotters = array(); # Hash keys = spotter calls, values = snrs
$spotters_old = "";

$r = mysqli_fetch_object($q);

while ($r) {
		if ($r->dxcall != $dxc or abs($r->freq - $freq) > 0.5 or !$aggregateSpotters or (!$aggregateSpeeds and ($r->wpm!=$minwpm or $r->wpm!=$maxwpm))) { # Start new entry
			if ($dxc != "") { 
			# Output previous entry if it exists
			array_push($json_a, build_json($dxc, $freq, $band, $age, ($minwpm<$maxwpm ? "$minwpm-$maxwpm" : "$minwpm"), $memberof, $spotters, $spotters_old));
			}

			$dxc = $r->dxcall;
			$freq = sprintf("%.1f", $r->freq);
			$band = $r->band;
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
array_push($json_a, build_json($dxc, $freq, $band, $age, ($minwpm<$maxwpm ? "$minwpm-$maxwpm" : "$minwpm"), $memberof, $spotters, $spotters_old));

echo "[".implode(",", $json_a)."]";
#error_log(1000*(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]));
if (intval(phpversion())>=5)
  mysqli_close($con);
else
  mysql_close();

function build_json ($dxc, $freq, $band, $age, $wpm, $memberof, $spotters, $spotters_old) {
	$ret = "";
	$ret .= "{\n\"freq\":\"$freq\",\n";
	$ret .= "\"band\":\"$band\",\n";
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

# error_log("query_time($ownCall): ".(1000*(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"])));
?>
