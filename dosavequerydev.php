<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Save Query
	</TITLE>
</HEAD>
<BODY>
	<H2>Save Query</H2>\n";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to save query; code $code");
	}

  $conn = openConnection (false);
	if (($queryname = $_GET['queryname'] ?? '') == '') {
		throw new Exception ("Query name is required");
	}

	$stmt = $conn->prepare("SELECT id FROM query WHERE name = ? AND owner = ?");
	$stmt->execute (array ($queryname, $userid));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$queryid = $row['id'];
		$verb = 'updated';
	} else {
		$queryid = '';
		$verb = 'created';
	}

  $parms = '';
	foreach ($_GET as $key => $value) {
		switch ($key) {
			case 'sessionkey':
			case 'type':
			case 'level':
			case 'queryname':
			case 'already':
			case 'simple':
			// not part of query; trader_cdltasukigap
			break;

			default:
			$parms .= "$key=" . urlencode ($value) . '&';
		}
	}

	$connw = openConnection (true);
	if (!$queryid) {
		$connw -> exec ("INSERT query (name, owner) VALUES ('$queryname', $userid)");
		$queryid = $connw->lastInsertId();
	}
	$stmt = $connw->prepare("UPDATE query SET parms = ? WHERE id = ?");
	$stmt->execute(array ($parms, $queryid));

  echo "Query $queryname $verb<P>\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block
unset ($connw);

echo '</BODY>';
// End of main script
?>
