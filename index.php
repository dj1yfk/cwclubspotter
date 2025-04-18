<!DOCTYPE html>

<!-- RBN / DX Cluster Spotter view                               	-->
<!-- Fabian Kurz, DJ5CW (ex DJ1YFK) <fabian@fkurz.net>              -->
<!-- Frank R. Oppedijk, PA4N <pa4n@xs4all.nl>                       -->
<!-- https://git.fkurz.net/dj1yfk/cwclubspotter                     -->
<!-- This code is in the public domain.                             -->

<?php
include_once("clubs.php");
?>


<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="shortcut icon" type="image/x-icon" href="/pa4n.ico">
<link rel="stylesheet" type="text/css" href="/bandmap.css">
<title>CW Club RBN Spotter</title>
<script src="js/cookies.js?cachebreak=<? echo filemtime("js/cookies.js"); ?>"></script>
<script src="js/members.js?cachebreak=<? echo time(); ?>"></script>

</head>
<body onload="init_rbn();">
<audio id="cwplayer"></audio>
<h1>CW Club RBN Spotter</h1>

<p>The table shows recent RBN spots of (optionally filtered by CW club members) in a dynamically updated
bandmap (also available via telnet).
See <a href="info">here</a> for more info. &nbsp; <span style="color:red;"></span> <span id="upd"></span></p>

