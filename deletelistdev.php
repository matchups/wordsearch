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
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='delete' method='get' onsubmit='return validateForm()' action='dodeletelist$type.php'><BR>
		Word list to delete<BR>
		<select name='list' id=list>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM corpus WHERE owner = $userid");
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
	var listname = ctrl.options[ctrl.selectedIndex].text;
	document.getElementById('listname').value = listname;
	return confirm ('Are you sure you want to delete the ' + listname + ' list?');
}
</script>
</BODY>";
// End of main script
?>
