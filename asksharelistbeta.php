<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
" . scriptRefs (true, false) . "	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Share Word List
	</TITLE>
</HEAD>
<BODY>
	<H2>Share Word List</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='share' method='get' onsubmit='return validateForm()' action='dosharelist$type.php'><BR>
		Word list to share<BR>
		<select name='list' id=list>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM corpus WHERE owner = $userid ORDER BY name");
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo "<option value={$row['id']}>{$row['name']}</option>\n";
	}
	echo "</select><BR>
		<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
		<input type=hidden name=level value='$level'>
		<input type=hidden name=type value='$type'>
		<input type=hidden name=listname id=listname>
		Share with:<BR>
		<input type=text name=sharewith id=sharewith required=true class=userlook><P>
		<input type=radio name=share id=shareit value=S checked=yes /> Share
		<input type=radio name=share id=unshareit value=U /> Unshare
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
	var ctrl = document.getElementById('list');
	var listname = ctrl.options[ctrl.selectedIndex].text;
	document.getElementById('listname').value = listname;
}

$(document).ready(function() {
	$('input.userlook').typeahead({
		name: 'userlook',
		remote: 'usersuggest{$_GET['type']}.php?query=%QUERY&userid=$userid'
	});
})
</script>
</BODY>";
// End of main script
?>
