<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Delete Word from List
	</TITLE>
</HEAD>
<BODY>
	<H2>Delete Word from List</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}

	$corpusid = $_GET['list'];
	$listname = $_GET['listname'];
	$word = $_GET["word$corpusid"];
	$stmt = $conn->prepare("SELECT id FROM entry WHERE name = ? AND corpus_id = ?");
	$stmt->execute (array ($word, $corpusid));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$id = $row['id'];
	} else {
		throw new Exception ("Unable to find $word in $listname");
	}

	openConnection (true)->exec ("DELETE FROM entry WHERE id = $id");
	Echo "<i>$word</i> has been removed from <i>$listname</i>.";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
</BODY>";
// End of main script
?>
