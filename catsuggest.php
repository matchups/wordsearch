<?php
// Called by category field in form via tool to provide a list of potential matches
$maxsuggest = 5;
$debug = false;
try {
  if ($query = $_REQUEST['query'] ?? ($_GET['query'] ?? '')) {
    $corpus = $_REQUEST['corpus'] ?? ($_GET['corpus'] ?? '');
    header('Content-Type: application/json');
    $mode = $debug ? '_w' : '';
    include "/usr/home/adf/credentials$mode.php";
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    debugLog ("$corpus $query");

    // Find matching categories and give them some value for number of members
    $result = $conn->query("SELECT title, count(*) AS members FROM category INNER JOIN entry_cat ON entry_cat.cat_id = category.id
        WHERE title LIKE '$query%' AND category.corpus_id = $corpus GROUP BY category.id");
    while (($row = $result->fetch(PDO::FETCH_ASSOC))) {
  		$catList[$row['title']] = $row['members'];
    }

    // Find more matching categories and give them some value for number of child categories
    $result = $conn->query("SELECT title, count(*) AS members FROM category INNER JOIN catparent ON catparent.parentcat = category.title
        WHERE title LIKE '$query%' AND category.corpus_id = $corpus GROUP BY category.id");
    while (($row = $result->fetch(PDO::FETCH_ASSOC))) {
      $catList[$row['title']] = ($catList[$row['title']] ?? 0) + $row['members'] * 5;
    }

    // sort what we found
    foreach ($catList as $title => $value) {
      $resultSort [chr (strlen ($value) + 64) . $value . "\t" . $title] = '';
    }
    krsort ($resultSort);

    // Compile final res$ret, 0, 40ults
    $counter = 0;
    foreach ($resultSort as $key => $dummy) {
      $finalResult [$counter] = explode ("\t", $key)[1];
      if (++$counter == $maxsuggest) {
        break;
      }
    }

  }
} catch (PDOException $e) {
  $finalResult[1] = $e.getMessage();
}
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