<a id="filterChoice" href="javascript:toggleFilter();">hide filter</a> - 
<a id="freqChoice" href="javascript:toggleFreq();">hide frequencies</a>
<div id="filter" style="display: block">
<form onSubmit="filter_change();return false;">
<table>
<tr><th>Members of</th><td colspan=2> 	
<button type="button" onclick="set_all('club', true)">all clubs</button>
</td>
<?php
$ccnt = 0;
foreach ($clubs as $c) {
    $ccnt++;
    echo "<td><input onclick='filter_change();' id='cb$c' type='checkbox' name='cb$c' value='1' checked><abbr title='".$clubname[$c]."'>".$clubabbr[$c]."</abbr></td>";
    if ($ccnt == 18) {
        echo "</tr><tr><th></th><td colspan=2> <button type=\"button\" onclick=\"set_all('club', false)\">no filter</button> </td>";
    }
}
?>
</tr>
<tr><th>Bands</th><td> 	
<button type="button" onclick="set_all('band', true)">all</button>
</td><td>
<button type="button" onclick="set_all('band', false)">nil</button>
</td><td>
<input onclick="filter_change()" id="cb160" type="checkbox" name="cb160" value="1" checked>160
</td><td>
<input onclick="filter_change()" id="cb80" type="checkbox" name="cb80" value="1" checked>80
</td><td>
<input onclick="filter_change()" id="cb60" type="checkbox" name="cb60" value="1" checked>60
</td><td>
<input onclick="filter_change()" id="cb40" type="checkbox" name="cb40" value="1" checked>40
</td><td>
<input onclick="filter_change()" id="cb30" type="checkbox" name="cb30" value="1" checked>30
</td><td>
<input onclick="filter_change()" id="cb20" type="checkbox" name="cb20" value="1" checked>20
</td><td>
<input onclick="filter_change()" id="cb17" type="checkbox" name="cb17" value="1" checked>17
</td><td>
<input onclick="filter_change()" id="cb15" type="checkbox" name="cb15" value="1" checked>15
</td><td>
<input onclick="filter_change()" id="cb12" type="checkbox" name="cb12" value="1" checked>12
</td><td>
<input onclick="filter_change()" id="cb10" type="checkbox" name="cb10" value="1" checked>10
</td><td>
<input onclick="filter_change()" id="cb6" type="checkbox" name="cb6" value="1" checked>6
</td></tr>
<tr><th>Speeds</th><td>
<button type="button" onclick="set_all('speed', true)">all</button>
</td><td>
<button type="button" onclick="set_all('speed', false)">nil</button>
</td><td>
<input onclick="filter_change()" id="cb<10" type="checkbox" name="cb<10" value="1" checked>&lt;10
</td><td>
<input onclick="filter_change()" id="cb10-14" type="checkbox" name="cb10-14" value="1" checked>10-14
</td><td>
<input onclick="filter_change()" id="cb15-19" type="checkbox" name="cb15-19" value="1" checked>15-19
</td><td>
<input onclick="filter_change()" id="cb20-24" type="checkbox" name="cb20-24" value="1" checked>20-24
</td><td>
<input onclick="filter_change()" id="cb25-29" type="checkbox" name="cb25-29" value="1" checked>25-29
</td><td>
<input onclick="filter_change()" id="cb30-34" type="checkbox" name="cb30-34" value="1" checked>30-34
</td><td>
<input onclick="filter_change()" id="cb35-39" type="checkbox" name="cb35-39" value="1" checked>35-39
</td><td>
<input onclick="filter_change()" id="cb>39" type="checkbox" name="cb>39" value="1" checked>&gt;39
</td></tr>
<tr><th>Call filter</th><td colspan="2">
<input onblur="filter_change()" id="callFilter" type="text" size="6" name="callFilter" value="*">
</td></tr>
<tr><th>Own call</th><td colspan="2">
<input onblur="filter_change()" id="ownCall" type="text" size="6" name="ownCall" value="">
</td>
<td colspan="5">
<span id="callmsg"></span>
<input onclick="filter_change()" id="selfSpots" type="checkbox" name="selfSpots" value="0" >Include self-spots
</td>
</tr>
<tr><th>Skimmers from</th><td>
<button type="button" onclick="set_all('cont', true)">all</button>
</td><td>
<button type="button" onclick="set_all('cont', false)">nil</button>
</td><td>
<input onclick="filter_change()" id="cbEU" type="checkbox" name="cbEU" value="1" checked>EU
</td><td>
<input onclick="filter_change()" id="cbNA" type="checkbox" name="cbNA" value="1" checked>NA
</td><td>
<input onclick="filter_change()" id="cbAS" type="checkbox" name="cbAS" value="1" checked>AS
</td><td>
<input onclick="filter_change()" id="cbSA" type="checkbox" name="cbSA" value="1" checked>SA
</td><td>
<input onclick="filter_change()" id="cbAF" type="checkbox" name="cbAF" value="1" checked>AF
</td><td>
<input onclick="filter_change()" id="cbOC" type="checkbox" name="cbOC" value="1" checked>OC
</td><td colspan=3>
<input onclick="filter_change()" id="cbCS" type="checkbox" name="cbCS" value="1" checked><a href="/geofilter">Custom</a>
<tr><th>Max spot age</th><td>
<input onclick="filter_change()" id="maxAge5" type="radio" name="maxAge" value="5">5
</td><td>
<input onclick="filter_change()" id="maxAge10" type="radio" name="maxAge" value="10">10
</td><td>
<input onclick="filter_change()" id="maxAge15" type="radio" name="maxAge" value="15">15
</td><td>
<input onclick="filter_change()" id="maxAge20" type="radio" name="maxAge" value="20" checked>20
</td><td>
<input onclick="filter_change()" id="maxAge25" type="radio" name="maxAge" value="25">25
</td><td>
<input onclick="filter_change()" id="maxAge30" type="radio" name="maxAge" value="30">30
</td><td>
<input onclick="filter_change()" id="maxAge35" type="radio" name="maxAge" value="35">35
</td><td>
<input onclick="filter_change()" id="maxAge40" type="radio" name="maxAge" value="40">40
</td><td>
<input onclick="filter_change()" id="maxAge45" type="radio" name="maxAge" value="45">45
</td><td>
<input onclick="filter_change()" id="maxAge50" type="radio" name="maxAge" value="50">50
</td><td>
<input onclick="filter_change()" id="maxAge55" type="radio" name="maxAge" value="55">55
</td><td>
<input onclick="filter_change()" id="maxAge60" type="radio" name="maxAge" value="60">60
</td></tr>
<tr><th>Refresh rate</th>
<td> <input onclick="filter_change()" id="refresh30" type="radio" name="refresh" value="30">30 s</td>
<td> <input onclick="filter_change()" id="refresh60" type="radio" name="refresh" value="60">60 s</td>
<td> <input onclick="filter_change()" id="refresh120" type="radio" name="refresh" value="120">120 s</td>
<td> <input onclick="filter_change()" id="refresh180" type="radio" name="refresh" value="180">180 s</td>
</tr>
<tr><th>Sort by</th><td>
<input onclick="filter_change();" id="sort1" type="radio" name="sort" value="1">Freq
</td><td>
<input onclick="filter_change();" id="sort2" type="radio" name="sort" value="2">Call
</td><td>
<input onclick="filter_change();" id="sort3" type="radio" name="sort" value="3">Age
</td><td>
<input onclick="filter_change();" id="sort4" type="radio" name="sort" value="4">Club
</td><td>
<input onclick="filter_change();" id="sort5" type="radio" name="sort" value="5">Speed
</td><td>
<input onclick="filter_change();" id="sort6" type="radio" name="sort" value="6">Spotter
</td></tr>
<tr>
<th>Misc.</th>
<td colspan=19>
<input onclick="filter_change()" id="cbAC" type="checkbox" name="cbAC" value="1">Abbreviate Club Names
&nbsp;
&nbsp;
&nbsp;
&nbsp;
<input onclick="filter_change()" id="cbT9" type="checkbox" name="cbT9" value="1"><a href="https://internationalcwcouncil.org/top9-activity/">Top 9</a> QRGs only
&nbsp;
&nbsp;
&nbsp;
&nbsp;
Click on call links to: <select onChange="filter_change();" id="linktarget" size="1">
<option value="qrz">QRZ.com</option>
<option value="rbn">RBN Activity Report</option>
<option value="hamqth">hamqth.com</option>
</select>

&nbsp;
&nbsp;
&nbsp;
&nbsp;

