<HTML>
<HEAD>
<TITLE>Set up quartet table</TITLE>
</HEAD>

<BODY>
top
<BR>
<?php
$servername = "db153.pair.com";
$username = "adf_w";
$password = "d7cJxwYR";


try {
    $conn = new PDO("mysql:host=$servername;dbname=adf_words", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    runit ("INSERT word_four (word_id, quartet) " .
	"SELECT words.id, substring(concat(words.text, '#'), spandex.value, 4) " .
	"FROM words, spandex " .
	"WHERE spandex.value + 1 < words.length");
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

