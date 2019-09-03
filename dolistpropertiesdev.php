<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Rename Word List
	</TITLE>
</HEAD>
<BODY>
	<H2>Rename Word List</H2>\n";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	// Rename entries
	$connw = OpenConnection (true);
	$corpusid = $_GET['list'];
	$url = $_GET['url'];
	$connw->exec ("UPDATE corpus SET url = '$url' WHERE id = $corpusid");
	unset ($connw);
	Echo "URL for word list <i>{$_GET['listname']}</i> set to <i>$url</i>.";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
</BODY>";
// End of main script
?>
