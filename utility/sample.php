<HTML>
<HEAD>
</HEAD>
<BODY>
<?php
include "/usr/home/adf/credentials.php";
$servername = "db153.pair.com";
$username = "adf_w";
$password = "d7cJxwYR";

$mysql = "delete from user";
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn->exec ($mysql);
?>
</BODY>

