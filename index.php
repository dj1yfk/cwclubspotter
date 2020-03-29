<!DOCTYPE html>

<!-- RBN / DX Cluster Spotter view                               	-->
<!--									-->
<!-- Fabian Kurz, DJ1YFK <fabian@fkurz.net>				-->
<!-- 2012-12-22								-->
<!--									-->
<!-- Frank R. Oppedijk, PA4N <pa4n@xs4all.nl>				-->
<!-- 2013-04-26								-->
<!--									-->
<!-- Original sources from: http://fkurz.net/ham/stuff.html?rbnbandmap	-->
<!--									-->
<!-- This code is in the public domain.					-->

<?php
include_once("clubs.php");
?>


<html>
<head>
<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=iso-8859-1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="shortcut icon" type="image/x-icon" href="/pa4n.ico">
<link rel="stylesheet" type="text/css" href="/bandmap.css">
<title>CW Club RBN Spotter</title>
<script src="js/cookies.js"></script>

</head>
<body onload="init_rbn();">
<audio id="cwplayer"></audio>
<h1>CW Club RBN Spotter</h1>

<p>The table shows recent RBN spots of CW club members in a dynamically updated
bandmap (also available via telnet).
See <a href="info">here</a> for more info. &nbsp; <span id="upd"></span></p>

