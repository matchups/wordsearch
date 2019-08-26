<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

// Need to check for a valid session before creating *any* output
$valid = false;
$code = '1';
if (isset ($_GET['sessionkey'])) { // make sure session info is passed to us
	$session = $_GET['sessionkey'];
	try {
		$conn = openConnection (false);
		$sql = "SELECT user_id, ip_address FROM session WHERE session_key = '$session' AND status = 'A'";
		$result = $conn->query($sql);
		if ($result->rowCount() > 0) { // make sure it is an active session
			$row = $result->fetch(PDO::FETCH_ASSOC);
			$userid = $row['user_id'];
			// confirm IP address; copy from search
			$valid = true;
		} else {
			$code = '3';
		}
	}
	catch(PDOException $e) {
		$code = $e->getCode ();
	}
}

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
	if (!$valid) {
		throw new Exception ("Unable to save results; code $code");
	}

  $conn = openConnection (true);
	if ($listname = $_GET['listname'] ?? '') == '') {
		throw new Exception ("List name is required");
	}
	$stmt = $conn->prepare("SELECT id FROM corpus WHERE name = ? AND owner = ?");
	$stmt->execute (array ($listname, $userid));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$listid = $row['id'];
	} else {
		$listid = '';
	}

	switch ($savetype = $_GET['savetype'] ?? 'missing') {
		case 'new':
			if ($listid) {
				throw new Exception ("List $listname already exists.");
			}
			//!! insert list
			$listid = 'dummy';
			break;
		case 'over':
		  // delete existing entries
		case 'add':
		if (!$listid) {
			throw new Exception ("Can't find existing list $listname.");
		}
			break;
		default:
			throw New Exception ("Invalid save type: $savetype");
	}

	// slurp words
	// add words
	// message
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo '</BODY>';
// End of main script
?>
