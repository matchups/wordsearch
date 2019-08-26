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
		$sql = "SELECT ip_address FROM session WHERE session_key = '$session' AND status = 'A'";
		$result = $conn->query($sql);
		if ($result->rowCount() > 0) { // make sure it is an active session
			$row = $result->fetch(PDO::FETCH_ASSOC);
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
	<script src='//code.jquery.com/jquery-2.1.4.min.js'></script>
		<script src='//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
		<script src='//netsh.pp.ua/upwork-demo/1/js/typeahead.js'></script>
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
	echo "
	<form name='save' id='save' method='get' action='dosaveresults$type.php'>
	<input type=text name=listname required=true /> Word list name<BR>
	<input type=radio name=savetype id=typenew value=new checked=true />
	New word list
	<input type=radio name=savetype id=typeover value=over />
	Overwrite existing list
	<input type=radio name=savetype id=typeadd value=add />
	Add to existing list
	<input type=hidden name=sessionkey value='$session'>
	<BR>
	<input type='submit'>
	</form>\n";

  $conn = openConnection (true);
	$conn->exec ("UPDATE session SET last_active = UTC_TIMESTAMP() WHERE session_key = '$session'");
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo '</BODY>';
// End of main script
?>