RBN Activity Lookup: 
<input id="rbna" type="text" size="12" name="rbna" placeholder="callsign..." value="">
<button type="button" onClick="document.location.href='//rbn.telegraphy.de/activity/' + document.getElementById('rbna').value">Go!</button>


&nbsp;
&nbsp;
&nbsp;
&nbsp;

<a href="/activity/rank">RBN Activity Ranking</a>

</td>
</tr>
<tr>
<th>Alerts</th>
<td colspan=19>
<input onblur="filter_change()" id="alerts" type="text" size="80" name="alerts" value="">
<input onclick="filter_change()" id="cbAlertVisual" type="checkbox" name="cbAlertVisual" value="1" checked> Visual alert &nbsp; &nbsp;
<input onclick="filter_change()" id="cbAlertAudio" type="checkbox" name="cbAlertAudio" value="1" checked> Audio alert (CW) &nbsp; &nbsp; <a href="/info#alerts">Alert help</a>
</td>
</tr>


<th>Awards</th>
<td colspan=19>
<b>CWops:</b> 
<input onclick="filter_change()" id="cbAwardACA" type="checkbox" name="cbAwardACA" value="1" checked> ACA
<input onclick="filter_change()" id="cbAwardCMA" type="checkbox" name="cbAwardCMA" value="1" checked> CMA

&nbsp;
<b>FOC:</b>
<input onclick="filter_change()" id="cbAwardAUG" type="checkbox" name="cbAwardAUG" value="1" checked> Augie
<input onclick="filter_change()" id="cbAwardW1" type="checkbox" name="cbAwardW1" value="1" checked> Windle (one QSO / year) 
<input onclick="filter_change()" id="cbAwardW" type="checkbox" name="cbAwardW" value="1"> Windle (normal) 
<input onclick="filter_change()" id="cbAwardABC" type="checkbox" name="cbAwardABC" value="1"> ABC
<input onclick="filter_change()" id="cbAwardWAFOC" type="checkbox" name="cbAwardWAFOC" value="1"> WAFOC

&nbsp;&nbsp;
<b>Options:</b>
<input onclick="filter_change()" id="cbAwardFilter" type="checkbox" name="cbAwardFilter" value="1"> 
Show <i>only</i> needed stations on bandmap
<input onclick="filter_change()" id="cbAwardAudio" type="checkbox" name="cbAwardAudio" value="1"> 
Audio alert (CW)

&nbsp;&nbsp;

<button type="button" onclick="load_awards();" title="Reload award data from server">↻</button>

&nbsp; &nbsp; <a href="/info#awards">Awards help</a>

</td>
</tr>


</table>
</form>
</div>
<br/>

<div id="frequencies" style="display: block">
<table style="border:none;">
<tr>
<td valign="top">
<table>
<tr><th>Club</th><th>160m</th><th>80m</th><th>60m</th><th>40m</th><th>30m</th><th>20m</th><th>17m</th><th>15m</th><th>12m</th><th>10m</th><th>6m</th></tr>
<tr><th>HSC, VHSC, SHSC, EHSC</th><td>-</td><td>3.525 / 3.567</td><td>-</td><td>7.024 / 7.025</td><td>10.125</td><td>14.025</td><td>-</td><td>21.025</td><td>-</td><td>28.025</td><td>-</td></tr>
<tr><th>CWops</th><td>1.818</td><td>3.528</td><td>-</td><td>7.028</td><td>10.118</td><td>14.028</td><td>18.078</td><td>21.028</td><td>24.908</td><td>28.028</td><td>-</td></tr>
<tr><th>FISTS</th><td>1.818</td><td>3.558</td><td>-</td><td>7.028</td><td>10.118</td><td>14.058</td><td>18.085</td><td>21.058</td><td>24.908</td><td>28.058</td><td>50.058</td></tr>
<tr><th>FOC</th><td>1.825</td><td>3.525</td><td>5.373</td><td>7.025</td><td>10.125</td><td>14.025</td><td>18.080</td><td>21.025</td><td>24.905</td><td>28.025</td><td>50.095</td></tr>
<tr><th>SKCC</th><td>1.8135</td><td>3.530 / 3.550</td><td>-</td><td>7.038 / 7.055 / 7.114</td><td>10.120</td><td>14.050 / 14.114</td><td>18.080</td><td>21.050 / 21.114</td><td>24.910</td><td>28.050 / 28.114</td><td>50.090</td></tr>
<tr><th>NAQCC</th><td>1.810 / 1.843</td><td>3.560</td><td>-</td><td>7.030 / 7.040</td><td>10.106 / 10.116</td><td>14.060</td><td>18.096</td><td>21.060</td><td>24.906</td><td>28.060</td><td>50.096</td></tr>
<tr><th>UFT</th><td>1.835</td><td>3.545</td><td>5.352</td><td>7.013</td><td>10.135</td><td>14.045</td><td>18.083</td><td>21.045</td><td>24.903</td><td>28.045</td><td>50.085</td></tr>
<tr><th>ECWARC</th><td>1.822</td><td>3.542</td><td>-</td><td>7.022</td><td>10.122</td><td>14.042</td><td>18.082</td><td>21.042</td><td>24.912</td><td>28.022</td><td>-</td></tr>
<tr><th>BUG</th><td>1.813</td><td>3.533</td><td>5.373</td><td>7.033 / 7.123</td><td>10.123</td><td>14.033</td><td>18.083</td><td>21.033</td><td>24.903</td><td>28.033</td><td>50.083</td></tr>
<tr><th>U-QRQ-C</th><td>-</td><td>3.518</td><td>-</td><td>7.018</td><td>-</td><td>14.055</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><th>GPCW</th><td>1.830</td><td>3.528</td><td>-</td><td>7.028</td><td>10.128</td><td>14.028</td><td>18.081</td><td>21.028</td><td>24.898</td><td>28.028</td><td>50.098</td></tr>
<tr><th>Marconi Club</th><td>-</td><td>3.564</td><td>-</td><td>7.029</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><th>HTC</th><td>-</td><td>3.550</td><td>-</td><td>7.033</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
</table>
</td>
<td>
<table id="events" width=450>
<tr><th colspan=2><a id="eventsback" style="text-decoration:none" href="javascript:load_events(--events_page);">&#9664;</a> &nbsp; &nbsp; Upcoming events &nbsp; &nbsp; <a id="eventsforward" style="text-decoration:none;" id="" href="javascript:load_events(++events_page);">&#9654;</a></th></tr>
</table>
</td>
</tr>
</table>
</div>


