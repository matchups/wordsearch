<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Word List Properties
	</TITLE>
</HEAD>
<BODY>
	<H2>Word List Properties</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='lookup' method='get' onsubmit='return validateForm()' action='asklistproperties$type.php'><BR>
		Select Word list<BR>
		<select name='list' id=list>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM corpus WHERE owner = $userid ORDER BY name");
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo "<option value={$row['id']}>{$row['name']}</option>\n";
	}
	echo "</select>
		<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
		<input type=hidden name=level value='$level'>
		<input type=hidden name=type value='$type'>
		<input type=hidden name=listname id=listname>
		<BR>
		<input type='submit'>
		</form>\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
<script>
function validateForm () {
	var ctrl = document.getElementById('list');
	document.getElementById('listname').value = ctrl.options[ctrl.selectedIndex].text;
	return true;
}
</script>
</BODY>";
// End of main script
?>
