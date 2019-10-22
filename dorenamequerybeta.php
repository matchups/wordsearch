<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Rename Query
	</TITLE>
</HEAD>
<BODY>
	<H2>Rename Query</H2>\n";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	// Rename entries
	$corpusid = $_GET['query'];
	$newname = $_GET['newname'];
	openConnection (true)->prepare ("UPDATE query SET name = ? WHERE id = ?")->execute(array ($newname, $corpusid));
	Echo "query <i>{$_GET['queryname']}</i> renamed to <i>$newname</i>.";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
</BODY>";
// End of main script
?>