<br/>
<div id='tab'>Spots should appear here. If they don't, maybe you have Javascript disabled in your browser.</div>
<br/>

<script>
	var spots = {};
	var baseurl = 'bandmap.php'; 
	var contName = new Array("EU", "NA", "AS", "SA", "AF", "OC", "CS");
	var contShow = new Array();
	var bandName = new Array("160", "80", "60", "40", "30", "20", "17", "15", "12", "10", "6");
	var bandShow = new Array();
    var clubName = new Array("<?php echo join('", "', $clubs); ?>");
	var clubShow = new Array();
	var speedName = new Array("<10", "10-14", "15-19", "20-24", "25-29", "30-34", "35-39", ">39");
	var speedShow = new Array();
	var maxAge;
	var refresh;
	var sort;
	var seqNr = seqNr || 1;
	var callFilter;
    var ownCall;
    var abbreviate = false;
    var t9 = false;
    var linktarget = 'qrz';

    var awardACA = true;
    var awardCMA = true;
    var awardAUG = true;
    var awardW1 = true;
    var awardW = false;
    var awardABC = false;
    var awardWAFOC = false;
    var awardFilter = false;
    var awardAudio = false;

    var show_all_events = false;

    var events_page = 1;

    var awardinfo = [];

    // Filters for CWops/FOC etc.
    // this may be loaded later from the server if there's information for the
    // user's call.
    var awards = {};

<?php

