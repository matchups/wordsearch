<HTML>
<HEAD>
<TITLE>PHP Testing 1</TITLE>
</HEAD>

<BODY>
top
<BR>
<?php
  include "/usr/home/adf/credentials_w.php";

try {
    $conn = new PDO("mysql:host=$servername;dbname=adf_words", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

runit ("delete from query where owner < 0");

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
