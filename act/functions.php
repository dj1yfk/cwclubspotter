<?
    function get_multiple_calls ($calls) {
        error_reporting(0);
        include_once('db.php');
        $db = mysqli_connect($mysql_host,$mysql_user,$mysql_pass, $mysql_dbname) or die ("<h1>Sorry: Could not connect to database..</h1>");
        $arr = Array();   # final array
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
        $q = mysqli_query($db, "select data, wl, dxcc from rbn_activity where callsign='$c' and wl=1;");
        if (!$q) {
            echo "<p>Database error.</p>\n";
            return;
        }

        $res = mysqli_fetch_row($q);

        if (!$res) {
           $arr = Array(); 
           $arr = array_pad($arr, 596064, 0);
        }
        else {
            $f = gzdecode ($res[0]);
            $hours = strlen($f)/4;
            $arr = unpack("V".$hours, $f);
            # NB: arr comes as associative array with index
            # starting at 1. Fix this into array with 0 ... end
            $arr[0] = 0;
            array_shift($arr);
        }

        # today's data available in Redis?
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $rd = $redis->get("RBN2live".$c);
        # merge into data from permanent database
        if ($rd) {
            $rd2 = gzdecode($rd);
            $hours = strlen($rd2)/4;
            $arr_redis = unpack("V".$hours, $rd2);
            $arr_redis[0] = 0;
            array_shift($arr_redis);
            # error_log("redis for $c fetched. length: $hours");
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] |=  $arr_redis[$i];
            }
        }

        return $arr;
    }

?>
