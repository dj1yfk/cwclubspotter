<?php
if ($_GET["b"]) {
    $bcn = true;
    $bcntext = " beacon ";
}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="/bandmap.css" />
<script>
</script>
    <title>RBN activity <?=$bcntext?> ranking</title>
</head>
<body>
<noscript>This page requires JavaScript to work properly</noscript>
<h1>RBN activity <?=$bcntext?> ranking</h1>

<p>
Type a <?=$bcntext?> callsign (or partial callsign) and see its position in the world wide RBN <?=$bcntext?> activity rank.<br>
Results are updates as you type. <a href="mailto:fabian@fkurz.net">Comments? Write me!</a> See also: <a href="/activity/stats">Statistics</a> - 

<?
if($bcn) {
?>
    <a href="/activity/rank">RBN Ranking (non beacons)</a>.
<?
}
else {
?>
    <a href="/activity/beaconrank">RBN Ranking for beacons</a>.
<?
}
?>

</p>

<form>
	<input onkeyup="page=1;update_rank();" onsubmit="return false;" type="text" size="15" id="cs" value="" placeholder="<?=$bcntext?>callsign...">
	 Per page: <a href="javascript:change_size(25);">25</a> - 
	<a href="javascript:change_size(50);">50</a> - 
    <a href="javascript:change_size(100);">100</a> -
    DXCC: <select name="dxcc" id="dxcc" size=1 onchange="update_dxcc();">
       <option>all</option>
<?php
    include("dxcc-list.php");
    foreach ($dxccs as $dxcc) {
        echo "<option>$dxcc</option>\n";
    }
?>
    </select>
</form>

<br>

<div id="navtop"></div>
<div id="rankcalls"></div>
<div id="navbot"></div>

<script>
var page = 1;	// page starts at 1
var size = 50;  // elements per page
var dxcc = 'all';  // limit to DXCC
var highlight_rank = 0;

window.onload = function () {
		get_hash_value();
		update_rank();
}

function update_dxcc () {
    dxcc = document.getElementById('dxcc').value;
    page = 1;
    highlight_rank = 0;
    // update URL
	var url = document.URL;
    var u = url.split('#');
    window.location.href = u[0] + "#" + dxcc
    update_rank();
}

function update_rank () {
		try {
				var c = document.getElementById('cs').value;
				// Sanity checks passed, now send to API
				var request =  new XMLHttpRequest();
				request.open("GET", '/act/r.php?<? if ($bcn == true) {echo "b=1&";} ?>c='+c+'&o='+ ((page - 1)* size) + '&n=' + size + '&d=' + dxcc, true);
				request.onreadystatechange = function() {
						var done = 4, ok = 200;
						if (request.readyState == done && request.status == ok) {
								if (request.responseText) {
										var o = JSON.parse(request.responseText);
										create_table(o);
										create_nav();
								}
						};
				}
				request.send();

		}
		catch (e) {
				console.log("exception");
		}
}

function create_table (o) {

    var show_ww_rank = (dxcc != 'all') || (document.getElementById('cs').value != "");

    var t = "<table><tr><th style='width:50px'>Rank</th>";
    if (show_ww_rank) {
        t += '<th>WW Rank</th>';
    } 
    t += "<th style='width:50px'>h / year</th><th style='width:50px'>h / day</th><th style='width:180px'>Callsign</th></tr>";
		for (var i = 0; i < o.length; i++) {
				var round_hours = Math.round(o[i].hours / 36.5) / 10;
				var call = o[i].callsign;

				if (highlight_rank > 0 && o[i].rank == highlight_rank) {
						call = '<b>' + o[i].callsign + '</b>';
				}


                t += "<tr><td>" + o[i].rank + "</td>";
                if (show_ww_rank) {
                    t += "<td>" + o[i].wwrank + "</td>";
                } 

                t += "<td>" + o[i].hours + "</td><td>" + round_hours + "</td><td><a href='/activity/" + o[i].callsign + "'>"+ call  +"</a></td></tr>";
		}
		t += "</table>";
		var d = document.getElementById('rankcalls');
		d.innerHTML = t;
}


function create_nav () {
		var nav = '<p>';
		nav += '<span style="display:inline-block;width:30px;text-align:center;"><a href="javascript:move(-1*(page-1))">start</a></span>';
		nav += navlink(-100);
		nav += navlink(-10);
		nav += navlink(-1);
		nav += '<span style="display:inline-block;width:60px;text-align:center;background:#cccccc;">Page ' + page + '</span>';
		nav += navlink(1);
		nav += navlink(10);
		nav += navlink(100);
		nav += '</p>';
		document.getElementById('navtop').innerHTML = nav;
		document.getElementById('navbot').innerHTML = nav;
}

function navlink (offset) {
		var	l = '<span style="display:inline-block;width:30px;text-align:center;">';

		if (-1*offset < page ) {
				l += '<a href="javascript:move(' + offset + ');">' + (offset > 0 ? '+' : '') + offset + '</a>';
		}
		else {
				l += offset;
		}
		l += '</span>';

		return l;
}

function move (o) {
		page += o;
		update_rank();	
}

function change_size (n) {
		size = n;
		update_rank();
}

function get_hash_value () {
		var url = document.URL;
		var u = url.split('#');

        // possible values after the #: DXCC or Rank number
        if (u.length == 2) {

            // DXCC must contain a letter
            if (u[1].match(/[a-z]/i)) {
                dxcc = u[1];
                console.log("DXCC = " + dxcc);
            }  
            else {  // assume it's a rank number
				page = Math.ceil(u[1] / 50);
				highlight_rank = u[1];
                console.log("Rank highlight = " + highlight_rank);
            }
		}
}
</script>


<hr>
<a href="/">Back to RBN</a> - <a href="/activity">Back to RBN Activity Charts</a>
<hr>
<p>Last modified: <? echo date ("Y-m-d",  filemtime("active.php")); ?> - <a href="http://fkurz.net/">Fabian Kurz, DJ5CW</a> <a href="mailto:fabian@fkurz.net">&lt;fabian@fkurz.net&gt;</a>
<?
	if (!$_SERVER['HTTPS']) { ?> - <a rel="nofollow" href="https://rbn.telegraphy.de/activity/rank">Switch to https</a> <? }
	else { ?> - <a rel="nofollow" href="http://rbn.telegraphy.de/activity/rank">Switch to http</a> <? }
?>
- <a href="/privacy">Impressum / Datenschutz / Privacy Policy</a>
</div>
<!-- Page rendered in  <? echo 1000*(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);  ?> ms -->
</body>
</html>
