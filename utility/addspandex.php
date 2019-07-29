<HTML>
<HEAD>
<?php
include 'addone.php';

?>
</HEAD>
<BODY>
<?php
$servername = "db153.pair.com";
//$username = "adf_w";
//$password = "d7cJxwYR";
$username = "adf";
$password = "aSs8BdpW";

try {
    $conn = new PDO("mysql:host=$servername;dbname=adf_words", 
    	$username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tname = "spandex";
    $sql = "CREATE TABLE $tname (
	id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	value int)";

    $conn->query($sql);
    echo "12:24 Table $tname created successfully";

    for ($ord = 1; $ord <= 100; $ord++) {
	sqlInsert ($conn, "INSERT spandex (value) VALUES ($ord)");
    }
}

catch(PDOException $e)
    {
    echo "SQL failed: " . $e->getMessage();
    }
?>
</BODY>