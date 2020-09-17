<?php
$type = "beta";
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
echo scriptStyleRefs (true, $type, true);
include "cons$type.php";
include "corpus$type.php";

echo "
</HEAD>

<BODY>\n";

$version = "0.85";
echo "<H2>Word Search <span class='specs'>$version</span></H2>";
$level = $_GET['level'];
include "form$type.php";
echo "<P>\n";

?>
</BODY>
