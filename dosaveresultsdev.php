<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";
include 'addmain.php';

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Save Results
	</TITLE>
</HEAD>
<BODY>
	<H2>Save Results</H2>\n";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to save results; code $code");
	}

  $conn = openConnection (true);
	if (($listname = $_GET['listname'] ?? '') == '') {
		throw new Exception ("List name is required");
	}
	$stmt = $conn->prepare("SELECT id FROM corpus WHERE name = ? AND owner = ?");
	$stmt->execute (array ($listname, $userid));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$corpusid = $row['id'];
	} else {
		$corpusid = '';
	}

	$connw = openConnection (true);
	switch ($savetype = $_GET['savetype'] ?? 'missing') {
		case 'new':
			if ($corpusid) {
				throw new Exception ("List $listname already exists.");
			}
			$corpusid = sqlInsert ($conn, "INSERT corpus (name, owner) VALUES ('$listname', $userid)");
			break;
		case 'over':
			$sql = "SELECT id FROM entry WHERE corpus_id = $corpusid";
			$result = $conn->query($sql);
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				deleteEntry ($connw, $row['id']);
			}
		case 'add':
			if (!$corpusid) {
				throw new Exception ("Can't find existing list $listname.");
			}
			break;
		default:
			throw New Exception ("Invalid save type: $savetype");
	}

	// slurp words
	$sql = "SELECT entry, corpus_id FROM session_words WHERE session_id = '$sessionid'";
	$result = $conn->query($sql);
	if ($result->rowCount() > 0) { // make sure it is an active session
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$entries [$row['entry']] = '';
		}
	} else {
		throw new Exception ("No saved words for list");
	}

	// add words
	$helper = new loadHelper ();
	$counter = 0;
	foreach ($entries as $entry => $dummy) {
		if (isset (newEntry ($connw, $entry, '', $corpusid, $helper)['id'])) {
			$counter++;
		}
	}
echo "$counter new entries added to list $listname<P>\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block
unset ($connw);

echo '</BODY>';
// End of main script
?>
