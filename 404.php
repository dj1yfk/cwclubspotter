<?
  // forwards for slashed zeros in calls
  $url = $_SERVER['REQUEST_URI'];
  if (preg_match('/%C3%98/', $url)) {
      $newurl = preg_replace('/%C3%98/', '0', $url);
      header("Location: https://rbn.telegraphy.de$newurl");
      return;
  }
?>

<!DOCTYPE html>
<html>
		<head>
				<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=iso-8859-1">
				<link rel="stylesheet" type="text/css" href="/bandmap.css">
				<title>Error 404</title>
				<link rel="icon" href="/favicon.ico">
				<link rel="shortcut icon" href="/favicon.ico">
		</head>
		<body>
				<h1>Error 404</h1>

<p>The site you are requesting cannot be found.</p>

<p>Here are some options:</p>
<ul>
<li><a href="/">Main site: CW Clubs RBN</a></li>
<li><a href="/activity">RBN Activity Charts</a></li>
<li><a href="/activity/rank">RBN Activity Ranking</a></li>
</ul>
<hr>
<a href="https://fkurz.net/">Fabian Kurz, DJ5CW</a> &lt;<a href="mailto:fabian@fkurz.net">fabian@fkurz.net</a>&gt;
</body>
</html>
