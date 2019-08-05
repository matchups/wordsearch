<?php
// Called by category field in form via Bootstrap tool to provide a list of potential matches
$maxsuggest = 5; // value used by typeahead tool
try {
  if ($query = $_REQUEST['query'] ?? ($_GET['query'] ?? '')) {
    include "/usr/home/adf/credentials.php";
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Find matching categories and give them some value for number of members
    $result = $conn->query("SELECT title, count(*) AS members FROM category INNER JOIN entry_cat ON entry_cat.cat_id = category.id
        WHERE title LIKE '$query%' GROUP BY category.id");
    while (($row = $result->fetch(PDO::FETCH_ASSOC))) {
  		$catList[$row['title']] = $row['members'];
    }

    // Find more matching categories and give them some value for number of child categories
    $result = $conn->query("SELECT title, count(*) AS members FROM category INNER JOIN catparent ON catparent.parentcat = category.title
        WHERE title LIKE '$query%' GROUP BY category.id");
    while (($row = $result->fetch(PDO::FETCH_ASSOC))) {
      $catList[$row['title']] = ($catList[$row['title']] ?? 0) + $row['members'] * 5;
    }

    // sort what we found
    foreach ($catList as $title => $value) {
      $resultSort [chr (strlen ($value) + 64) . $value . "\t" . $title] = '';
    }
    krsort ($resultSort);

    // Compile final results
    $counter = 0;
    foreach ($resultSort as $key => $dummy) {
      $finalResult [$counter++] = explode ("\t", $key)[1];
      if ($counter == $maxsuggest) {
        break;
      }
    }

  }
} catch (PDOException $e) {
  $finalResult[1] = $e.getMessage();
}
//Return JSON array
echo json_encode ($finalResult);
?>