include("js/bm_alerts.js");
?>


    var linktargets = { "qrz": "https://www.qrz.com/db/", "hamqth": "https://hamqth.com/", "rbn": "https://rbn.telegraphy.de/activity/" };

	load_cookies(); // Load the cookies. This will also fetch the matching spots for this first time

	var refresh_interval = window.setInterval('fetch_spots()', refresh*1000);
	window.setInterval('load_events()', 600000);

    function load_cookies () {
        console.log("load_cookies");
		var i;
		for (i = 0; i < contName.length; i++) {
			document.getElementById('cb' + contName[i]).checked = (getCookie(contName[i])==null || getCookie(contName[i])=='true');
			   // Retrieve cookie values. If non-existant (first time), default to on
		}
		for (i = 0; i < bandName.length; i++) {
			document.getElementById('cb' + bandName[i]).checked = (getCookie(bandName[i])==null || getCookie(bandName[i])=='true');
		}
		for (i = 0; i < clubName.length; i++) {
			document.getElementById('cb' + clubName[i]).checked = getCookie(clubName[i])=='true';
		}
		for (i = 0; i < speedName.length; i++) {
			document.getElementById('cb' + speedName[i]).checked = (getCookie(speedName[i])==null || getCookie(speedName[i])=='true');
		}

		var j=getCookie('maxAge') || 20;
		document.getElementById('maxAge'+j).checked = true;

		var j=getCookie('refresh') || 30;
		document.getElementById('refresh'+j).checked = true;

		var j=getCookie('sort') || 1;
		document.getElementById('sort'+j).checked = true;

		var k=getCookie('callFilter') || "*";
		document.getElementById('callFilter').value=k;

		var k=getCookie('ownCall') || "";
		document.getElementById('ownCall').value=k;

		document.getElementById('selfSpots').checked = getCookie('selfSpots')=='true';
        
        document.getElementById('cbAC').checked = getCookie('abbreviate')=='true';
        document.getElementById('cbT9').checked = (getCookie('top9') == null) ? false : (getCookie('top9')=='true'); 
        
        document.getElementById('linktarget').value = (getCookie('linktarget') == null) ? 'qrz' : getCookie('linktarget');

        document.getElementById('cbAlertVisual').checked = getCookie('alertVisual')=='true';
        document.getElementById('cbAlertAudio').checked = getCookie('alertAudio')=='true';
        
        document.getElementById('cbAwardCMA').checked = getCookie('awardCMA')=='true';
        document.getElementById('cbAwardACA').checked = getCookie('awardACA')=='true';
        document.getElementById('cbAwardAUG').checked = getCookie('awardAUG')=='true';
        document.getElementById('cbAwardW1').checked = getCookie('awardW1')=='true';
        document.getElementById('cbAwardW').checked = getCookie('awardW')=='true';
        document.getElementById('cbAwardABC').checked = getCookie('awardABC')=='true';
        document.getElementById('cbAwardWAFOC').checked = getCookie('awardWAFOC')=='true';
        document.getElementById('cbAwardFilter').checked = getCookie('awardFilter')=='true';
        document.getElementById('cbAwardAudio').checked = getCookie('awardAudio')=='true';

		var al = getCookie('alerts') || "";
		document.getElementById('alerts').value=al;
		filter_change(); // Update the internal arrays to represent the actual values of the checkboxes

		var l = (getCookie('showFilter')==null || getCookie('showFilter')=='true');
        showFilter(l); // Show or hide the filter section, depending on the cookie value
        
        var l = (getCookie('showFreq')==null || getCookie('showFreq')=='true');
        showFreq(l); // Show or hide the club frequencies
	}

	function set_all (what, mode) {
		var i;
		var arr;

		if (what==='club')
			arr=clubName;
		else if (what==='band')
			arr=bandName;
		else if (what==='speed')
			arr=speedName;
		else // what===cont
			arr=contName;

		for (i = 0; i < arr.length; i++) {
			document.getElementById('cb' + arr[i]).checked = mode;
		}

		filter_change();
			
	}

	function filter_change () {
		var i;
		for (i = 0; i < contName.length; i++) {
			contShow[i] = document.getElementById('cb' + contName[i]).checked;
			setCookie(contName[i], contShow[i]);
		}
		for (i = 0; i < bandName.length; i++) {
			bandShow[i] = document.getElementById('cb' + bandName[i]).checked;
			setCookie(bandName[i], bandShow[i]);
		}
		for (i = 0; i < clubName.length; i++) {
			clubShow[i] = document.getElementById('cb' + clubName[i]).checked;
			setCookie(clubName[i], clubShow[i]);
		}
		for (i = 0; i < speedName.length; i++) {
			speedShow[i] = document.getElementById('cb' + speedName[i]).checked;
			setCookie(speedName[i], speedShow[i]);
		}

		var ages = document.getElementsByName('maxAge');
		for(i = 0; i < ages.length; i++) {
   			if (ages[i].checked == true) {
       				maxAge = ages[i].value;
				break;
				}
   		}
		setCookie('maxAge', maxAge);

		var refreshes = document.getElementsByName('refresh');
		for(i = 0; i < refreshes.length; i++) {
   			if (refreshes[i].checked == true) {
       				refresh = refreshes[i].value;
				break;
				}
   		}
		setCookie('refresh', refresh);
        window.clearInterval(refresh_interval);
	    refresh_interval = window.setInterval('fetch_spots()', refresh*1000);

                var sorts = document.getElementsByName('sort');
                for(i = 0; i <sorts.length; i++) {
                        if (sorts[i].checked == true) {
                                sort = sorts[i].value;
                                break;
                                }
                }
                setCookie('sort', sort);

		callFilter = document.getElementById('callFilter').value.toUpperCase() || "*";
                setCookie('callFilter', callFilter);
		document.getElementById('callFilter').value=callFilter; // Displayed uppercase too

		ownCall = document.getElementById('ownCall').value.toUpperCase();
		if (ownCall=="") {
		   ownCall = "GUEST" + Math.floor(Math.random() *10000);
		   document.getElementById('ownCall').value = "N0CALL";
		}

		setCookie('ownCall', ownCall);
		document.getElementById('ownCall').value=ownCall; // Displayed uppercase too

		selfSpots = document.getElementById('selfSpots').checked;
        setCookie('selfSpots', selfSpots);

        abbreviate = document.getElementById('cbAC').checked;
        setCookie('abbreviate', abbreviate);

        t9 = document.getElementById('cbT9').checked;
        setCookie('top9', t9);

        linktarget = document.getElementById('linktarget').value;
        setCookie('linktarget', linktarget);

        alertVisual = document.getElementById('cbAlertVisual').checked;
        setCookie('alertVisual', alertVisual);
        
        alertAudio = document.getElementById('cbAlertAudio').checked;
        setCookie('alertAudio', alertAudio);

        alert_text = document.getElementById('alerts').value.toUpperCase();
        document.getElementById('alerts').value = alert_text;
        setCookie('alerts', alert_text);

        awardFilter = document.getElementById('cbAwardFilter').checked;
        setCookie('awardFilter', awardFilter);

        awardAudio = document.getElementById('cbAwardAudio').checked;
        setCookie('awardAudio', awardAudio);

        awardCMA = document.getElementById('cbAwardCMA').checked;
        setCookie('awardCMA', awardCMA);
        
        awardACA = document.getElementById('cbAwardACA').checked;
        setCookie('awardACA', awardACA);

        awardW1 = document.getElementById('cbAwardW1').checked;
        setCookie('awardW1', awardW1);

        awardW = document.getElementById('cbAwardW').checked;
        setCookie('awardW', awardW);

        awardABC = document.getElementById('cbAwardABC').checked;
        setCookie('awardABC', awardABC);

        awardWAFOC = document.getElementById('cbAwardWAFOC').checked;
        setCookie('awardWAFOC', awardWAFOC);

        awardAUG = document.getElementById('cbAwardAUG').checked;
        setCookie('awardAUG', awardAUG);

		fetch_spots(); // Fetch the spots matching this filter
	}

	function validate_numeric(evt) {
		var theEvent = evt || window.event;
  		var key = theEvent.keyCode || theEvent.which;
  		key = String.fromCharCode( key );
  		var regex = /[0-9]|\./;
  		if(!regex.test(key)) {
    			theEvent.returnValue = false;
    			if(theEvent.preventDefault) theEvent.preventDefault();
  		}
	}

    function fetch_spots () {
            console.log("Fetch spots");

			if (document.hidden && (seqNr++ % 4)) {
				console.log("browser tab hidden. skip fetching 3 of 4 times; nr " + seqNr);
				return;
			}

			document.getElementById('upd').innerHTML = 'Updating...';

			var i;
			var queryurl = baseurl + '?req=' + seqNr;
			for (i = 0; i < contName.length; i++) {
					queryurl += '&' + contName[i] + '=' + contShow[i];
			}
			for (i = 0; i < bandName.length; i++) {
					queryurl += '&' + bandName[i] + '=' + bandShow[i];
			}
			for (i = 0; i < clubName.length; i++) {
					queryurl += '&' + clubName[i] + '=' + clubShow[i];
			}
			for (i = 0; i < speedName.length; i++) {
					queryurl += '&' + speedName[i] + '=' + speedShow[i];
			}

			queryurl += '&' + 'maxAge=' + maxAge;
			queryurl += '&' + 'sort=' + sort;
			queryurl += '&' + 'callFilter=' + callFilter;
			queryurl += '&' + 'ownCall=' + ownCall;
			queryurl += '&' + 'selfSpots=' + selfSpots;
			// console.log(queryurl);

			var request =  new XMLHttpRequest();
			request.open("GET", queryurl, true);
			request.onreadystatechange = function() {
				var done = 4, ok = 200;
				if (request.readyState == done && request.status == ok) {
					spots = JSON.parse(request.responseText);
					update_table();
				}
			}
			request.send(null);
	}

	function update_table () {
			var thisSelf = false;
			var tabclass = "";
			var d = document.getElementById('tab');
            var alert_list = document.getElementById('alerts').value.toUpperCase().split(/[^\~\!A-Z0-9\/()\-\,]+/);
            var alert_calls = [];
            var alert_freqs = {};
            createCookie('alerts', alert_list.join(" "), 365);
            

			var newtable;
			newtable = '<table id="spots">' + '<tr><th>Frequency</th><th>Call</th><th>Age</th><th>Member of</th><th style="width:45px">WPM</th><th>Spotted by (and signal strength)</th></tr>';

			for (var i = 0; i < spots.length; i++) {
				if (stripcall(spots[i].dxcall) == stripcall(ownCall)) { tabclass = 'selfspot'; }
				else if (spots[i].age < 2) { tabclass = 'newspot'; }
				else if (spots[i].age < 10) { tabclass = 'midspot'; }
                else { tabclass = 'oldspot'; }

                if (t9) {
                   if (!(
                        (spots[i].freq >= 3561 &&  spots[i].freq <= 3570) ||
                        (spots[i].freq >= 7031 &&  spots[i].freq <= 7040) ||
                        (spots[i].freq >= 10121 &&  spots[i].freq <= 10130) ||
                        (spots[i].freq >= 14061 &&  spots[i].freq <= 14070) ||
                        (spots[i].freq >= 18086 &&  spots[i].freq <= 18095) ||
                        (spots[i].freq >= 21061 &&  spots[i].freq <= 21070) ||
                        (spots[i].freq >= 24906 &&  spots[i].freq <= 24915) ||
                        (spots[i].freq >= 28061 &&  spots[i].freq <= 28070)
                        ))
                        continue;
                }

                var scall = stripcall(spots[i].dxcall);

                // alert calls prefixed with ~ are removed from the table
                if (alert_list.indexOf("~" + scall) >= 0)  {
                    console.log("skip " + scall +  " " + i);
                    continue;
                }

                // handle "normal" alerts, caused by callsigns entered into the
                // alert window
                var alert_line = false;
                if (match_alert(scall, spots[i].freq, alert_list)) {
                    alert_calls.push(scall);
                    if (alert_freqs[scall] == null) {
                        alert_freqs[scall] = [ spots[i].freq ];
                    }
                    else {
                        alert_freqs[scall].push(spots[i].freq);
                    }
                    if (!awardFilter && tabclass != 'selfspot') {  // if we only show filtered spots, don't colour them
                        tabclass='alert';
                    }
                    alert_line = true;
                }

                // handle alerts generated by needed-lists from CWops/FOC
                try {
                    awardinfo = [];
                    var cl = stripcall(spots[i].dxcall);
                    if (awards[cl][spots[i].band] || awards[cl]['all']) {

                        /* there's an entry for this call on this band/any.
                         * Check if we really want to show it, for which we
                         * need to have checked the check boxe matching the
                         * award in question and we need to make sure this
                         * call is not actively suppressed from alerts by
                         * an entry in the manual alerts list with a ! in
                         * front... */

                        if (alert_list.indexOf("!" + cl) == -1) {
                            // merge the two arrays
                            var a = Array();
                            if (awards[cl][spots[i].band]) {
                                a = awards[cl][spots[i].band];
                            }
                            if (awards[cl]['all']) {
                                a = a.concat(awards[cl]['all']);
                            }

                            if (awardCMA && a.indexOf('CMA') >= 0) {
                                alert_line = true;
                                awardinfo.push("CMA");
                            }
                            if (awardACA && a.indexOf('ACA') >= 0) {
                                alert_line = true;
                                awardinfo.push("ACA");
                            }
                            if (awardAUG && a.indexOf('AUG') >= 0) {
                                alert_line = true;
                                awardinfo.push("Augie");
                            }
                            if (awardW1 && a.indexOf('W1') >= 0) {
                                alert_line = true;
                                awardinfo.push("Windle (all band)");
                            }
                            if (awardW && a.indexOf('W') >= 0) {
                                alert_line = true;
                                awardinfo.push("Windle");
                            }
                            if (awardABC && a.indexOf('A') >= 0) {
                                alert_line = true;
                                awardinfo.push("ABC");
                            }
                            if (awardWAFOC && a.indexOf('O') >= 0) {
                                alert_line = true;
                                awardinfo.push("WAFOC");
                            }

                            if (alert_line) {
                                if (awardAudio) {
                                    alert_calls.push(cl);
                                    if (alert_freqs[cl] == null) {
                                        alert_freqs[cl] = [ spots[i].freq ];
                                    }
                                    else {
                                        alert_freqs[cl].push(spots[i].freq);
                                    }
                                }
                                awardinfo = awardinfo.join(', ', awardinfo);
                                if (!awardFilter && tabclass != 'selfspot') {  // if we only show filtered spots, don't colour them
                                    tabclass='alert';
                                }
                            }

                        }   /* alert of this call not suppressed */
                        else {
                            console.log("Alert for " + cl + " suppressed by user.");
                        }
                    }
                    else {
                        awardinfo = '';
                    }
                }
                catch (e) {
                }

                if (awardFilter == true && !alert_line)
                    continue;

                var name = "";
                try {
                    name = members.hasOwnProperty(scall) ? members[scall] : ""; 
                }
                catch (e) {
                }

				newtable += '<tr title="' + awardinfo + '" class="' + tabclass + '">';
                newtable += '<td class="right"><a target="_blank" href="http://websdr.ewi.utwente.nl:8901/?tune='+spots[i].freq+'cw" rel="nofollow">' + spots[i].freq+ '</a>&nbsp;</td>';
                newtable += '<td title="' + name + '"><a href="' + linktargets[linktarget]  + spots[i].dxcall + '" target="_blank">' + spots[i].dxcall + '</a></td>';
				newtable += '<td class="right">' + spots[i].age+ '</td>';

                var mo = spots[i].memberof;

                if (abbreviate && mo.length > 10) {
                    var moa = mo.split(' ');
                    mo = '';
                    for (var j = 0; j < moa.length - 1; j++) {
                        mo += '<abbr title="' + moa[j] + '">' + moa[j].substr(0,1)  + '</abbr> '
                    }
                }

				newtable += '<td>' + mo + '</td>';
				newtable += '<td class="center">' + spots[i].wpm + '</td>';

                var mobile = false;
                var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth; 
                var limit = mobile ? 6 : Math.floor((width - 600) / 40);
                var moreskimmers = '';
				var high_contrast = true;

				newtable += '<td>';
				for (var j = 0; j < spots[i].snr_spotters.length; j++) {
                    if (j < limit) {
                        newtable += '<span title="' + spots[i].snr_spotters[j].snr +'" class="snr';
                        if (spots[i].snr_spotters[j].snr > 50) {
                                newtable += '50';
                        }
                        else if (spots[i].snr_spotters[j].snr > 40) {
                                newtable += '40';
                        }
                        else if (spots[i].snr_spotters[j].snr > 30) {
                                newtable += '30';
                        }
                        else if (spots[i].snr_spotters[j].snr > 20) {
                                newtable += '20';
                        }
                        else if (spots[i].snr_spotters[j].snr > 10) {
                                newtable += '10';
                        }
                        else {
                                newtable += '00';
                        }
                        newtable += '">';
                        newtable += spots[i].snr_spotters[j].call;
                        newtable += '</span> ';
                    }
                    else {
                        moreskimmers += spots[i].snr_spotters[j].call + ' (' +
                            + spots[i].snr_spotters[j].snr + ' dB) ';
                    }
                }

				if (moreskimmers != '') {
					newtable += ' <span title="' + moreskimmers + '" class="snr00';
					if (high_contrast == 'true' || high_contrast == true) {
						newtable += 'hc';
					}
					newtable += '">(+ ' + (spots[i].snr_spotters.length-limit);
					if (!mobile) {
						newtable += ' more';
					}
					newtable += ')</span>';
				}

				newtable += '</td>';
				newtable += '</tr>';
					
			}
			newtable += '</table>';

            //try {
                check_alert(alert_calls, alert_freqs);
            /* }
            catch (e) {
                console.log("check_alert failed.");
            }
*/

			d.innerHTML = newtable;
			document.getElementById('upd').innerHTML = '';
			
	}

