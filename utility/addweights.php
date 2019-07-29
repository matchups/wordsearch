<HTML>
<HEAD>
<?php
include 'addone.php';

function setWeights ($conn, $name, $weights) {
    Echo "setWeights $name<br>";
    for ($ord = 0; $ord < 26; $ord++) {
	$char = chr (ord ('a') + $ord);
	$weight = $weights [$ord];
	sqlInsert ($conn, "INSERT weights (name, letter, weight) " .
	    "VALUES ('$name', '$char', $weight)");
    }
}
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
    Echo "<b>11:44</b><p>";
    $conn = new PDO("mysql:host=$servername;dbname=adf_words", 
    	$username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
/*
    // Table already created
    $tname = "weights";
    $sql = "CREATE TABLE $tname (
	id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name varchar (255),
	letter char (1),
	weight int)";

    $conn->query($sql);
    echo "11:23 Table $tname created successfully";
*/
    $array = str_split ('1332142418513113-11114484-');
    $array [16] = 10; //q
    $array [25] = 10; //z
    setWeights ($conn, 'SCR', $array);

    for ($ord = 0; $ord < 26; $ord++) {
	$array [$ord] = $ord + 1;
    }
    setWeights ($conn, 'ALF', $array);
}

catch(PDOException $e)
    {
    echo "SQL failed: " . $e->getMessage();
    }
?>
</BODY>