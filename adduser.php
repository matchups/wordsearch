<HTML>
<HEAD>
<TITLE>Add user</TITLE>
</HEAD>
<BODY>
<H1>Add User</H1>
<?php
/*
foreach ($_POST as $key => $value) {
    echo "$key>>$value<BR>\n";
}
*/
include "/usr/home/adf/credentials_w.php";
try {
    // Connect and run query
    // Beware of overuse of '$username' variable.  For historical reasons, not worth trying to change either.
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = $_POST['username'];
    $email = $_POST['email'];
    $pwhash = substr (hash ('sha512', $_POST['password_sha']), 0, 56);
    $realname = $_POST['realname'];
    $level = $_POST['level'];

    $valid = true;
    foreach (array ('username', 'email') as $dupcheck) {
	$value = $_POST[$dupcheck];
	$sql = "SELECT id FROM user WHERE $dupcheck='$value'";
	if ($conn->query($sql)->rowCount() > 0) {
	    echo "<font color=red>Sorry, the $dupcheck <i>$value</i> is already in use.</font><br>\n";
	    $valid = false;
	}
    }
    if ($valid) {
	$sql = "INSERT user (username, realname, email, password_SHA, level) " .
		" VALUES ('$username', '$realname', '$email', '$pwhash', $level)";
	$conn->exec ($sql);
	echo "Account successfully created.";
    }
}

catch(PDOException $e)
{
    echo "SQL failed: " . $e->getMessage();
}
?>
</BODY>
