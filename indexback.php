<?php
$type = "";
include "utility" . $type . ".php";

$conn = openConnection (false); // get into a global for subsequent use

if ($code = securityCheck ($level, $userid, $sessionid)) {
	header("Location: http://www.8wheels.org/wordsearch/index.html?code=$code"); // No valid session, so ask user to sign on
		// and provide a general indication of the error type for our use
	exit();
}
?>
<HTML>
<HEAD>
<TITLE>Word Search</TITLE>
<?php
echo scriptRefs (true, true);
include "cons$type.php";
include "corpus$type.php";

echo "<meta name='viewport' content='width=device-width, initial-scale=1'>
<link rel='stylesheet' href='styles.css'>
<link rel='stylesheet' href='catcss$type.php'>
<link rel='stylesheet' href='wideleft.css'>
</HEAD>

<BODY>\n";

$version = "0.76";
echo "<H2>Word Search <span class='specs'>$version</span></H2>";
$level = $_GET['level'];
include "form$type.php";
echo "<P>\n";
?>
</BODY>
