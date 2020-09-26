<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="/bandmap.css" />
<style>
p {
 max-width: 50em;
 text-align: justify;
}
</style>
<title>RBN activity FAQ</title>
</head>
<body>
<h1>RBN activity FAQ</h1>

<h2 id="embed">How do I embed the RBN statistics on my website/qrz.com/HamQTH.com profile?</h2>
<p>You can embed the profile as an image on any website. A HTML snippet is provided on each statistics page, which looks like this:</p>
<pre style="background-color:#eeeeef">
&lt;a href="https://rbn.telegraphy.de/activity/SO5CW"&gt;&lt;img src="https://rbn.telegraphy.de/activity/image/SO5CW"&gt;&lt;/a&gt;
</pre>
<img src="/act/img/src.png" align="left">
<p>Replace SO5CW with your call. When embedding this on your QRZ.com/HamQTH profile, make sure you switch to the <b>Source</b> view (click on the icon shown on the left) to see the raw HTML code, and then copy&amp;paste the snippet into the source code. Note that the image is only updated once every hour, as opposed to the website which shows data in real time.</p>

<h2>I want to have my data removed!</h2>
<p>According to the <a href="https://en.wikipedia.org/wiki/General_Data_Protection_Regulation">European General Data Protection Regulation</a>, you have the "right to be forgotten" and therefore you can request your data to be removed. In order to make this happen, simply send a mail to  <a href="mailto:fabian@fkurz.net">fabian@fkurz.net</a> in which you request the removal of your data, or write to the address mentioned in the privacy policy.</p>

<h2>Why does my callsign not show up? Which modes are covered?</h2>
<p>Reports are generated for every callsign spotted by the <a href="http://www.reversebeacon.net/">RBN</a>. Currently the RBN receives CQ calls in Morse code, PSK31/PSK63, and RTTY. If you're QRV in these modes, <strong>and call CQ</strong>, it is very likely that your callsign shows up on the RBN.</p>

<h2>The reported hours for my callsign are completely wrong!</h2>
<p>Please note that the report says "hours with at least one RBN spot," which means exactly that: Clock hours (e.g. from minute 00 to 59) in which one spot was received. If you call CQ at 11:59:30 and one Skimmer reports you at 11:59:59 and another at 12:00:01, this means there was RBN activity in two hours, although you only called CQ once.</p>
<p>If you call CQ a lot, the reported hours will be inflated compared to the hours you spend at the radio. If you mainly answer CQ calls, you may not show up at all, regardless of how many hours you spend on the radio.</p>

<h2>I just called CQ, but my report is blank for today</h2>
<p>RBN data is downloaded and processed daily, around 02:00 UTC. Additionally (as of 2019-01-21) live data is also fed into the database, but this is still an experimental feature. Therefore, to be sure that your data is included, wait until 02:00 UTC on the following day.</p>
<p>You can always check your signal in real time on <a href="http://reversebeacon.net/srch.php">reversebeacon.net</a> or <a href="http://skimmer.g7vjr.org/?dx=dj1yfk">skimmer.g7vjr.org</a>.</p>

<h2>I see reported activity for a band that I was not active on!</h2>
<p>Skimmers are not perfect. It often happens that calls are not copied correctly by skimmers and therefore a wrong callsign gets reported. You can find <strong>dozens</strong> of variations of some well known contest calls, with dots missing, etc. For example <a href="/activity/W3LPL">W3LPL</a> frequently gets spotted as <a href="/activity/W3LP">W3LP</a>, <a href="/activity/W3RPL">W3RPL</a>, <a href="/activity/LW3LPL">LW3LPL</a>,...</p>
<p>Another source of error is that multiband skimmers often suffer from receiver overload and nonlinearities, resulting in phantom spots on wrong bands. So if you called CQ on 40m but a Skimmer reported you on 17m, you might want to check your transmitter for spurious emissions to be sure, but it is more likely that a Skimmer was overloaded. This naturally happens most frequently with Skimmers where your signal is very strong on the fundamental frequency.</p>

<h2>My callsign was spotted wrong by the RBN. Will you correct this?</h2>
<p>This site reflects exactly what the RBN spots, and I am reluctant to make any changes to the data I am receiving. Quite often, a callsign gets spotted with a missing last letter or some other miscopied parts, due to QRM, QSB etc. However, for every "busted" spot there are typically dozens of good spots, so they statistically don't matter and the accuracy of the report for your own callsign hardly suffers from this.</p>

<hr>
<a href="/">Back to the CW Clubs RBN Spotter</a> - <a href="/activity">Back to RBN Activity Charts</a>
<hr>
<p>Last modified: <? echo date ("Y-m-d",  filemtime("faq.php")); ?> - <a href="http://fkurz.net/">Fabian Kurz, DJ1YFK</a> <a href="mailto:fabian@fkurz.net">&lt;fabian@fkurz.net&gt;</a>
<?
	if (!$_SERVER['HTTPS']) { ?> - <a rel="nofollow" href="https://rbn.telegraphy.de/activity/faq">Switch to https</a> <? }
	else { ?> - <a rel="nofollow" href="http://rbn.telegraphy.de/activity/faq">Switch to http</a> <? }
?>
<br>
<a href="/privacy">Impressum / Datenschutz / Privacy Policy</a>
</div>
</body>
</html>
