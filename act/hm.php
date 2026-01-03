<?
    include_once("functions.php");
	error_reporting(0);

    # call can be a single call or space separated calls
	$c = $_GET['call'];
#    error_log("FKDEBUG0  HeatMap $c");

	$start = $_GET['start'];
	$stop  = $_GET['stop'];

	if (isset($_GET['bands'])) {
		$filter_bands = true;
		$bands_get = $_GET['bands'];
        # remove trailing comma, if any
        $bands_get = preg_replace("/,$/", '', $bands_get);
		$band_arr = explode(',', $bands_get);
        # all bands selected? if so, don't filter (and omit continents)
        if (count($band_arr) == 13) {
            $filter_bands = false;
        }
	}
	else {
		$filter_bands = false;
	}

	$c = strtoupper($c);

	if (!preg_match('/^[ A-Z0-9\/]+$/', $c)) {
		echo "error";
		return;
	}


    $calls = explode(" ", $c);
    $arr = get_multiple_calls($calls);

#    error_log("FKDEBUG2  HeatMap fetched $c");
	# 01.01.2009 = start of all data (first cell)
	$t0 = 1230768000;

	$t0 += 43200;	# mid day - some clients may display the previous
					# day depending on summer/winter time crap

	// $start and $stop are unix timestamps;
	// calculate start and stop day from that
	$startd = intval(($start - 1230768000) / 60 / 60 / 24) - 2; // 2 days just to be sure.
	$stopd  = intval(($stop  - 1230768000) / 60 / 60 / 24) + 2; //

    $utc_offset = 0;
    if ($c == "N4TY") {
        $utc_offset = 5;
    }

	# summarize days
	$out = Array();
	for ($i = $startd; $i < $stopd; $i++) {
		$bands = 0;
		for ($j = 0; $j < 24; $j++) {	
			 $val = $arr[$utc_offset + $i*24 + $j - 1];		# arr = array of hours... why -1?

			 if ($val) {
				# Apply filter bitmask to the value first
				if ($filter_bands) {
						$bm = 0x0000;
						foreach ($band_arr as $b) {
								if ($b == '160') { $bm |= 0x0001; }
								if ($b == '80') { $bm |= 0x0002; }
								if ($b == '60') { $bm |= 0x0004; }
								if ($b == '40') { $bm |= 0x0008; }
								if ($b == '30') { $bm |= 0x0010; }
								if ($b == '20') { $bm |= 0x0020; }
								if ($b == '17') { $bm |= 0x0040; }
								if ($b == '15') { $bm |= 0x0080; }
								if ($b == '12') { $bm |= 0x0100; }
								if ($b == '10') { $bm |= 0x0200; }
								if ($b == '6') { $bm |= 0x00100000; }
								if ($b == '4') { $bm |= 0x00080000; }
								if ($b == '2') { $bm |= 0x00040000; }
						}
					$val = $val & $bm;
				}

			 	$bands += ($val & 0x0001) ? 1 : 0;
			 	$bands += ($val & 0x0002) ? 1 : 0;
			 	$bands += ($val & 0x0004) ? 1 : 0;
			 	$bands += ($val & 0x0008) ? 1 : 0;
			 	$bands += ($val & 0x0010) ? 1 : 0;
			 	$bands += ($val & 0x0020) ? 1 : 0;
			 	$bands += ($val & 0x0040) ? 1 : 0;
			 	$bands += ($val & 0x0080) ? 1 : 0;
			 	$bands += ($val & 0x0100) ? 1 : 0;
			 	$bands += ($val & 0x0200) ? 1 : 0;
			 	$bands += ($val & 0x00100000) ? 1 : 0;
			 	$bands += ($val & 0x00080000) ? 1 : 0;
			 	$bands += ($val & 0x00040000) ? 1 : 0;

				# if we filter by bands, the continent count cannot be used any more
				# because it is not recorded by band
                if (!$filter_bands) {
					$bands += ($val & 0x0400) ? 1 : 0;
					$bands += ($val & 0x0800) ? 1 : 0;
					$bands += ($val & 0x1000) ? 1 : 0;
					$bands += ($val & 0x2000) ? 1 : 0;
					$bands += ($val & 0x4000) ? 1 : 0;
					$bands += ($val & 0x8000) ? 1 : 0;
				}
			 }
		}
		if ($bands > 0) {
			array_push($out, '"'.($t0 + ($i*86400)).'": ' . $bands );
		}
	}

	echo "{\n";
	echo join($out, ",\n");
	echo "}\n";

#    error_log("FKDEBUG3  HeatMap $c");

?>
