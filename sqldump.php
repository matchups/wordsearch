<?php
class TableRows extends RecursiveIteratorIterator {
    function __construct($it) {
        parent::__construct($it, self::LEAVES_ONLY);
    }

    function current() {
        return "<td style='width:150px;border:1px solid black;'>" . parent::current(). "</td>";
    }

    function beginChildren() {
        echo "<tr>";
    }

    function endChildren() {
        echo "</tr>" . "\n";
    }
}

/* First arg is SQL; second is parameter
*/
function sqlDump () {
  echo "<table style='border: solid 1px black;'>";
  if (func_num_args() == 0) {
     echo 'No arguments passed to sqlDump!';
     return;
  }

  try {
    $mysql = func_get_arg(0);
    $stmt = openConnectionRead()->prepare($mysql);
    if (func_num_args() == 1) {
      $stmt->execute();
    } else {
      $stmt->execute(array (func_get_arg(1)));
    }

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach(new TableRows(new RecursiveArrayIterator($stmt->fetchAll())) as $k=>$v) {
      echo $v;
    }
  }
  catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "<BR>$mysql<BR>";
  }
  $conn = null;
  echo "</table>";
}

function verifySession () {
  if (isset ($_GET['sessionkey'])) { // make sure session info is passed to us
  	$session = $_GET['sessionkey'];
		$sql = "SELECT 1 FROM session WHERE session_key = '$session' AND status = 'A' and user_id = 3";
		$result = openConnectionRead ()->query($sql);
		if ($result->rowCount() > 0) {
      return true;
    }
  }
  return false;
}

function openConnectionRead () {
  include "/usr/home/adf/credentials.php";
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $conn;
}
?>
