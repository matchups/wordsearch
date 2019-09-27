<HTML>
<HEAD>
<TITLE>PHP Testing 1</TITLE>
</HEAD>

<BODY>
top
<BR>
<?php
$servername = "localhost";
$username = "adf";
$password = $_GET["password"];

try {
    $conn = new PDO("mysql:host=$servername;dbname=adf_words", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tname = "query";

/*
    runit ("CREATE TABLE $tname (
	id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	owner int,
	parms varchar(4000))");
*/

    runit ("ALTER TABLE $tname
	ADD name varchar(100)");
//    runit ("CREATE INDEX entrycorpidx ON $tname(corpus_id)");
//    runit ("UPDATE $tname SET like_id = -1 WHERE id in (2,3)");
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
