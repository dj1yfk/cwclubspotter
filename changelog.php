<h2>Last updates of member lists:</h2>
<table>
<tr><th>Club</th><th>Website</th><th>Date</th><th>Calls (approx.)</th></tr>
<?
include_once("clubs.php");
foreach ($clubs as $c) {
    echo "<tr><td>".strtoupper($c)."</td><td><a href='".$clubweb[$c]."'>".$clubname[$c]."</a></td><td>".date ("d-F-Y",  filemtime("src/members/".strtolower($c)."members.txt"))."</td><td>".count(file("src/members/".strtolower($c)."members.txt"))."</td></tr>\n";
}
?>
</table>

<p>Missing your favorite club? Get in touch!</p>

<h2>Site changes</h2>

<table>
<tr><th>Date</th><th>Change</th></tr>
<tr><td>21-Oct-2020</td><td>Add new club: UFT (Union Française des Télégraphistes) - tnx F5IYJ</td></tr>
<tr><td>21-Aug-2020</td><td>Add a calendar of CW operating events - tnx SQ9S</td></tr>
<tr><td>27-Jul-2020</td><td>Move RBN activity stats to rbn.telegraphy.de. Add support for 6m, 4m, 2m.</td></tr>
<tr><td>16-Jul-2020</td><td>Show a table of CW club frequencies (optional) - tnx SQ9S</td></tr>
<tr><td>14-Jul-2020</td><td>Deselecting all clubs now shows all unfiltered spots.</td></tr>
<tr><td>14-Jun-2020</td><td>Added SOC</td></tr>
<tr><td>11-May-2020</td><td>Added TORCW (Tortugas CW Club)</td></tr>
<tr><td>21-Apr-2020</td><td>Alerts now shown all bands (not just first). Visual alert includes frequencies.</td></tr>
<tr><td>17-Apr-2020</td><td>Added CWJF</td></tr>
<tr><td>09-Apr-2020</td><td>Added QRP ARCI</td></tr>
<tr><td>29-Mar-2020</td><td>Added NRR (Novice Rig Round-Up).</td></tr>
<tr><td>29-Mar-2020</td><td>Show alerted rows in yellow to make them stand out better.</td></tr>
<tr><td>10-Mar-2020</td><td>Added "Alerts". Enter calls you like to be alerted about, visually or with a Morse alert!</td></tr>
<tr><td>07-Mar-2020</td><td>Added a new club: LIDS</td></tr>
<tr><td>25-Feb-2020</td><td>Callsigns now either link to QRZ.com, HamQTH.com or RBN Activity Reports.</td></tr>
<tr><td>25-Feb-2020</td><td>New club: RCWC added.</td></tr>
<tr><td>25-Feb-2020</td><td>Enable telnet port (<code>rbn.telegraphy.de:7000</code>)</td></tr>
<tr><td>17-Feb-2020</td><td>Make hiding/abbreviating club names optional.</td></tr>
<tr><td>12-Feb-2020</td><td>Added new clubs: AGCW, NAQCC, BUG - new clubs can now easily be added!</td></tr>
<tr><td>01-Nov-2018</td><td>Recognize calls with portable indicators (e.g. SP/DJ1YFK); avoid multiple spots per band for one callsign.</td></tr>
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
<a href="/">Back to the CW Clubs RBN</a>

