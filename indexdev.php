<?php
if (!isset ($_GET['sessionkey'])  ||  !isset ($_GET['level'])) {
	header("Location: http://www.8wheels.org/wordsearch/index.html"); // No valid session, so ask user to sign on
	exit();
}
?>
<HTML>
<HEAD>
<TITLE>Word Search</TITLE>
<?php
$type = "dev";
Echo "<script src='//code.jquery.com/jquery-2.1.4.min.js'></script>
	<script src='//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
	<script src='//netsh.pp.ua/upwork-demo/1/js/typeahead.js'></script>
	<script src='utility$type.js'></script>\n";
include "utility$type.php";
include "cons$type.php";
include "corpus$type.php";

echo "<meta name='viewport' content='width=device-width, initial-scale=1'>
<link rel='stylesheet' href='styles.css'>
</HEAD>

<BODY>\n";

$version = "0.75h";
echo "<H2>Word Search <span class='specs'>$version</span></H2>";
$level = $_GET['level'];
include "form$type.php";
echo "<P>\n";

preserveInfo ($type, $version);
?>
</BODY>
