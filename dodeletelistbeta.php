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
	$listname = $_GET['listname'];
	$username = SQLQuery($connw, "SELECT realname FROM user WHERE id = $userid")->fetch(PDO::FETCH_ASSOC)['realname'];
	deleteAll ($connw, $corpusid);
	$sql = "SELECT id, user_id FROM corpus_share WHERE corpus_id = $corpusid";
	$result = $connw->query($sql);
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$connw->exec ("DELETE FROM corpus_share WHERE id = {$row['id']}");
		mailUser ($row['user_id'], "Alfwords: List deleted", "$username has deleted list $listname, which was previously shared with you.");
	}
	$connw->exec ("DELETE FROM corpus WHERE id = $corpusid");

	unset ($connw);
	Echo "Word list <i>$listname</i> deleted.";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
</BODY>";
// End of main script
?>
