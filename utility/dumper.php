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
  sqlDump ($mysql);
}
?>
</BODY>
