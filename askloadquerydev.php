<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

echo "<HTML>
<HEAD>
" . scriptStyleRefs (false, false, false) . "	<TITLE>
	Load Saved Query
	</TITLE>
</HEAD>
<BODY>
	<H2>Load Saved Query</H2>\n";

try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='load' method='get' action='doloadquery$type.php'><BR>
		Query to load<BR>
		<select name='query' id=query>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM query WHERE owner = $userid ORDER BY name");
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo "<option value={$row['id']}>{$row['name']}</option>\n";
	}
	$result = SQLQuery($conn, "SELECT query.id AS query_id, user.realname, query.name from query_share
		inner join query on query.id = query_share.query_id
		inner join user on user.id = query.owner
		where user_id = $userid");
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo "<option value={$row['query_id']}>{$row['name']} ({$row['realname']})</option>\n";
	}
	echo "</select><BR>
		<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
		<input type=hidden name=level value='$level'>
		<input type=hidden name=type value='$type'>
		<BR>
		<input type='submit' value='Submit' id='xxx'/>
		</form>\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "</BODY>";
// End of main script
?>
