<?php
include("db.php");
?>
<!DOCTYPE html>
<html>
<head>
<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8">
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<link rel="shortcut icon" type="image/x-icon" href="/pa4n.ico">
<link rel="stylesheet" type="text/css" href="/bandmap.css">
<link rel="stylesheet" href="/js/leaflet.css">
<link rel="stylesheet" href="/js/leaflet-geoman.css"/>
<script src="js/cookies.js?cachebreak=<? echo filemtime("js/cookies.js"); ?>"></script>
<title>RBN Skimmer Filter Selection Map</title>

</head>
<h1>RBN Skimmer Filter Selection Map: <span id="ownCall"></span></h1>

<style>
#map { height: 750px; }
</style>
<div id="map"></div>

<div>Selected skimmers: <span id="skimmers"></span></div>

<div>
<button class="btn" id="save" onClick="javascript:save();">Save settings</button>
</div>

<script src="/js/leaflet-src.js"></script>
<script src="/js/leaflet-geoman.min.js"></script>

<script>

    var ownCall = getCookie('ownCall') || "";
    document.getElementById('ownCall').innerHTML = ownCall;

    var selected_skimmers = [];
    var polygons = [];

    var map = L.map('map').setView([30, 0], 2);
    var osmUrl='https://cgi2.lcwo.net/osm_tiles.php?z={z}&x={x}&y={y}';
    var osmAttrib='Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
    var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});
    map.addLayer(osm);

    map.pm.addControls({
        drawMarker: false,
        drawPolygon: true,
        editMode: true,
        drawPolyline: false,
        drawCircle: false,
        drawCircleMarker: false,
        drawText: false,
        drawRectangle: false,
        removalMode: true,
        dragMode: false,
        cutPolygon: false,
        rotateMode: false
    });

    const skimmers = L.layerGroup();

<?
  $q = mysqli_query($con, "select `callsign`, `lat`, `lng` from skimmers;");
  while ($r = mysqli_fetch_row($q)) {
      $r[0] = preg_replace('/[^\w]/', '', $r[0]);
      echo "var marker = L.marker([".$r[1].", ".$r[2]."]).bindPopup('$r[0]').addTo(skimmers);\n";
  }
?>

    map.addLayer(skimmers);
    var overlayMaps = { "Skimmers": skimmers };
    var layerControl = L.control.layers({}, overlayMaps).addTo(map);

    // check for polygons in local storage 
    var saved_polygons = JSON.parse(localStorage.getItem("polygons"));

    for (var i = 0; i < saved_polygons.length; i++) {
        // convert to suitable format for leaflet
        var arr = [];
        for (var j = 0; j < saved_polygons[i].length; j++) {
            arr.push([ saved_polygons[i][j]["lat"] , saved_polygons[i][j]["lng"] ]);
        }
        L.polygon(arr, {color: '#3388ff'}).addTo(map);
    } 
    update();

    // callbacks provided by geoman
    map.on("pm:create", (e) => { update(); });
    map.on("pm:remove", (e) => { update(); });

    // find all polygons and skimmers inside
    function update() {
        selected_skimmers = [];
        polygons = [];
        document.getElementById("skimmers").innerHTML = "";
        var l = L.PM.Utils.findLayers(map);

        for (var i = 0; i < l.length; i++) {
            if (l[i].pm._shape == "Polygon") {
                var ll = l[i].getLatLngs();
                polygons.push(ll[0]);
                add_skimmers_in_polygon(ll[0]);
            }
        }
    }

    function add_skimmers_in_polygon(poly) {
        var request =  new XMLHttpRequest();
        request.open("POST", "/api?action=skimmers_in_polygon", true);
        request.onreadystatechange = function() {
            var done = 4, ok = 200;
            if (request.readyState == done && request.status == ok) {
                var ret = JSON.parse(request.responseText);
                for (var i = 0; i < ret.length; i++) {
                    selected_skimmers.push(ret[i]);
                }
                document.getElementById("skimmers").innerHTML = selected_skimmers.join(", ");
            }
        }
        request.send(JSON.stringify(poly));
    }

    function save() {
        localStorage.setItem("polygons", JSON.stringify(polygons));
        localStorage.setItem("selected_skimmers", selected_skimmers);

        var request =  new XMLHttpRequest();
        request.open("POST", "/api?action=save_polygons&ownCall=" + ownCall, true);
        request.onreadystatechange = function() {
            var done = 4, ok = 200;
            if (request.readyState == done && request.status == ok) {
                var ret = JSON.parse(request.responseText);
                alert(ret["status"]);
            }
        }
        request.send(JSON.stringify(polygons));
    }

</script>

<hr>
<a href="/">Back to bandmap page</a>

</body>
</html>
