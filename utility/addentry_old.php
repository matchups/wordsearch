<HTML>
<HEAD>
<?php
include 'addone.php';
?>
</HEAD>
<BODY>
<?php
$servername = "db153.pair.com";
$username = "adf_w";
$password = "d7cJxwYR";

try {
    $conn = new PDO("mysql:host=$servername;dbname=adf_words", 
    	$username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if (isset ($_GET['word'])) {
   	$entry = $_GET['word'];
   	newEntry ($conn, $entry, 86);
    } else {
   	Echo "No word";
    }
}

catch(PDOException $e)
    {
    echo "SQL failed: " . $e->getMessage();
    }

Echo '<form name="test" action="addentry.php" id="test" method="get">';
Echo "<label>Word: <input type=text name=word value='$entry'/></label><br>";
?>
<P>
<input type="submit" value="Submit" id="xxx"/>
</form>
</BODY>

