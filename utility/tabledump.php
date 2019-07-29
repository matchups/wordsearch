<HTML>
<HEAD>
<?php
$table = $_GET['table'];
echo "<TITLE>$table</TITLE>";
?>
</HEAD>
<BODY>
<?php
echo "<H2>Dump of $table</H2>";
include 'sqldump.php';

$mysql = "SELECT * FROM " .  $_GET['table'];
if (isset ($_GET['orderby'])) {
    $order = $_GET['orderby'];
	$mysql = $mysql . " ORDER BY $order";
}
sqlDump ($mysql);
?>
</BODY>

