<!DOCTYPE html>
<html>
<head>
<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=iso-8859-1">
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<link rel="shortcut icon" type="image/x-icon" href="/pa4n.ico">
<link rel="stylesheet" type="text/css" href="/bandmap.css">
<title>CW Club RBN Spotter info</title>
</head>
<h1>CW Club RBN Spotter info</h1>

<p>The table on the bandmap page shows recent <a href="http://www.reversebeacon.net/">RBN</a> spots of CW club members in a dynamically updated bandmap (30 seconds update interval). 
</p>
<p>In the upper part of the page, you can specify the filter options you wish to apply. The lower part of the page shows the bandmap with the matching spots.</p>

<p>Some details:
<ul><li>New spots (less than 2 minutes old) appear with a <span class="newspot">red background</span>, old spots (10 minutes or older) with <span class="oldspot">gray background</span>.</li>
<li>In the call filter, you can use ? and * as wildcard characters. A ? matches exactly one character, a * matches zero or more characters. E.g. VK* will show all matches from Australia. W?FOC will match W1FOC, W2FOC, etc. but not W75FOC.</li>
<li>In case a call is spotted by multiple skimmers, and these skimmers report dissimilar WPM speeds, the lowest and highest reported speed are listed, separated by a hyphen.</li>
<li>The font color of the receiving skimmer's callsign depends on the SNR:
<span class="snr50">SNR &gt; 50 dB</span>,
<span class="snr40">SNR &gt; 40 dB</span>,
<span class="snr30">SNR &gt; 30 dB</span>,
<span class="snr20">SNR &gt; 20 dB</span>,
<span class="snr10">SNR &gt; 10 dB</span>,
<span class="snr00">SNR &gt; 0 dB</span>. Hover your mouse over a spotter call to see the exact value.</li>
<li>If you enter your own call and check the 'Include self-spots' box, your self-spots will be shown along with the other spots, with a <span class="selfspot">green background</span>. In order for this feature to work, you must be a member of at least one of the CW clubs. For your self-spots only, all your filter settings - except 'Bands' and 'Max spot age' - will be ignored. This means you will see your self-spots regardless of which filtering you've selected for clubs, speeds, call filter, and skimmer continents.</li>
<li>Alerts (see below) are shown with a <span class="alert">yellow background</span></li>
</ul></p>

<p>Do <b>spots of your station not appear</b> in this RBN application? 
<ul><li>First, please check at <a href="http://www.reversebeacon.net">www.reversebeacon.net</a> if your signals were spotted at all.</li>
<li>Second, if you've recently joined a club, your membership may not have been included in the membership list of that club. Please check with the club membership secretary.</li>
<li>Please note that I do not maintain any membership lists myself; I only import lists that are maintained by the respective club membership secreataries at regular intervals (see list of updates below).</li>
</ul>
</p>

<p><b>Don't you see any spots at all?</b>
<ul>
<li>First, please look at the filters you have defined. Maybe they are set too narrow?</li>
<li>Did you enter a callsign in the "Own Call" box? If you wish to stay anonymous, just enter <em>anything</em> here.</li>
<li>Also, please check at <a href="http://www.reversebeacon.net">www.reversebeacon.net</a> if the RBN site is up and running at all.</li></ul>
</p>


<h2 id="alerts">Alerting</h2>
<ul>
<li>You can enter any number of callsigns, separated by spaces, in the <em>Alert</em> field. If one of these calls is spotted, it will be <span class="alert">marked in yellow</span> on the bandmap.</li>
<li>Even calls that are not members of the selected clubs (if any) will be shown in the bandmap</li>
<li>It is possible to limit the alerts by frequency by adding the range in parenthesis behind the call (without a space). Examples:
<ul>
<li><code>DJ5CW(3500-10150)</code> - will only raise an alert for DJ5CW on 80m through 30m</li>
<li><code>SO5CW(1810-1840,7000-7040,18068-18168)</code> - will only raise an alert for SO5CW on 160m, 40m and 17m in the specified ranges</li>
</ul>
</li>
<li>By checking the "Visual alert" box, you will receive a browser notification and the title of the site will flash for a few seconds to indicate there's an alert</li>
<li>By checking the "Audio alert" box, you will receive an alert in Morse code, announcing the call(s) that were spotted</li>
</ul>

