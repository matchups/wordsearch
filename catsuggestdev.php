<?php
// Called by category field in form via Bootstrap tool to provide a list of potential matches
$maxsuggest = 5; // value used by typeahead tool
if (isset($_REQUEST['query'])) {
  $counter = 0;

  include "/usr/home/adf/credentials.php";
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $result = $conn->query("SELECT title FROM category WHERE title LIKE '{$_REQUEST['query']}%'");
  while (($row = $result->fetch(PDO::FETCH_ASSOC))  &&  $counter <= $maxsuggest) {
		$catList[$counter++] = array ('label' => $row['title'], 'value' => $row['title']);
    }

  //Return JSON array
  echo json_encode ($catList);
}
?>
