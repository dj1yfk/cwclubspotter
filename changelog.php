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
<title>CW Club RBN Spotter changelog</title>
</head>
<h1>CW Club RBN Spotter changelog</h1>

<h2>Last updates of member lists:</h2>
<table>
<tr><th>Club</th><th>Date</th></tr>
<?
$clubs = array("cwops", "ehsc",  "fists",  "foc",  "hsc",  "shsc", "skcc", "vhsc");
foreach ($clubs as $c) {
    echo "<tr><td>".strtoupper($c)."</td><td>".date ("d-F-Y",  filemtime("src/members/".$c."members.txt"))."</td></tr>\n";
}
?>
</table>

<h2>Site changes</h2>

<table>
<tr><th>Date</th><th>Change</th></tr>
<tr><td>29-Oct-2018</td><td>After running the CW Club Spotter for 5 years,
        Frank/PA4N hands over operations to Fabian/DJ1YFK. The new URL is:
        http://rbn.telegraphy.de/</td></tr>
<tr><td>11-Jan-2015</td><td>Added support for 60 meters</td></tr>
<tr><td>11-Sep-2014</td><td>Updated FOC memberships list so it recognizes alternate call signs too</td></tr>
<tr><td>15-Jan-2014</td><td>Remember the 'show/hide filter' setting between sessions</td></tr>
<tr><td>05-Dec-2013</td><td>Added 'self-spots' feature</td></tr>
<tr><td>01-Dec-2013</td><td>Bug fix: when filtering on HSC members, members of VHSC/SHSC/EHSC <i>but not of HSC</i> would be shown </td></tr>
<tr><td>12-Nov-2013</td><td>Added 'hide filter' option to the filter section of the page</td></tr>
<tr><td>17-Aug-2013</td><td>Application is stable; no longer 'experimental'. Moved application from port 1161 to the more common port 80</td></tr>
<tr><td>19-Jun-2013</td><td>Application will now be started after server reboot, so can run unattended</td></tr>
<tr><td>14-Jun-2013</td><td>Removed console.log (doesn't work on IE8/IE9)</td></tr>
<tr><td>12-Jun-2013</td><td>Moved info to separate page; added counter displaying # users on info page</td></tr>
<tr><td>12-Jun-2013</td><td>Added table sort on all columns; added call sign filter</td></tr>
<tr><td>10-Jun-2013</td><td>Filtered out non-CW spots (RTTY, PSK...)</td></tr>
<tr><td>03-Jun-2013</td><td>Fixes for IE8/IE9 (X-UA-Compatible and onchange-&gt;onclick)</td></tr>
<tr><td>14-May-2013</td><td>Changed bandmap-view.php to bandmap.html etc, added sequence number in call to PHP program</td></tr>
<tr><td>13-May-2013</td><td>Added Google Analytics</td></tr>
<tr><td>13-May-2013</td><td>Added configurable max spot age</td></tr>
<tr><td>07-May-2013</td><td>Added Set all / clear all buttons</td></tr>
<tr><td>07-May-2013</td><td>Added lowest-highest WPM listing</td></tr>
<tr><td>05-May-2013</td><td>Added VHSC, SHSC, EHSC</td></tr>
<tr><td>05-May-2013</td><td>Added band filter</td></tr>
<tr><td>04-May-2013</td><td>Fixed setting of cookies, fixed race condition regarding 'onload'</td></tr>
<tr><td>04-May-2013</td><td>Added cookies, SKCC, updated layout, changelog. More speed options</td></tr>
<tr><td>02-May-2013</td><td>Split source code into view and model</td></tr>
<tr><td>01-May-2013</td><td>Added speed filtering</td></tr>
<tr><td>30-Apr-2013</td><td>Added FISTS</td></tr>
<tr><td>26-Apr-2013</td><td>First version by Frank, PA4N. Support for CWops and HSC added</td></tr>
<tr><td>12-Jan-2013</td><td>Original version for FOC, by Fabian, DJ1YFK</td></tr>
</table>
<br/>
<br/>
<form><input type="button" value="Back" onClick="history.go(-1);return true;"> to info page</form>

</body>
</html>
