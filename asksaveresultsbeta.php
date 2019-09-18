<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

if ($upload = ($_GET['source'] == 'upload')) {
	$sourcedesc = 'Upload';
	$method = 'post';
	$enctype = 'enctype=multipart/form-data';
} else {
	$sourcedesc = 'Save Results as';
	$method = 'get';
	$enctype = '';
}
echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	$sourcedesc Word List
	</TITLE>
</HEAD>
<BODY>
	<H2>Save Results</H2>\n";

try {
	$conn = openConnection(false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to save results; code $code");
	}
	echo "
	<form name='save' id='save' method='$method' action='dosaveresults$type.php' $enctype><BR>\n";
	if ($upload) {
		echo "Select word list file to upload:
		    <input type='file' name='uploadfile' id='uploadfile'><BR>\n";
	}
	echo "<input type=text name=listname id=listname required=true /> Word list name<BR>
		<BR>Select word list if updating an existing one.<BR>
		<select name='list' id=list onchange='listChange()'>
		<option value=''></option>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM corpus WHERE owner = $userid ORDER BY name");
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo "<option value={$row['id']}>{$row['name']}</option>\n";
	}
	echo "</select><br>\n";

	$current = $conn->query("SELECT count(1) AS current FROM corpus WHERE owner = $userid")->fetch(PDO::FETCH_ASSOC)['current'];
	$limit = ($level == 3) ? 20 : 5;
	if ($current < $limit) {
		echo "<input type=radio name=savetype id=typenew value=new checked=true />
		New word list\n";
		$addcheck = '';
	} else {
		$addcheck = 'checked';
	}
	echo "<input type=radio name=savetype id=typeover value=over />  Overwrite existing list
	<input type=radio name=savetype id=typeadd value=add $addcheck />
	Add to existing list\n";
	if ($current >= $limit) {
		echo "\n<P>Your account is at its maximum ($limit) number of word lists.
		You can add to an existing list, delete a list, or upgrade your account.
		Also, if your list is of general interest, let us know and we will consider adding it to the common library.\n";
	}

	echo "<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
	<input type=hidden name=level value='$level'>
	<input type=hidden name=source value='{$_GET['source']}'>
	<input type=hidden name=type value='$type'>
	<BR>
	<input type='submit'>
	</form>
	<script>
	function listChange() {
		var ctrl = document.getElementById('list');
		var newText = ctrl.options[ctrl.selectedIndex].text;
		if (newText == '') {
		  document.getElementById('typenew').checked = true;
		} else {
			if (document.getElementById('typenew').checked) {
				document.getElementById('typeadd').checked = true;
			}
			document.getElementById('listname').value = newText;
		}
	}
	</script>
	\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo '</BODY>';
// End of main script
?>