<h2 id="awards">Award specific alerting</h2>
<p>The CW Club RBN spotter can display alerts for various operating awards/activities of <a href="https://g4foc.org/">FOC</a> and <a href="https://cwops.org/">CWops</a>, by retrieving an user's list of needed callsigns/bands from the respective Award Tools (<a href="https://foc.telegraphy.de/">FOC Award Tools</a>, <a href="https://cwops.telegraphy.de/">CWops Award Tools</a>).</p>
<p>If you're a member of either club and already upload your logs to the award tools, you can immediately be notified of new spots which are valid for the various operating awards by selecting the respetive checkboxes in the filter tab.</p>
<p>You can refer to this <a target="_new" href="rbn_awards.png">screenshot</a>: 1. Enter your own callsign, 2. Select the desired awards for which you want to be alerted, 3. Hit the "Refresh" symbol to load your needed callsign lists from the CWops/FOC Award Tools.</p>
<p>There are two display options: By default, spots that are new points for awards will be shown in yellow on the normal bandmap. You can also remove all other spots if you activate the checkbox "Show only needed stations on bandmap". If you hover your mouse cursor over the spot, a small popup will show for which award the spot counts.</p>
<p>The award functions are currently only available on the web interface. The same function will eventually also be implemented for the telnet interface.</p>
<p><b>Note:</b> If you want to suppress certain alerted calls, you can add them into your alert list with an exclamation mark in front of them, e.g. <code>!DJ5CW</code> will mean no alerts will be generated for DJ5CW. To complete remove a call from the bandmap, prepend a tilde, e.g. <code>~DJ5CW</code>.</p>


<h2 id="telnet">Connect via telnet</h2>
<p>You can receive the spots via telnet by connecting to <code>rbn.telegraphy.de</code> on port <code>7000</code>. Filtering by club, continent (including "custom" skimmer selections), band and speed is implemented for the telnet port, as well as the block list (calls with a ~ in the alert box).
Award filters are not yet available. Any changes you make to your filters here on the website automatically applies to the telnet port. If you need multiple sets of filters, simply use SSIDs like DJ5CW-1, DJ5CW-2 and log in accordingly. If you think that additional filters could be helpful, let me know!</p>
<p>You can switch between the telnet stream filtered by club members and a raw stream (all spots) by entering <code>set/clubs</code> and <code>set/raw</code> respectively.</p>
<p>If you want to reduce the spot load, issue the command <code>set/nodupes</code>. If enabled, only one spot per callsign on the same frequency will be posted in a 5 minute window. To switch back to all spots, enter <code>set/dupes</code>.</p>
<p>Ham Radio Deluxe users: If you don't see any spots, please try switching to the VE7CC output format (CC11) by entering <code>set/ve7cc</code>. You can switch back with <code>set/normal</code></p> 

<h2>Embedding into your own website</h2>
<p>It is possible to include a bandmap (possibly adjusted to your own needs) on your own website, e.g. a club website (example: <a href="https://www.uft.net/les-stations-uft-sur-lair/">UFT</a>). If you need this, feel free to contact Fabian, DJ5CW and discuss the technical details.</p>

<h2>About</h2><p>This page was created by <a href="http://fkurz.net/">Fabian, DJ5CW</a> and <a href="http://www.qrz.com/db/pa4n">Frank, PA4N</a>.<br>
As of November 2018, Fabian took over the maintenance, further development and hosting of the CW Club Spotter. Adam, SQ9S takes care of the activity calendar. You can find the source code here: <a href="https://git.fkurz.net/dj1yfk/cwclubspotter/">https://git.fkurz.net/dj1yfk/cwclubspotter/</a></p>

<p> Current users:
<?php

  include_once("db.php");

  $q=mysqli_query($con, "delete from users where time < (NOW() - INTERVAL 1 DAY);");
  $q=mysqli_query($con, "select count(distinct(ipaddress)) from users where time > (NOW() - INTERVAL 10 MINUTE);");
  mysqli_data_seek($q, 0);
  $resrow = mysqli_fetch_row($q);
  echo $resrow[0];
?>
    (web), <?php system("netstat -tn |  grep -e ':70[70]0' | grep ESTAB | wc -l"); ?> (telnet)
</p>
<p>
Comments are welcome via email - 73 de Fabian, DJ5CW (ex DJ1YFK) &lt;<a href="mailto:fabian@fkurz.net">fabian@fkurz.net</a>&gt;</p>
<br/>
<a href="/">Back to bandmap page</a>

<?
include("changelog.php");
?>



<hr>
<a href="/privacy">Privacy / Datenschutz / Impressum</a>

</body>
</html>
