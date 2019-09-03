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
	$more = '';
	echo "<form name='lookup' method='get' onsubmit='return validateForm()' action='dolistproperties$type.php'><BR>
		Select Word list<BR>
		<select name='list' id=list onchange='selectChange()'>\n";
	$result = SQLQuery($conn, "SELECT id, name, url FROM corpus WHERE owner = $userid ORDER BY name");
	$first = true;
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$id = $row['id'];
		$url = $row['url'];
		if ($first) {
			$urlDefault = $url;
			$first = false;
		}
		echo "<option value=$id>{$row['name']}</option>\n";
		$more = $more . "<input type=hidden id={$id}_url value='$url'>\n";
	}
	echo "</select>
		$more<P>
		<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
		<input type=hidden name=level value='$level'>
		<input type=hidden name=type value='$type'>
		<input type=hidden name=listname id=listname>
		<BR>
		<input type=text name=url id=url value='$urlDefault' size=60> URL<!-- Needs pattern -->
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

function selectChange () {
	document.getElementById('url').value = document.getElementById(document.getElementById('list').value + '_url').value;
}
</script>
</BODY>";
// End of main script
?>
