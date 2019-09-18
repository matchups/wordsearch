<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Share Word List
	</TITLE>
</HEAD>
<BODY>
	<H2>Share Word List</H2>\n";

try {
	$conn = openConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	$shareid = $_GET['share'];
	$sharename = $_GET['sharename'];
	$action= $_GET['action'];

	$stmt = $conn->prepare ("SELECT display FROM corpus_share WHERE id = ? AND user_id = ?");
	$stmt->execute (array ($shareid, $userid));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$display = $row ['display'];
		$actionname = $action == 'H' ? 'hidden' : 'show';
	} else {
		throw new Exception ("Not able to find a record of $sharename being shared with you.");
	}
	if ($action == 'U') {
		$sql = "DELETE FROM corpus_share";
		$msg = "You will no longer have access to $sharename.";
	} else if ($display == $action) {
		throw new Exception ("Display mode for $sharename already set to $actionname.");
	} else {
		$sql = "UPDATE corpus_share SET display = '$action'";
		$msg = "Status of $sharename updated to $actionname.";
	}
	openConnection (true)->query ($sql . " WHERE id=$shareid");
	echo $msg;
}
catch(Exception $e) {
	echo "<font color=red>Unable to perform requested share: " . $e->getMessage() . "</font>";
} // end main code block

echo "</BODY>";
// End of main script
?>
