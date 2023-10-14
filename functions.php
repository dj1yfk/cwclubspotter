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



?>
