<?

    # $_GET['call'] can be a single call or multiple calls separated by "+"

    # user has JS disabled and ends up on ...?cl=CALL url...
    if (preg_match('/\?cl=(\w+)/', $_SERVER['REQUEST_URI'], $matches)) {
		header("HTTP/1.1 301 Moved Permanently"); 
		header("Location: http".($_SERVER['HTTPS'] ? 's' : '')."://rbn.telegraphy.de/activity/".strtoupper($matches[1])); 
    }

	$c = strtoupper($_GET['call']);
	if (!preg_match('/^[ A-Z0-9\/]+$/', $c)) {
		echo "$c is not a proper callsign.\n";
		return;
	}

    $iframe = array_key_exists('iframe', $_GET) ? '1' : '0';

	# lowercase call? 301 to uppercase so we have
	# ONE URL for every call
	if ($c != $_GET['call']) {
            # change spaces to plus again...
            $c = preg_replace("/ /", "+", $c);
			header("HTTP/1.1 301 Moved Permanently"); 
			header("Location: http".($_SERVER['HTTPS'] ? 's' : '')."://rbn.telegraphy.de/activity/$c"); 
	}

    # change spaces (used for multiple calls) to plus again...
    $c = preg_replace("/ /", "+", $c);

?>
<!DOCTYPE html>
<html>
<head>
<script src="/act/d3/d3.min.js" charset="utf-8"></script>
<link rel="stylesheet" href="/act/cal/cal-heatmap.css" />
<link rel="stylesheet" href="/bandmap.css" />
<script type="text/javascript" src="/act/cal/cal-heatmap.min.js"></script>
<script>
	function in_iframe () {
			try {
					return window.self !== window.top;
			} catch (e) {
					return true;
			}
	}

	// Hide everything we don't need/want when embedding,
	// and show "banner" link
	function go_iframe () {
			var eh = Array('head', 'buttons', 'form', 'explain', 'hover');
			var v,l;

			for (var i = 0; i < eh.length; i++) {
                if (document.getElementById(eh[i]) != null) {
    				document.getElementById(eh[i]).style.display = 'none';
                }
			}

			v = document.getElementById('jsneeded');
			l = document.getElementById('mylinks');

			v.style.display = 'block';
			v.innerHTML = '<span style="font-weight:bold;font-size:14px">RBN activity statistics for <?=$c?></span>';
			l.innerHTML = '<code style="font-family:monospace">https://rbn.telegraphy.de/activity/</code> - Search for any callsign and create your own embeddable RBN widget! A free service by DJ1YFK.';
	}

