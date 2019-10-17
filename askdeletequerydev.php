<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
" . scriptStyleRefs (false, false, false) . "	<TITLE>
	Delete Query
	</TITLE>
</HEAD>
<BODY>
	<H2>Delete Query</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='load' method='get' action='dodeletequery$type.php' onsubmit='return validateForm()' ><BR>

		Query to delete<BR>
		<select name='query' id=query>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM query WHERE owner = $userid ORDER BY name");
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo "<option value={$row['id']}>{$row['name']}</option>\n";
	}
	echo "</select><BR>
		<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
		<input type=hidden name=level value='$level'>
		<input type=hidden name=type value='$type'>
		<input type=hidden name=queryname id=queryname>
		<BR>
		<input type='submit' value='Submit' id='xxx'/>
		</form>\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
<script>
function validateForm () {
	var ctrl = document.getElementById('query');
	var queryname = ctrl.options[ctrl.selectedIndex].text;
	document.getElementById('queryname').value = queryname;
	return confirm ('Are you sure you want to delete the ' + queryname + ' query?');
}
</script>
</BODY>";
// End of main script
?>
