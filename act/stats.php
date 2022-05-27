<?
/* Extract statistics from RBN data:
   * Hours slots active per band
   * Hour slots active total
   * Continental stats
   * Input: Call, Starting Timestamp, Duration (Days)
 */

function print_stats($call, $start_ts, $days) {
    # error_log(">$call<");
	$ret = '';

	$c = strtoupper($call);

    $c = preg_replace("/ /", "+", $c);
	if (!preg_match('/^[A-Z0-9\/\+]+$/', $c)) {
        error_log("invalid call $c");
		return $ret;
	}

	include_once('functions.php');

    $calls = explode("+", $c);
    $arr = get_multiple_calls($calls);

	# get ranking position

    include('db.php');
    for ($i = 0; $i < count($calls); $i++)
        $calls[$i] = "'".$calls[$i]."'";
    $db = mysqli_connect($mysql_host,$mysql_user,$mysql_pass, $mysql_dbname) or die ("<h1>Sorry: Could not connect to database!</h1>");
    $query = "select min(rank) from rbn_rank_beacon where callsign in (".join(',', $calls).");";
    # error_log($query);
	$q = mysqli_query($db, $query);
	if ($q) {
		$rank = mysqli_fetch_row($q);
		$rank = $rank[0];
	}
	else {
		$rank = false;
	}

    # error_log("call '$c' rank $rank");
	# 01.01.2009 = start of all data (first cell)
	$t0 = 1230768000;

	// Count activity hours for last x days in associative array
	$start_cell = ($start_ts - $t0) / 60 / 60;	
	$stop_cell = $start_cell + ($days*24);

	// For the hourly stats, get the hour of the initial data cell
	$start_hr = gmdate('H', $start_ts);
	$start_hr++; 

	$start_cell = intval($start_cell);
	$ret .= "<!--   $start_ts   $start_cell    $start_hr  -->\n";


	$act['AS'] = 0; $act['EU'] = 0; $act['AF'] = 0; $act['NA'] = 0; $act['SA'] = 0; $act['OC'] = 0;

	for ($i = $start_cell; $i < $stop_cell ; $i++) {
			 if ($arr[$i]) {
				$val = $arr[$i];

				# hourly stats. we are in hour ($hr + ($i - $start_cell)) % 24 
				$hr = ($start_hr + ($i - $start_cell)) % 24;

				$act['any']++;

				if ($val & 0x0001) { $act['160']++; $hract[$hr]['160']++;};
				if ($val & 0x0002) { $act['80']++;  $hract[$hr]['80']++;};
				if ($val & 0x0004) { $act['60']++; $hract[$hr]['60']++;};
				if ($val & 0x0008) { $act['40']++; $hract[$hr]['40']++; };
				if ($val & 0x0010) { $act['30']++; $hract[$hr]['30']++; };
				if ($val & 0x0020) { $act['20']++; $hract[$hr]['20']++; };
				if ($val & 0x0040) { $act['17']++; $hract[$hr]['17']++; };
				if ($val & 0x0080) { $act['15']++; $hract[$hr]['15']++; };
				if ($val & 0x0100) { $act['12']++; $hract[$hr]['12']++; };
				if ($val & 0x0200) { $act['10']++; $hract[$hr]['10']++; };
				if ($val & 0x400000) { $act['2200']++; $hract[$hr]['2200']++; };
				if ($val & 0x200000) { $act['630']++; $hract[$hr]['630']++; };
				if ($val & 0x100000) { $act['6']++; $hract[$hr]['6']++; };
				if ($val & 0x80000) { $act['4']++; $hract[$hr]['4']++; };
				if ($val & 0x40000) { $act['2']++; $hract[$hr]['2']++; };
				if ($val & 0x20000) { $act['0.7']++; $hract[$hr]['0.7']++; };
				if ($val & 0x0400) { $act['OC']++; };
				if ($val & 0x0800) { $act['AF']++; };
				if ($val & 0x1000) { $act['SA']++; };
				if ($val & 0x2000) { $act['AS']++; };
				if ($val & 0x4000) { $act['NA']++; };
				if ($val & 0x8000) { $act['EU']++; };

			 }
	}

    if ($act['any'] == 0) {
        return "<p><b>No activity in selected time frame or the owner of the callsign does not wish to appear on this page.</b></p>";
    }

	# for hourly stats, find the highest sum for any hour
	$maxhr = 0;
	for ($i = 0; $i < 24; $i++) {
		$hrsum = 0;
		foreach ($hract[$i] as $k => $v) {
			$hrsum += $v;
		}
		if ($hrsum > $maxhr) {
				$maxhr = $hrsum;
		}
	}

	$ret .= "Hours (in $days days) with at least one RBN spot: ".$act['any']." (".round($act['any']/$days,1)."h / day).\n";
	$ret .= "EU: ".$act['EU'].", NA: ".$act['NA'].", AS: ".$act['AS'].", SA: ".$act['SA'].", AF: ".$act['AF'].", OC: ".$act['OC'];
	if ($rank) { 
			$ret .= ". <a target='_top' href='/activity/rank#$rank'>Rank: ".$rank."</a>";
	}
	$ret .= "<br><br>\n"; 
	$ret .= "<div style='overflow:hidden; width:100%'>\n<div style='float:left'>";
	$ret .= "<table style='padding:0px; margin:0px; border-spacing:0;border-collapse:collapse;'><tr><th>Band</th><th>Active hours</th></tr>\n";
	$ret .= band_bar('160', $act);
	$ret .= band_bar('80', $act);
	$ret .= band_bar('60', $act);
	$ret .= band_bar('40', $act);
	$ret .= band_bar('30', $act);
	$ret .= band_bar('20', $act);
	$ret .= band_bar('17', $act);
	$ret .= band_bar('15', $act);
	$ret .= band_bar('12', $act);
    $ret .= band_bar('10', $act);
    $nob = 10;  # always show 10 HF bands
    if ($act['6']) { $ret .= band_bar('6', $act); $nob++; }
	if ($act['4']) { $ret .= band_bar('4', $act); $nob++; }
	if ($act['2']) { $ret .= band_bar('2', $act); $nob++; }
	$ret .= "</table>\n &nbsp; </div> <div>\n";
	$ret .= "<table style='border-spacing:0;border-collapse:collapse;'><tr><th colspan='24'>Activity vs. Time (UTC)  <span id='hover'>[hover mouse over bar to see band]</span></th></tr><tr>";
	for ($i = 0; $i < 24; $i++) {
        $height = 15 * $nob + 5;
		$ret .= "<td id='v$i' style='vertical-align:bottom;height:${height}px;'>".hour_bar($i, $hract[$i], $maxhr, $height)."</td>\n";
	}
	$ret .= "</tr>\n";
	$ret .= "<tr>\n";
	for ($i = 0; $i < 24; $i++) {
		$ret .=  "<td>$i</td>";
	}
	$ret .= "</tr></table> </div> </div>";

	return $ret;
} // function print_stats