function toggleFilter() {
	var ele = document.getElementById("filter");
	var text = document.getElementById("filterChoice");
	if (ele.style.display == "block") {
  		showFilter(false);
		setCookie('showFilter', false);
		//console.log('Setting showFilter cookie to false');
	} else {
		showFilter(true);
		setCookie('showFilter', true);
		//console.log('Setting showFilter cookie to true');
	}
} 

function showFilter(display) {
	//console.log(display);
	var ele = document.getElementById("filter");
	var text = document.getElementById("filterChoice");
	if (display==true) {
		ele.style.display = "block";
		text.innerHTML = "hide filter";
		//console.log('Showing filter')
	} else {
    		ele.style.display = "none";
		text.innerHTML = "show filter";
		//console.log('Hiding filter')
	}
}


function toggleFreq() {
    var ele = document.getElementById("frequencies");
    var text = document.getElementById("freq");
	if (ele.style.display == "block") {
  		showFreq(false);
		setCookie('showFreq', false);
	} else {
		showFreq(true);
		setCookie('showFreq', true);
	}
}

function showFreq(display) {
	var ele = document.getElementById("frequencies");
	var text = document.getElementById("freqChoice");
	if (display==true) {
		ele.style.display = "block";
		text.innerHTML = "hide frequencies and calendar";
	} else {
    	ele.style.display = "none";
		text.innerHTML = "show frequencies and calendar";
	}
}