</script>
<title>RBN activity of <?=$c?></title>
</head>
<body>
<noscript>
<a target="_new" href="https://rbn.telegraphy.de/activity/<?=$c?>"><img src="https://rbn.telegraphy.de/activity/image/<?=$c?>"></a><br><br><br><br>
</noscript>
<span id='jsneeded'><b>This page requires JavaScript to work properly.</b> QRZ.com disabled JavaScript for Iframes (see <a href="https://forums.qrz.com/index.php?threads/qrz-service-update-biography-pages.600972/">the announcement at qrz.com</a>...<br>
<b>Workaround for QRZ.com:</b> Embed the static graphic version instead: <a href="//rbn.telegraphy.de/activity/image/<?=$c;?>">rbn.telegraphy.de/activity/image/<?=$c;?></a>.</span>
<div id="head">
<h1>RBN activity of <?=$c?>
<?
    if ($c == "VE5SDH") {
        echo " †";
    }
?>
</h1>
<p>The heatmap shows the activity as reported by the RBN (CW/PSK/RTTY, no FT8) of the last 12 months. You can scroll back to January 2015.</p>
</div>
<div id="cal-heatmap"></div>
<script type="text/javascript">
	// Start date should be the 1st of a month, one year back
	var now = new Date();
	var startDate = new Date(now.getFullYear() - 1, now.getMonth() + 1);
	var locale = '';

    function printts(ts) {
        var newDate = new Date();
        newDate.setTime(ts*1000);
        console.log(newDate.toUTCString());
    }

	if (navigator.browserLanguage) {
    	locale =  navigator.browserLanguage;
	}
	else if (navigator.language) {
	    locale =  navigator.language;
	}

	var week_start_monday = (locale.indexOf('US') >= 0) ? false : true;

	var cal = new CalHeatMap();
	cal.init({
		itemName: ["point", "points"],
		label: { position: "top" },
		cellSize: 7,
		domainGutter: 5,
		legend: [2, 5, 20, 50, 380],
		highlight: "now",
		start: startDate,
		range: 12,
		weekStartOnMonday: week_start_monday,
		domain: "month",
		subDomain: "x_day",
		data: "/act/hm.php?call=<?=$c?>&start={{t:start}}&stop={{t:end}}",
		afterLoadData: function (ts) {	// cal-heatmap displays local time, so add the offset from UTC
			var offset = parseInt(new Date().getTimezoneOffset() * 60);
			var ret =  {};
			for (var t in ts) {
				var tint = parseInt(t);
				ret[tint + offset] = ts[t]; 
			}
			return ret;
		},
		onClick: function(d, nb) {
            var ts = d.getTime() / 1000;	// this is local time, seconds epoch
            printts(ts);

			var offset = parseInt(new Date().getTimezoneOffset() * 60);
			// adjusted for UTC 
			ts += offset;

            printts(ts);

			// BUT to get 00:00Z UTC for any day,
			// i.e. also days that are in winter time while local time
			// is in summer, or summer time, while local time is winter
			// time, we need to construct the day ourselves...
			ts += 60*60*6; 		// go to 6am for good measure

            printts(ts);

			// convert back to date object
			var dob = new Date(ts*1000);

			// construct new date at 00:00 UTC that day
			var zeroutc = new Date(Date.UTC(dob.getUTCFullYear(),
					dob.getUTCMonth(),
					dob.getUTCDate(),	// NB: Date = Day of month!
				 0, 0, 0));

            var newts = zeroutc / 1000 - 60*60; // 1h offset in API (FIXME)
            stat_load (newts, 1);	 
            printts(newts);
            var sd = document.getElementById('singleday');
            var now = parseInt(new Date() / 1000);
            now -= 365*24*60*60;
            sd.innerHTML = 'Showing data for ' + d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate() + ' - ';
            sd.innerHTML += ' <a href="javascript:stat_load (' + now + ', 365);">Show full data again.</a>';
		}
	});
</script>

<script type="text/javascript">
	var show_bands = new Array(161);
	show_bands[160] = true;
	show_bands[80] = true;
	show_bands[60] = true;
	show_bands[40] = true;
	show_bands[30] = true;
	show_bands[20] = true;
	show_bands[17] = true;
	show_bands[15] = true;
	show_bands[12] = true;
	show_bands[10] = true;
	show_bands[6] = true;
	show_bands[4] = true;
	show_bands[2] = true;

	function filter (band) {
			var btn = document.getElementById(band);
			if (show_bands[band] == true) {
				btn.style.color = '#cccccc';
				show_bands[band] = false;
			}
			else {
				btn.style.color = '#000000';
				show_bands[band] = true;
			}
			var bandlist = '';
			bandlist += (show_bands[160] ? '160,' : '');
			bandlist += (show_bands[80] ? '80,' : '');
			bandlist += (show_bands[60] ? '60,' : '');
			bandlist += (show_bands[40] ? '40,' : '');
			bandlist += (show_bands[30] ? '30,' : '');
			bandlist += (show_bands[20] ? '20,' : '');
			bandlist += (show_bands[17] ? '17,' : '');
			bandlist += (show_bands[15] ? '15,' : '');
			bandlist += (show_bands[12] ? '12,' : '');
			bandlist += (show_bands[10] ? '10,' : '');
			bandlist += (show_bands[6] ? '6,' : '');
			bandlist += (show_bands[4] ? '4,' : '');
			bandlist += (show_bands[2] ? '2,' : '');
			cal.update("/act/hm.php?call=<?=$c?>&start={{t:start}}&stop={{t:end}}&bands=" + bandlist, false);
	}
</script>

<br>
<div id="buttons" style="float:none;margin-bottom: 20px;">
<button class="btn" id="previous" onClick="javascript:cal.previous(1);stat_change(-1);">Previous</button>
<button class="btn" id="next" onClick="javascript:cal.next(1);stat_change(1);">Next</button>

&nbsp; Filter: 
<button class="btn" id="160" onClick="javascript:filter(this.id);">160m</button>
<button class="btn" id="80" onClick="javascript:filter(this.id);">80m</button>
<button class="btn" id="60" onClick="javascript:filter(this.id);">60m</button>
<button class="btn" id="40" onClick="javascript:filter(this.id);">40m</button>
<button class="btn" id="30" onClick="javascript:filter(this.id);">30m</button>
<button class="btn" id="20" onClick="javascript:filter(this.id);">20m</button>
<button class="btn" id="17" onClick="javascript:filter(this.id);">17m</button>
<button class="btn" id="15" onClick="javascript:filter(this.id);">15m</button>
<button class="btn" id="12" onClick="javascript:filter(this.id);">12m</button>
<button class="btn" id="10" onClick="javascript:filter(this.id);">10m</button>
<button class="btn" id="6" onClick="javascript:filter(this.id);">6m</button>
<button class="btn" id="4" onClick="javascript:filter(this.id);">4m</button>
<button class="btn" id="2" onClick="javascript:filter(this.id);">2m</button>
</div>
	<script>
	// dynamically load the right time frame into stat_div
	// which is queried by XHR and returned as full HTML content.

	var callsign = '<?=$c;?>';

	// ts = time when the page was loaded
	var ts = new Date().getTime()/1000;  
	ts -= 365*24*60*60;	// start time 1 year back


	function stat_change(offset) {	// -1 or +1 month
			ts += offset * 365/12 * 24 * 60 * 60;   // this is not precise :<
			stat_load(Math.floor(ts), 365);
	}

	function stat_load (ts, days) {
        var request =  new XMLHttpRequest();
        request.open("GET", '/act/stat?call='+callsign + '&start=' + ts + '&days=' + days, true);
        request.onreadystatechange = function() {
                var done = 4, ok = 200;
                if (request.readyState == done && request.status == ok) {
                        if (request.responseText) {
                                var s = document.getElementById('stat_div');
								s.innerHTML = request.responseText;
                        }
                };
        }
        request.send();
	}
	</script>


<div id="stat_div">
<?
	include('stats.php');
	echo print_stats($c, time() - 365*24*60*60, 365);
?>
</div>
<div id='form'>
<br>
<form onSubmit="javascript:document.location.href='//rbn.telegraphy.de/activity/' + this.cl.value.toUpperCase(); return false;">
Generate this report for any call: <input type="text" size="12" name="cl">
<input type="submit" value="Go!"> - <a href="/activity/faq">FAQ - Data Removal - etc.</a>
 - You can generate joint reports for two calls, e.g. <a href="/activity/DJ1YFK+SO5CW">DJ1YFK+SO5CW</a>
</form>
</div>

<? if ($c ==  'T2DE' or $c == 'T2DEJ') { ?>
<p><b>Note:</b> This is not a proper callsign. <b>T2DE</b> is the prosign &lt;DO&gt; (which initiates Japanese <a href="https://en.wikipedia.org/wiki/Wabun_code">Wabun code</a>) sent with wrong spacing, plus <em>DE</em>. Skimmer spots appear if a station calls <i>CQ DO DE JA...</i> but does not use exact spacing, i.e. sends <i>CQ T2DE JA...</i>.
<? } ?>
<div id='explain'>
<p>Daily activity is measured by the number of active hour slots (24) times band slots (10, 160m - 10m incl. WARC and 60m) and the number of continents in which
a skimmer spotted the station in the respective hour, for a maximum daily score of 24 * (10 + 6) = 384. When filtering by band, the continent information is omitted, because it's only saved once per slot, not per band.</p>
<p id='singleday'>Click a single day on the heatmap to see its details.</p>
<div id="embed">
<p>You can embed the statistics on your own website or profile on <a href="https://www.hamqth.com/">HamQTH</a>/QRZ.com by copying the following HTML snippet: (example: <a href="https://www.hamqth.com/dj1yfk">DJ1YFK</a> on HamQTH.com - <a href="http://rbn.telegraphy.de/activity/faq#embed">Click here for details.</a>)</p>
<pre style="background-color:#eeeeef">
&lt;a href="https://rbn.telegraphy.de/activity/<?=$c;?>"&gt;&lt;img src="https://rbn.telegraphy.de/activity/image/<?=$c;?>"&gt;&lt;/a&gt;
</pre>
</div>
<hr>
</div>
<div id='mylinks'>
<p>Thanks to PY1NB and the team of the <a href="http://www.reversebeacon.net/">Reverse Beacon Network</a> for the aggregated RBN data.</p>
<hr>
<a href="https://foc.dj1yfk.de/bandmap">FOC RBN</a> - <a href="https://rbn.telegraphy.de">CW Club RBN Spotter</a> - <a href="/activity/rank">RBN Activity Rank</a> - <a href="/activity/stats">RBN Activity Statistics</a>
<hr>
<p>Last modified: <? echo date ("Y-m-d",  filemtime("active.php")); ?> - <a href="http://fkurz.net/">Fabian Kurz, DJ1YFK</a> <a href="mailto:fabian@fkurz.net">&lt;fabian@fkurz.net&gt;</a>
<?
	if (!$_SERVER['HTTPS']) { ?> - <a rel="nofollow" href="https://rbn.telegraphy.de/activity/<?=$c;?>">Switch to https</a> <? }
	else { ?> - <a rel="nofollow" href="http://rbn.telegraphy.de/activity/<?=$c;?>">Switch to http</a> <? }
?>
- <a href="/privacy">Impressum / Datenschutz / Privacy Policy</a>
</div>

<script>
	document.getElementById('jsneeded').style.display = 'none';
    if (in_iframe() || <?=$iframe;?>) {
			go_iframe();
	}
</script>
<!-- Page rendered in  <? echo 1000*(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);  ?> ms -->
</body>
</html>
