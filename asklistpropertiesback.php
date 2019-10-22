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
<BODY onload='onLoad()'>
	<H2>Word List Properties</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	$more = '';
	echo "<form name='lookup' method='get' onsubmit='return validateForm()' action='dolistproperties$type.php'><BR>
		Select word list<BR>
		<select name='list' id=list onchange='selectChange()'>\n";
	$result = SQLQuery($conn, "SELECT id, name, url, like_id FROM corpus WHERE owner = $userid ORDER BY name");
	$first = true;
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$id = $row['id'];
		$url = $row['url'];
		$like = $row['like_id'];
		if ($first) {
			$urlDefault = $url;
			$likeDefault = $like;
			$first = false;
		}
		echo "<option value=$id>{$row['name']}</option>\n";
		$more = $more . "<input type=hidden id={$id}_url value='$url'>
		<input type=hidden id={$id}_like value='$like'>\n";
	}
	echo "</select>
		<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
		<input type=hidden name=level value='$level'>
		<input type=hidden name=type value='$type'>
		<input type=hidden name=listname id=listname>
		<BR><BR>
		<input type=text name=url id=url value='$urlDefault' size=60 pattern='[-a-zA-Z0-9._+~/:@]*'> URL
		<div class=hint>Specify the URL for a sample web page corresponding to an entry in this word list.  Use an
		at sign (@) for the place where the entry name should be substituted.</div>
		<select name='like' id=like>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM corpus WHERE like_id = -1 ORDER BY name");
	echo "<option value=>none</option>\n";
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$id = $row['id'];
		$name = $row['name'];
		echo "<option value=$id>$name</option>\n";
		$more = $more . "<input type=hidden id={$id}_parent value='$name'>\n";
	}
	echo "</select>  Works like standard word list
		$more<P>
		<BR>
		<input type=hidden name=parentname id=parentname>
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
	var ctrl = document.getElementById('like');
	document.getElementById('parentname').value = ctrl.options[ctrl.selectedIndex].text;
	return true;
}

function selectChange () {
	var newValue = document.getElementById('list').value;
	document.getElementById('url').value = document.getElementById(newValue + '_url').value;
	document.getElementById('like').value = document.getElementById(newValue + '_like').value;
}

function onLoad () {
	selectChange ();
}
</script>
</BODY>";
// End of main script
?>
