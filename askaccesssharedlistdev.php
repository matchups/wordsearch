<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
	<script src='//code.jquery.com/jquery-2.1.4.min.js'></script>
	<script src='//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
	<script src='//xnetsh.pp.ua/upwork-demo/1/js/typeahead.js'></script>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	Access Shared Word List
	</TITLE>
</HEAD>
<BODY>
	<H2>Access Shared Word List</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='share' method='get' onsubmit='return validateForm()' action='doaccesssharedlist$type.php'><BR>
		Word list to access:<BR>
		<select name='share' id=share>\n";
	$result = SQLQuery($conn, "SELECT corpus_share.id, corpus.name AS cname, user.realname as uname FROM corpus_share
			INNER JOIN corpus on corpus.id = corpus_share.corpus_id
			INNER JOIN user ON user.id = corpus.owner
			WHERE corpus_share.user_id = $userid ORDER BY corpus.name");
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo "<option value={$row['id']}>{$row['cname']} ({$row['uname']})</option>\n";
	}
	echo "</select><BR>
		<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
		<input type=hidden name=level value='$level'>
		<input type=hidden name=type value='$type'>
		<input type=hidden name=sharename id=sharename>
		Action:<BR>
		<input type=radio name=action id=show value=S checked=yes /> Show
		<input type=radio name=action id=hide value=H /> Hide
		<input type=radio name=action id=unshare value=U /> Unshare (this action cannot be undone)
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
	var ctrl = document.getElementById('share');
	var sharename = ctrl.options[ctrl.selectedIndex].text;
	if (document.getElementById('unshare').checked) {
		return confirm ('Are you sure you want to give up access to ' + sharename + '?');
	}
	document.getElementById('sharename').value = sharename;
}
</script>
</BODY>";
// End of main script
?>
