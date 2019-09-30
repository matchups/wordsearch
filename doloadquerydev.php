<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to load query; code $code");
	}
  if (($queryid = $_GET['query'] ?? '') == '') {
		throw new Exception ("Query is required");
	}
	$conn = openConnection(false);
	$parms = sqlQuery($conn, "SELECT parms FROM query WHERE id = $queryid")->fetch(PDO::FETCH_ASSOC)['parms'];
	if (!$parms) {
		throw new Exception ("Query has no content");
	}
	$sessionkey = sqlQuery($conn, "SELECT session_key FROM session WHERE id = $sessionid")->fetch(PDO::FETCH_ASSOC)['session_key'];
	if (!$sessionkey) {
		throw new Exception ("System integrity violation");
	}

  $url = "http://alfwords.com/search$type.php?$parms&type=$type&sessionkey=$sessionkey&level=$level&norun=on";
	header("Location: $url");
}
catch(Exception $e) {
	echo "<HTML>
	<HEAD>
		<meta name='viewport' content='width=device-width, initial-scale=1'>
		<link rel='stylesheet' href='styles.css'>
		<TITLE>
		Load Query
		</TITLE>
	</HEAD>
	<BODY>
		<H2>Load Query</H2>
	<font color=red>" . $e->getMessage() . "</font></BODY>";
} // end main code block
?>
