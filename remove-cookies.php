<?php

include("clubs.php");

$expiry=time()-3600; 

$allconts = array('EU', 'NA', 'AS', 'SA', 'AF', 'OC');
foreach ($allconts as $c) {
	setcookie($c, 'bla', $expiry);
}
	
$allconts = array('160', '80', '40', '30', '20', '17', '15', '12', '10', '6');
foreach ($allconts as $c) {
	setcookie($c, 'bla', $expiry);
}

foreach ($clubs as $c) {
	setcookie($c, 'bla', $expiry);
}

$allspeeds = array('<20', '20-24', '25-29', '30-34', '35-39', '>39');
foreach ($allspeeds as $c) {
	setcookie($c, 'bla', $expiry);
}

setcookie('maxAge', 'bla', $expiry);
setcookie('sort', 'bla', $expiry);
setcookie('callFilter', 'bla', $expiry);
setcookie('ownCall', 'bla', $expiry);
setcookie('selfSpots', 'bla', $expiry);
setcookie('showFilter', 'bla', $expiry);
?>
