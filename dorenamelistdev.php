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
	$newname = $_GET['newname'];
	$connw->exec ("UPDATE corpus SET name = '$newname' WHERE id = $corpusid");
	unset ($connw);
	Echo "Word list <i>{$_GET['listname']}</i> renamed to <i>$newname</i>.";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
</BODY>";
// End of main script
?>