function band_bar ($b, $a) {
		$maxval = 0;
		foreach ($a as $k => $v) {
				if (is_int($k)) {
						if ($v > $maxval) {
								$maxval = $v;
						}
				}
		}

		# chose image 0 ... 5 depending on value of this band
		$myval = $a[$b];
		$barlen = $myval / $maxval * 150;

		$img[160] = 0;
		$img[80] = 1;
		$img[60] = 2;
		$img[40] = 3;
		$img[30] = 4;
		$img[20] = 0;
		$img[15] = 1;
		$img[17] = 2;
		$img[12] = 3;
		$img[10] = 4;
		$img[6] = 0;
		$img[4] = 1;
		$img[2] = 2;

		return "<tr><td style='height:15px;'>".$b."m</td><td><img src='/act/img/".$img[$b].".png' height='7' width='$barlen'>&nbsp;".$myval."</td></tr>\n";
}

function hour_bar ($h, $a, $max, $height) {

		$img[2200] = 0;
		$img[630] = 0;
		$img[160] = 0;
		$img[80] = 1;
		$img[60] = 2;
		$img[40] = 3;
		$img[30] = 4;
		$img[20] = 0;
		$img[15] = 1;
		$img[17] = 2;
		$img[12] = 3;
		$img[10] = 4;
		$img[6] = 0;
		$img[4] = 1;
		$img[2] = 2;

		$sum = 0;
		foreach ($a as $b => $c) {		# band -> count
			$sum += $c;
		}

		# scaled bandwise markers

		$ret = '';
		ksort($a);
		foreach ($a as $b => $c) {		# band -> count
			if ($c and is_int($b)) {
				$ret .= "<img style='margin:0px;padding:0px;height:".(($c/$max)*($height-5))."px;width:8px;display:block;' title='$b"."m - $c"."h' src='/act/img/".$img[$b].".png'>";
			}
		}
	return $ret;
}


?>
