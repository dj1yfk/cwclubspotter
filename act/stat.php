<?
include("stats.php");

$call  = $_GET['call'];
$start = $_GET['start'] + 0;
$days  = $_GET['days'] + 0;

echo print_stats($call, $start, $days);

?>
