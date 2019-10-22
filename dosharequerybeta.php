<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Share Query
	</TITLE>
</HEAD>
<BODY>
	<H2>Share Query</H2>\n";

try {
	$conn = openConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	$queryid = $_GET['query'];
	$queryname = $_GET['queryname'];
	$sharewith= $_GET['sharewith'];
	$share = $_GET['share'];

	$stmt = $conn->prepare ("SELECT id FROM user WHERE realname = ?");
	$stmt->execute (array ($sharewith));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$touserid = $row ['id'];
	} else {
		throw new Exception ("No user named $sharewith.");
	}

	if (preg_match ('/[^0-9]/', $queryid . $touserid)) {
		throw new Exception ("Problem with input arguments.");
	}

	$username = SQLQuery($conn, "SELECT realname FROM user WHERE id = $userid")->fetch(PDO::FETCH_ASSOC)['realname'];

	$stmt = $conn->prepare ("SELECT id FROM query_share WHERE query_id = ? AND user_id = ?");
	$stmt->execute (array ($queryid, $touserid));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($share == 'S') {
			throw new Exception ("Query $queryname is already shared with $sharewith");
		} else {
			openConnection (true)->prepare ("DELETE FROM query_share WHERE id = ?")->execute (array ($row['id']));
			Echo "Query <i>$queryname</i> share with <i>$sharewith</i> has been removed.";
			mailUser ($touserid, "Alfwords: Query unshared", "$username has removed the share on query $queryname with you.");
		}
	} else {
		if ($share == 'U') {
			throw new Exception ("Word list $queryname is not shared with $sharewith");
		} else {
			openConnection (true)->exec ("INSERT query_share (query_id, user_id) VALUES ($queryid, $touserid)");
			Echo "Word list <i>$queryname</i> shared with <i>$sharewith</i>.";
			mailUser ($touserid, "Alfwords: List shared", "$username has shared list $queryname with you.");
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
