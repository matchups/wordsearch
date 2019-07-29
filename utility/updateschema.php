<HTML>
<HEAD>
<TITLE>PHP Testing 1</TITLE>
</HEAD>

<BODY>
top
<BR>
<?php
$servername = "db153.pair.com";
$username = "adf";
$password = $_GET["password"];

try {
    $conn = new PDO("mysql:host=$servername;dbname=adf_words", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tname = "catparent";
/*
    runit ("CREATE TABLE $tname (
	id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	entry_id int,
	cat_id int)");
*/
/*
    runit ("ALTER TABLE $tname
	ADD flags char(12)");
*/
    runit ("CREATE INDEX catparentidx ON $tname (parentcat)");
    }
catch(PDOException $e)
    {
    echo "SQL failed: " . $e->getMessage();
    }

function runit ($sql) {
    echo $sql . '<BR>';

    $GLOBALS ['conn']->query($sql);
    echo "Executed successfully<BR>";
}
?>
<BR>
bottom

