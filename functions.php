<?
// misc functions for the RBN Club Spotter

// point in polygon algorithm
include("pipa.php");
include("db.php");

function skimmers_in_polygon ($poly) {
    global $con;
    $sip = array();

    # a polygon must contain at least 4 points, first == last
    $poly = json_decode($poly, true);

    # ensure last point = first point
    $poly[] = $poly[0];

    if (!$poly or count($poly) < 4 or $poly[0]["lat"] != $poly[count($poly)-1]["lat"] or $poly[0]["lng"] != $poly[count($poly)-1]["lng"]) {
        return $sip; // empty array
    }

    # bring polygon into format accepted by pipa algo => array("lat lng", "lat lng", ...)
    $polygon = array();
    foreach ($poly as $p) {
        $polygon[] = $p["lat"]." ".$p["lng"];
    }

    $pointLocation = new pointLocation();

    # check if skimmer location is in polygon
    $q = mysqli_query($con, "select `callsign`,  `lat`, `lng`  from skimmers");
    while ($r = mysqli_fetch_row($q)) {
        $pos = "$r[1] $r[2]";
        $res = $pointLocation->pointInPolygon($pos, $polygon);    
        if($res != "outside")
           $skimmers[] = $r[0];
    }

    return $skimmers;
}

function save_polygons ($c, $p) {

    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    $ownCall = $c;
    $ownCall = preg_replace("/[^A-Z0-9\/\-]/", "", $ownCall);

    $redis->hset("rbnpolygons", $ownCall, $p);

    // save matching calls
    $skimmers = [];
    $polys = json_decode($p, true);
    foreach ($polys as $poly) {
        $sip = skimmers_in_polygon(json_encode($poly));
        $skimmers = array_merge($skimmers, $sip);
    }

    $redis->hset("rbnskimmers", $ownCall, join(" ", $skimmers));

    return "OK";
}


function calendar($p) {
    global $con;
    $num_rows = 13;

    if ($p == 0) {
        $limit = "";
    }
    else {
        $p--;
        $p *= $num_rows;
        $limit = " limit $p,$num_rows ";
    }

    $q = mysqli_query($con, "select * from calendar order by day asc, hours asc $limit;");
    $out = array();
    while ($d = mysqli_fetch_array($q, MYSQLI_ASSOC)) {
        array_push($out, $d);
    }
    return $out;
}

function save_calendar($p) {
    global $con;
    $cal = json_decode($p, true);

    for ($i = 0; $i < count($cal); $i++) {
        $day = date("Y-m-d", $cal[$i]["ts"]);
        $hours = mysqli_real_escape_string($con, $cal[$i]["hours"]);
        $name = mysqli_real_escape_string($con, $cal[$i]["name"]);
        $url = mysqli_real_escape_string($con, $cal[$i]["url"]);
        mysqli_query($con, "insert into calendar (day, hours, name, url) values ('$day', '$hours', '$name', '$url')");
    }

    return "OK";
}

function del_calendar($id, $all) {
    global $con;

    if ($all+0 == 1) {
        $q = mysqli_query($con, "select * from calendar where id='$id' limit 1");
        $r = mysqli_fetch_assoc($q);
        $name = $r["name"];
        $q = mysqli_query($con, "delete from calendar where name='$name'");
    }
    else {
        $q = mysqli_query($con, "delete from calendar where id='$id'");
    }

    return "OK";
}


?>
