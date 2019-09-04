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
	<H2>Word List Properties</H2>\n";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	// Set properties
	$url = $_GET['url'];
	$stmt = OpenConnection (true)->prepare("UPDATE corpus SET url = ?, like_id = ? WHERE id = ?");
	$stmt->execute(array ($url, $_GET['like'] ?? 'NULL', $_GET['list']));

  if ($url == '') {
		$url = 'none';
	}
	Echo "Updating word list <i>{$_GET['listname']}</i>.<BR>
	 URL set to <i>$url</i>.<BR>
	 Set to be similar to <i>{$_GET['parentname']}</i>.";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
</BODY>";
// End of main script
?>
