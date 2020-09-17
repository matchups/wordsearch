<?php
// Called by category field in form via Bootstrap tool to provide a list of potential matches
$maxsuggest = 5;
$debug = false;
try {
  if ($query = $_REQUEST['query'] ?? ($_GET['query'] ?? '')) {
    $corpus = $_REQUEST['corpus'] ?? ($_GET['corpus'] ?? '');
    $mode = $debug ? '_w' : '';
    include "/usr/home/adf/credentials$mode.php";
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    debugLog ("$corpus $query");

    // Find matching words and give them some value for number of members
    $result = $conn->query("SELECT name FROM entry
        WHERE name LIKE '$query%' AND corpus_id = $corpus");
        $counter = 0;
    while (($row = $result->fetch(PDO::FETCH_ASSOC))) {
  		$finalResult [$counter++] = $row['name'];
      if ($counter == $maxsuggest) {
        break;
      }
    }
  }
} catch (PDOException $e) {
  $finalResult[0] = $e.getMessage();
}
//Return JSON array
header('Content-Type: application/json');
//Return JSON array
$ret = json_encode (array ("data" => $finalResult));
echo $ret;
debugLog (count($finalResult) . ': ' . substr ($ret, 0, 40));

function debugLog ($message) {
  if ($GLOBALS['debug']) {
    $message = str_replace ("'", ".", $message); // safe SQL
    $time = date ('D M j, Y  g:i:sa', time());
    $GLOBALS['conn']->exec ("INSERT query (owner, name, parms) values (-1, '$time', '$message')");
  }
}
?>
