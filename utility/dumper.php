<HTML>
<HEAD>
<TITLE>SQL dump</TITLE>
</HEAD>
<BODY>
<?php
include 'sqldump.php';

$mysql = $_GET['sql'];
$quotedsql = "'" . str_replace ("'", "\\'", $mysql) . "'";

echo "<form name='dump' action='dumper.php' id='dump' method='get'>
SQL: <input type=text name=sql value=$quotedsql />
<br>
<input type=hidden name=sessionkey value='{$_GET['sessionkey']}' />
<input type='submit' value='Submit' id='xxx'/>
</form>\n";

echo $mysql;
echo '<BR>';
if (verifySession ()) {
  $start = microtime();
  sqlDump ($mysql);
  $end = microtime();
  echo "<P>Time=" . timeDiff ($start, $end);
}

function timeDiff ($begin, $end) {
	$beginArray = explode (' ', $begin);
	$endArray = explode (' ', $end);
	return intval ((($endArray[1] - $beginArray[1]) + ($endArray[0] - $beginArray[0])) * 1000 + 0.5);
}
?>
</BODY>
