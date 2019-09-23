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
$type = "back";
Echo "<script src='//code.jquery.com/jquery-2.1.4.min.js'></script>
	<script src='//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
	<script src='//xnetsh.pp.ua/upwork-demo/1/js/typeahead.js'></script>
	<script src='utility$type.js'></script>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles$type.css'>
</HEAD>\n";
?>

<BODY>
<?php
$version = "0.72";
echo "<H2>Word Search <span class='specs'>$version</span></H2>";
include "utility$type.php";
include "cons$type.php";
include "corpus$type.php";
include "form$type.php";
echo "<P>\n";

preserveInfo ($type, $version);

$level = $_GET['level'];
$security = "?sessionkey=" . $_GET['sessionkey'] . "&level=$level";
// Links to other versions of the project, based on permissions
if ($type != "back"  &&  $level > 1) {
	Echo "<A HREF='indexback.html$security&type=back'>Previous</A><BR>";
}
if ($type != "") {
	Echo "<A HREF='index.php$security'>Current</A><BR>";
}
if ($type != "beta"  &&  $level > 1) {
	Echo "<A HREF='indexbeta.php$security&type=beta'>Beta</A><BR>";
}
if ($type != "dev"  &&  $level > 2) {
	Echo "<A HREF='indexdev.php$security&type=dev'>Development</A><BR>";
}
?>
</BODY>
