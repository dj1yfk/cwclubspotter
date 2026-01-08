<?
    function get_multiple_calls ($calls) {
        error_reporting(0);
        include_once('db.php');
        $db = mysqli_connect($mysql_host,$mysql_user,$mysql_pass, $mysql_dbname) or die ("<h1>Sorry: Could not connect to database..</h1>");

        # single call? no need to merge 
        if (count($calls) == 1) {
            $arr = get_arr_from_db($db, $calls[0]);
            mysqli_close($db);
            return $arr;
        }

        # several calls - merge. prepopulate array for speed.
        $arr = Array();   # final array
        $arr = array_pad($arr, 876000, 0);
        foreach ($calls as $c) {
            $tmp = get_arr_from_db($db, $c);
            for ($i = 0; $i < count($tmp); $i++) {
                $arr[$i] |= $tmp[$i];
            }
        }
        mysqli_close($db);
        return $arr;
    }

    function get_arr_from_db ($db, $c) {
        error_reporting(0);
        #error_log("FKDEBUG0 fetch $c");

        # Do we have this data cached?
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $rd = $redis->get("RBNcache".$c);
        if ($rd) {
            return unserialize($rd);
        }

        $q = mysqli_query($db, "select data, wl, dxcc from rbn_activity where callsign='$c' and wl=1;");
        if (!$q) {
            echo "<p>Database error.</p>\n";
            return;
        }

        $res = mysqli_fetch_row($q);
        #error_log("FKDEBUG1 fetched from DB $c");

        if (!$res) {
           $arr = Array(); 
           $arr = array_pad($arr, 876000, 0);
        }
        else {
            $f = gzdecode ($res[0]);
            $hours = strlen($f)/4;
            $arr = unpack("V".$hours, $f);
            # NB: arr comes as associative array with index
            # starting at 1. Fix this into array with 0 ... end
            $arr[0] = 0;
            array_shift($arr);

            # old format of 596064 bytes? pad to 876000 bytes (25 years from
            # 2009)
            $arr = array_pad($arr, 876000, 0);
        }
        #error_log("FKDEBUG2 decoded from DB$c");

        # today's data available in Redis?
        $rd = $redis->get("RBNlive3".$c);
        #error_log("FKDEBUG3 fetched from Redis $c");
        # merge into data from permanent database
        if ($rd) {
            $rd2 = gzdecode($rd);
            #error_log("FKDEBUG4 decoded from Redis $c");
            $hours = strlen($rd2)/4;
            $arr_redis = unpack("V".$hours, $rd2);
            $arr_redis[0] = 0;
            array_shift($arr_redis);
            # error_log("redis for $c fetched. length: $hours");
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] |= $arr_redis[$i];
            }
            #error_log("FKDEBUG5 merged $c");
        }
        #error_log("FKDEBUG6 done $c");

        $redis->set("RBNcache".$c, serialize($arr), 10);

        return $arr;
    }

?>
