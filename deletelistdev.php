<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

if ($upload = ($_GET['source'] == 'upload')) {
	$sourcedesc = 'Upload';
	$method = 'post';
	$enctype = 'enctype=multipart/form-data';
} else {
	$sourcedesc = 'Save Results as';
	$method = 'get';
	$enctype = '';
}
echo "<HTML>
<HEAD>
	<script src='//code.jquery.com/jquery-2.1.4.min.js'></script>
		<script src='//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
		<script src='//netsh.pp.ua/upwork-demo/1/js/typeahead.js'></script>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	$sourcedesc Word List
	</TITLE>
</HEAD>
<BODY>
	<H2>Save Results</H2>\n";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to save results; code $code");
	}
	echo "
	<form name='save' id='save' method='$method' action='dosaveresults$type.php' $enctype><BR>\n";
	if ($upload) {
		echo "Select word list file to upload:
		    <input type='file' name='uploadfile' id='uploadfile'><BR>\n";
	}
	echo "<input type=text name=listname required=true /> Word list name<BR>
	<input type=radio name=savetype id=typenew value=new checked=true />
	New word list
	<input type=radio name=savetype id=typeover value=over />
	Overwrite existing list
	<input type=radio name=savetype id=typeadd value=add />
	Add to existing list
	<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
	<input type=hidden name=level value='$level'>
	<input type=hidden name=source value='{$_GET['source']}'>
	<input type=hidden name=type value='$type'>
	<BR>
	<input type='submit'>
	</form>\n";

  $conn = openConnection (true);
	$conn->exec ("UPDATE session SET last_active = UTC_TIMESTAMP() WHERE session_key = '$session'");
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo '</BODY>';
// End of main script
?>