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
	$corpusid = $_GET['list'];
	$listname = $_GET['listname'];
	$sharewith= $_GET['sharewith'];
	$share = $_GET['share'];

	$stmt = $conn->prepare ("SELECT id FROM user WHERE realname = ?");
	$stmt->execute (array ($sharewith));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$touserid = $row ['id'];
	} else {
		throw new Exception ("No user named $sharewith.");
	}

	if (preg_match ('/[^0-9]/', $corpusid . $touserid)) {
		throw new Exception ("Problem with input arguments.");
	}

	$username = SQLQuery($conn, "SELECT realname FROM user WHERE id = $userid")->fetch(PDO::FETCH_ASSOC)['realname'];

	$stmt = $conn->prepare ("SELECT id FROM corpus_share WHERE corpus_id = ? AND user_id = ?");
	$stmt->execute (array ($corpusid, $touserid));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($share == 'S') {
			throw new Exception ("Word list $listname is already shared with $sharewith");
			openConnection (true)->prepare ("DELETE FROM corpus_share WHERE id = ?")->execute (array ($row['id']));
		} else {
			Echo "Word list <i>$listname</i> share with <i>$sharewith</i> has been removed.";
			mailUser ($touserid, "Alfwords: List unshared", "$username has removed the share on list $listname with you.");
		}
	} else {
		if ($share == 'U') {
			throw new Exception ("Word list $listname is not shared with $sharewith");
		} else {
			openConnection (true)->exec ("INSERT corpus_share (corpus_id, user_id) VALUES ($corpusid, $touserid)");
			Echo "Word list <i>$listname</i> shared with <i>$sharewith</i>.";
			mailUser ($touserid, "Alfwords: List shared", "$username has shared list $listname with you.");
		}
	}

}
catch(Exception $e) {
	echo "<font color=red>Unable to perform requested share: " . $e->getMessage() . "</font>";
} // end main code block

echo "
</BODY>";
// End of main script
?>
