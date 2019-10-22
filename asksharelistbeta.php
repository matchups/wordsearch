<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
" . scriptStyleRefs (true, false, false) . "	<TITLE>
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
		<div class='typeahead__container'>
			<div class='typeahead__field'>
				<div class='typeahead__query'>
					<input type=text name=sharewith[query] id=sharewith required=true class=sharewith><P>
				</div>
			</div>
		</div>
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

$.typeahead({
		dynamic: true,
		input: '.sharewith',
		delay: 500,
		source: {
			ajax: {
				url: 'usersuggest{$_GET['type']}.php',
				data: {
					 query: '{{query}}',
					 userid: '$userid'
			 },
			 path: 'data'
			}
		}
});
</script>
</BODY>";
// End of main script
?>
