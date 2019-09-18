<?php
// Called by category field in form via Bootstrap tool to provide a list of potential matches
$maxsuggest = 5; // value used by typeahead tool
try {
  if ($query = $_REQUEST['query'] ?? ($_GET['query'] ?? '')) {
    $corpus = $_REQUEST['corpus'] ?? ($_GET['corpus'] ?? '');
    include "/usr/home/adf/credentials.php";
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
echo json_encode ($finalResult);
?>
