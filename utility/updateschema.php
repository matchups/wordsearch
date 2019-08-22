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

    $tname = "word_entry";
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

    runit ("UPDATE entry SET flags = concat (entry.flags, 'C') where entry.corpus_id = 3 and entry.flags NOT like '%C%' and (id between 6250000 and 6254937 or id between 6262942 and 6331000 or id between 6331582 and 6331656)");
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
