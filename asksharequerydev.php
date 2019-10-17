<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
" . scriptStyleRefs (true, $type, false) . "
	<TITLE>
	Share Query
	</TITLE>
</HEAD>
<BODY>
	<H2>Share Query</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='share' method='get' onsubmit='return validateForm()' action='dosharequery$type.php'><BR>
		Query to share<BR>
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
	var ctrl = document.getElementById('query');
	var queryname = ctrl.options[ctrl.selectedIndex].text;
	document.getElementById('queryname').value = queryname;
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
