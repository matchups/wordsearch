<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Delete Word List
	</TITLE>
</HEAD>
<BODY>
	<H2>Delete Word List</H2>\n";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	// Delete entries
	include "addmain.php";
	$connw = OpenConnection (true);
	$corpusid = $_GET['list'];
	deleteAll ($connw, $corpusid);
	$connw->exec ("DELETE FROM corpus WHERE id = $corpusid");
	unset ($connw);
	Echo "Word list <i>{$_GET['listname']}</i> deleted.";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
</BODY>";
// End of main script
?>
