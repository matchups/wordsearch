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
	$conn = openConnection(false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to save results; code $code");
	}
	echo "
	<form name='save' id='save' method='get' action='dosavequery$type.php' onsubmit='return validateForm()'><BR>\n"; // need to add the other parameters
	echo "<input type=text name=queryname id=queryname required=true /> Query name<BR>";
	$result = SQLQuery($conn, "SELECT name FROM query WHERE owner = $userid");
	$already = '|';
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$already .= $row['name'] . '|';
	}

	foreach ($_GET as $key => $value) {
		echo "<input type=hidden name=$key value='$value'>\n";
	}
	echo "<BR>
	<input type='submit'>
	</form>
	<script>
	</script>
	\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "</BODY>
	<script>
	// Give them a chance to confirm before overwriting a query
	function validateForm() {
		var queryname = document.getElementById('queryname').value;
		if ('$already'.indexOf('|' + queryname + '|') >= 0) {
			return confirm ('Are you sure you want to overwrite the existing ' + queryname + ' query?');
		}
		return true;
	}
	</script>
";
// End of main script
?>
