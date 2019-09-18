<?php
// Called by user lookup field in various forms to provide a list of potential matches
$maxsuggest = 5; // value used by typeahead tool
try {
  if ($query = $_REQUEST['query'] ?? ($_GET['query'] ?? '')) {
    include "/usr/home/adf/credentials.php";
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Find matching user and give them some value for number of sessions
    $userid = $_REQUEST['userid'] ?? $_GET['userid'];
    $sql = "SELECT user.realname, count(*) AS sessions FROM user INNER JOIN session ON session.user_id = user.id
        WHERE user.realname LIKE '%$query%' AND user.id <> $userid GROUP BY user.id";
    $result = $conn->query($sql);
    while (($row = $result->fetch(PDO::FETCH_ASSOC))) {
  		$userList[$row['realname']] = $row['sessions'];
    }

    // sort what we found
    foreach ($userList as $name => $value) {
      $resultSort [chr (strlen ($value) + 64) . $value . "\t" . $name] = '';
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
  $finalResult = array ($e.getMessage());
}
//Return JSON array
echo json_encode ($finalResult);
?>
