<?
	error_reporting(0);
	$c = $_GET['c'];
	$c = strtoupper($c);

	$o = 0;
	if (array_key_exists('o', $_GET)) {
		$o = $_GET['o'];
		if (!is_int($o+0)) { $o = 0; }
	}

	$n = 25;
	if (array_key_exists('n', $_GET)) {
		$n = $_GET['n'];
		if (!is_int($n+0)) { $n = 25; }
	}

	$b = false;
	if (array_key_exists('b', $_GET)) {
        $b = true;
	}

    # DXCC
    $d = '';
	if (array_key_exists('d', $_GET)) {
		$d = $_GET['d'];
        if (!preg_match('/^[A-Z0-9\/]{0,25}$/i', $d)) {  // invalid dxcc
		    $d = '';
    	}
        if ($d == 'all') {
            $d = '';
        }
	}

	if ($n > 1000) {
		$n = 25;
	}

	if (!preg_match('/^[A-Z0-9\/]{0,25}$/', $c)) {  // invalid call
		return "[]";
	}

    $dxcc = '';
    if ($d != '') {
        $dxcc = " and dxcc='$d' ";
    }

	include_once('db.php');
	$db = mysqli_connect($mysql_host,$mysql_user,$mysql_pass,$mysql_dbname) or die ("<h1>Sorry: Could not connect to database.</h1>");

    if ($b) {
        $table = "rbn_rank_beacon";
    }
    else {
        $table = "rbn_rank_nobeacon";
    }

    $query1 = "set @r=$o;";
    $query2 = "select @r:=@r+1 as rank, rank as wwrank, callsign, hours, beacon, wl from $table where wl=1 and callsign like \"%$c%\" $dxcc limit $o,$n;";
	$q = mysqli_query($db, $query1);
	$q = mysqli_query($db, $query2);
	if (!$q) {
		$ret = "<p>Database error.</p>\n";
		return $ret;
	}
	$out = array();
    while ($d = mysqli_fetch_array($q, MYSQLI_ASSOC)) {
        if ($d['wl'] == "0") {
            $d['anon'] = 1;
            $d['callsign'] = substr($d['callsign'], 0, 3)."...";
        }
		array_push($out, $d);
	}
	echo json_encode($out);
?>
