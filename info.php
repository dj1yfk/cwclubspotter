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


<p id="alerts"><b>Alerting</b>
<ul>
<li>You can enter any number of callsigns, separated by spaces, in the <em>Alert</em> field. If one of these calls is spotted, it will be <span class="alert">marked in yellow</span> on the bandmap.</li>
<li>It is possible to limit the alerts by frequency by adding the range in parenthesis behind the call (without a space). Examples:
<ul>
<li><code>DJ1YFK(3500-10150)</code> - will only raise an alert for DJ1YFK on 80m through 30m</li>
<li><code>SO5CW(1810-1840,7000-7040,18068-18168)</code> - will only raise an alert for SO5CW on 160m, 40m and 17m in the specified ranges</li>
</ul>
</li>
<li>By checking the "Visual alert" box, you will receive a browser notification and the title of the site will flash for a few seconds to indicate there's an alert</li>
<li>By checking the "Audio alert" box, you will receive an alert in Morse code, announcing the call(s) that were spotted</li>
</ul>
</p>


<p><b style="color:red">NEW</b> <b>Connect via telnet:</b> You can receive the spots via Telnet by connecting to <code>rbn.telegraphy.de</code> on port <code>7000</code>. Currently filtering by club and continent is implemented for the telnet port. Any changes you make to your filters here on the website automatically applies to the telnet port. If you need multiple sets of filters, simply use SSIDs like DJ1YFK-1, DJ1YFK-2 and log in accordingly. If you think that additional filters could be helpful, let me know!</p>

<p><b>About:</b> This page was created by <a href="http://fkurz.net/">Fabian, DJ1YFK</a> and <a href="http://www.qrz.com/db/pa4n">Frank, PA4N</a>.<br>
As of November 2018, Fabian took over the maintenance, further development and hosting of the CW Club Spotter. You can find the source code here: <a href="https://git.fkurz.net/dj1yfk/cwclubspotter/">https://git.fkurz.net/dj1yfk/cwclubspotter/</a></p>

<p> Current users:
<?php
# DB config
$mysql_host   = "localhost";
$mysql_user   = "spotfilter";
$mysql_pass   = "spotfilter";
$mysql_dbname = "spotfilter";

  $con=mysqli_connect($mysql_host,$mysql_user,$mysql_pass);
  if (!$con)  die("<h1>Sorry: Could not connect to database.</h1>");
  mysqli_select_db($con, $mysql_dbname);
  $q=mysqli_query($con, "delete from users where time < (NOW() - INTERVAL 1 DAY);");
  $q=mysqli_query($con, "select count(distinct(ipaddress)) from users where time > (NOW() - INTERVAL 1 MINUTE);");
  mysqli_data_seek($q, 0);
  $resrow = mysqli_fetch_row($q);
  echo $resrow[0];
?>
    (web), <?php system("netstat -tn |  grep -e ':70[70]0' | grep ESTAB | wc -l"); ?> (telnet)
</p>
<p>
Comments are welcome via email - 73 de Fabian, DJ1YFK &lt;<a href="mailto:fabian@fkurz.net">fabian@fkurz.net</a>&gt;</p>
<br/>
<a href="/">Back to bandmap page</a>

<?
include("changelog.php");
?>



<hr>
<a href="/privacy">Privacy / Datenschutz / Impressum</a>

</body>
</html>