<a id="filterChoice" href="javascript:toggleFilter();">hide filter</a>
<div id="filter" style="display: block">
<form onSubmit="filter_change();return false;">
<table>
<tr><th>Members of</th><td> 	
<button type="button" onclick="set_all('club', true)">all</button>
</td><td>
<button type="button" onclick="set_all('club', false)">nil</button>
</td>
<?php
foreach ($clubs as $c) {
    echo "<td><input onclick='filter_change();' id='cb$c' type='checkbox' name='cb$c' value='1' checked><abbr title='".$clubname[$c]."'>".$clubabbr[$c]."</abbr></td>";
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
<input onclick="filter_change()" id="cb<20" type="checkbox" name="cb<20" value="1" checked><20
</td><td>
<input onclick="filter_change()" id="cb20-24" type="checkbox" name="cb20-24" value="1" checked>20-24
</td><td>
<input onclick="filter_change()" id="cb25-29" type="checkbox" name="cb25-29" value="1" checked>25-29
</td><td>
<input onclick="filter_change()" id="cb30-34" type="checkbox" name="cb30-34" value="1" checked>30-34
</td><td>
<input onclick="filter_change()" id="cb35-39" type="checkbox" name="cb35-39" value="1" checked>35-39
</td><td>
<input onclick="filter_change()" id="cb>39" type="checkbox" name="cb>39" value="1" checked>>39
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
<td colspan=12>
<input onclick="filter_change()" id="cbAC" type="checkbox" name="cbAC" value="1">Abbreviate Club Names
&nbsp;
&nbsp;
&nbsp;
&nbsp;
Click on call links to: <select onChange="filter_change();" id="linktarget" size="1">
<option value="qrz">QRZ.com</option>
<option value="rbn">RBN Activity Report</option>
<option value="hamqth">hamqth.com</option>
</select>
</td>
</tr>
<tr>
<th>Alerts</th>
<td colspan=15>
<input onblur="filter_change()" id="alerts" type="text" size="80" name="alerts" value="">
<input onclick="filter_change()" id="cbAlertVisual" type="checkbox" name="cbAlertVisual" value="1" checked> Visual alert &nbsp; &nbsp;
<input onclick="filter_change()" id="cbAlertAudio" type="checkbox" name="cbAlertAudio" value="1" checked> Audio alert (CW) &nbsp; &nbsp; <a href="/info#alerts">Alert help</a>
</td>
</tr>
</table>
</form>
</div>
<br/>
<hr/>
<br/>
<div id='tab'>Spots should appear here. If they don't, maybe you have Javascript disabled in your browser.</div>
<br/>

<script>
	var spots = {};
	var baseurl = 'bandmap.php'; 
	var contName = new Array("EU", "NA", "AS", "SA", "AF", "OC");
	var contShow = new Array();
	var bandName = new Array("160", "80", "60", "40", "30", "20", "17", "15", "12", "10", "6");
	var bandShow = new Array();
    var clubName = new Array("<?php echo join('", "', $clubs); ?>");
	var clubShow = new Array();
	var speedName = new Array("<20", "20-24", "25-29", "30-34", "35-39", ">39");
	var speedShow = new Array();
	var maxAge;
	var sort;
	var seqNr = seqNr || 1;
	var callFilter;
    var ownCall;
    var abbreviate = false;
    var linktarget = 'qrz';

<?php
include("js/bm_alerts.js");
?>


    var linktargets = { "qrz": "https://www.qrz.com/db/", "hamqth": "https://hamqth.com/", "rbn": "https://foc.dj1yfk.de/activity/" };

	load_cookies(); // Load the cookies. This will also fetch the matching spots for this first time

	window.setInterval('fetch_spots()', 30000);

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
			document.getElementById('cb' + clubName[i]).checked = (getCookie(clubName[i])==null || getCookie(clubName[i])=='true');
		}
		for (i = 0; i < speedName.length; i++) {
			document.getElementById('cb' + speedName[i]).checked = (getCookie(speedName[i])==null || getCookie(speedName[i])=='true');
		}

		var j=getCookie('maxAge') || 20;
		document.getElementById('maxAge'+j).checked = true;

		var j=getCookie('sort') || 1;
		document.getElementById('sort'+j).checked = true;

		var k=getCookie('callFilter') || "*";
		document.getElementById('callFilter').value=k;

		var k=getCookie('ownCall') || "";
		document.getElementById('ownCall').value=k;

		document.getElementById('selfSpots').checked = getCookie('selfSpots')=='true';
        
        document.getElementById('cbAC').checked = getCookie('abbreviate')=='true';
        
        document.getElementById('linktarget').value = (getCookie('linktarget') == null) ? 'qrz' : getCookie('linktarget');

        document.getElementById('cbAlertVisual').checked = getCookie('alertVisual')=='true';
        document.getElementById('cbAlertAudio').checked = getCookie('alertAudio')=='true';

		var al = getCookie('alerts') || "";
		document.getElementById('alerts').value=al;
		filter_change(); // Update the internal arrays to represent the actual values of the checkboxes

		var l = (getCookie('showFilter')==null || getCookie('showFilter')=='true');

        showFilter(l); // Show or hide the filter section, depending on the cookie value
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
		//console.log(callFilter);

		ownCall = document.getElementById('ownCall').value.toUpperCase();
		if (ownCall=="") {
		   document.getElementById('callmsg').innerHTML = '<-- Please enter your call sign &nbsp;&nbsp;';
		   document.getElementById('callmsg').style.color="red";
		   document.getElementById('callmsg').style.fontWeight="bold";
		   return;
		} else {
		   document.getElementById('callmsg').innerHTML = '';
		}

        setCookie('ownCall', ownCall);
		document.getElementById('ownCall').value=ownCall; // Displayed uppercase too

		selfSpots = document.getElementById('selfSpots').checked;
        setCookie('selfSpots', selfSpots);

        abbreviate = document.getElementById('cbAC').checked;
        setCookie('abbreviate', abbreviate);

        linktarget = document.getElementById('linktarget').value;
        setCookie('linktarget', linktarget);

        alertVisual = document.getElementById('cbAlertVisual').checked;
        setCookie('alertVisual', alertVisual);
        
        alertAudio = document.getElementById('cbAlertAudio').checked;
        setCookie('alertAudio', alertAudio);

        alert_text = document.getElementById('alerts').value.toUpperCase();
        document.getElementById('alerts').value = alert_text;
        setCookie('alerts', alert_text);

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
			document.getElementById('upd').innerHTML = 'Updating...';

			var i;
			var queryurl = baseurl + '?req=' + seqNr++;
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
			console.log(queryurl);

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
			var d = document.getElementById('tab');
            var alert_list = document.getElementById('alerts').value.toUpperCase().split(/[^A-Z0-9\/()\-\,]+/);
            var alert_calls = [];
            createCookie('alerts', alert_list.join(" "), 365);
            

			var newtable;
			newtable = '<table id="spots">' + '<tr><th>Frequency</th><th>Call</th><th>Age</th><th>Member of</th><th style="width:45px">WPM</th><th>Spotted by (and signal strength)</th></tr>';

			for (var i = 0; i < spots.length; i++) {
				if (selfSpots==true && spots[i].dxcall==ownCall) { tabclass = 'selfspot'; }
				else if (spots[i].age < 2) { tabclass = 'newspot'; }
				else if (spots[i].age < 10) { tabclass = 'midspot'; }
                else { tabclass = 'oldspot'; }

                var scall = stripcall(spots[i].dxcall);
                var alert_this = '';
                if (match_alert(scall, spots[i].freq, alert_list, alert_calls)) {
                    alert_calls.push(scall);
                    tabclass='alert';
                }

				newtable += '<tr class="' + tabclass + '">';
                newtable += '<td class="right">' + spots[i].freq+ '&nbsp;</td>';
                newtable += '<td><a href="' + linktargets[linktarget]  + spots[i].dxcall + '" target="_blank">' + spots[i].dxcall + '</a></td>';
				newtable += '<td class="right">' + spots[i].age+ '</td>';

                var mo = spots[i].memberof;

                if (abbreviate && mo.length > 10) {
                    var moa = mo.split(' ');
                    mo = '';
                    for (var j = 0; j < moa.length - 1; j++) {
                        mo += '<abbr title="' + moa[j] + '">' + moa[j].substr(0,1)  + '</abbr> '
                    }
                }
                else {
                    mo = mo.replace(/ /g, '&nbsp;')
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
                check_alert(alert_calls);
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

function init_rbn () {
    load_alerts();
    fetch_spots();
}

</script>


<hr>
<a href="/privacy">Privacy / Datenschutz / Impressum</a>
<?
        if (!$_SERVER['HTTPS']) { ?> - <a rel="nofollow" href="https://rbn.telegraphy.de/">Switch to https</a> <? }
                    else { ?> - <a rel="nofollow" href="http://rbn.telegraphy.de/">Switch to http</a> <? }
?>

</body>
</html>