function toggle_events () {
    var nr = 0;
	var i = 0;

	while (document.getElementById('event' + (++nr)));

	if (show_all_events == 1) {
		for (i=1; i < nr; i++) {
			document.getElementById('event' + i).style.display = "table-row";
			document.getElementById('toggle_events').innerHTML = "(show less)";
		}
	}
	else {
		for (i=13; i < nr; i++) {
			document.getElementById('event' + i).style.display = "none";
			document.getElementById('toggle_events').innerHTML = "(show all)";
		}
	}
	show_all_events = !show_all_events;
}

function load_events(page = 1) {
    if (page < 1) { page = 1; }
    if (page > 100) { page = 100; }

    var mnt = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    var request =  new XMLHttpRequest();
    request.open("GET", "/api.php?action=view_calendar&page="+page, true);
    request.onreadystatechange = function() {
    	var done = 4, ok = 200;
        if (request.readyState == done && request.status == ok) {
            var cal = JSON.parse(request.responseText);
			var t = document.getElementById("events");
            while (t.rows.length > 1) {
                t.deleteRow(-1);
            }
            for (var i = 0; i < 13; i++) {
                // 2023-12-01 ==> Dec 1
                var tmpday = cal[i]["day"].split("-");
                var newday = mnt[parseInt(tmpday[1])-1] + " " + parseInt(tmpday[2]);
                cal[i]["day"] = newday;
                var row = t.insertRow(-1);
                var day = row.insertCell(0);
                var evt = row.insertCell(1);
                day.innerHTML = cal[i]["day"] + ", " +  cal[i]["hours"];
                evt.innerHTML = "<a href='"+cal[i]["url"]+"'>" + cal[i]["name"] +  "</a>"; 
			}
        }
    }
	request.send();
}


function load_awards() {
    var request =  new XMLHttpRequest();
    request.open("GET", '/filter?c=' + ownCall, true);
    request.onreadystatechange = function() {
        var done = 4, ok = 200;
            if (request.readyState == done && request.status == ok) {
                awards = JSON.parse(request.responseText);
                update_table();
            }
    }
    request.send(null);
}

function init_rbn () {
    load_alerts();
    load_awards();
    load_events();
    fetch_spots();
	toggle_events();
}

</script>


<hr>
<a href="/privacy">Privacy / Datenschutz / Impressum</a>
</body>
</html>
