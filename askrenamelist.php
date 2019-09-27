<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Rename Word List
	</TITLE>
</HEAD>
<BODY>
	<H2>Rename Word List</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='delete' method='get' onsubmit='return validateForm()' action='dorenamelist$type.php'><BR>
		Word list to rename<BR>
		<select name='list' id=list>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM corpus WHERE owner = $userid ORDER BY name");
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo "<option value={$row['id']}>{$row['name']}</option>\n";
	}
	echo "</select>
		<BR>New name<BR>
		<input type=text name=newname id=newname required=true>
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
	var oldname = ctrl.options[ctrl.selectedIndex].text;
	var newname = document.getElementById('newname').value;
	var here;
	if (oldname == newname) {
		alert ('The new name must be different from the current name.');
		return false;
	}
	for (here = 0; here < ctrl.options.length; here++) { // not clear why forEach didn't work here
  	if (newname == ctrl.options[here].text) {
			alert ('You already have a list with that name.');
			return false;
		}
	};

	document.getElementById('listname').value = oldname;
	return true;
}
</script>
</BODY>";
// End of main script
?>
