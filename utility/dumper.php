<HTML>
<HEAD>
<TITLE>SQL dump</TITLE>
</HEAD>
<BODY>
<?php
include 'sqldump.php';

$mysql = $_GET['sql'];
echo $mysql;
echo '<BR>';
if (verifySession ()) {
  sqlDump ($mysql);
}
?>
</BODY>
