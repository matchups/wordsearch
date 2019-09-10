<?php
if ($_GET['test']) {
  $debug = true;
} else {
  //Set the content-type header and charset.
  header("Content-Type: text/css; charset=utf-8");
  $debug = false;
}
include "utility.php"; // Might be nice to get $type one, but not sure we can do it in this context

$ipaddress = $_SERVER['REMOTE_ADDR'];
$userid = '0';
$conn = openConnection (false);
try {
  $sql = "SELECT max(id) AS session_id FROM session WHERE status = 'A' AND concat('|', ip_address, '|') LIKE '%|$ipaddress|%'";
  $result = $conn ->query($sql);
  if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $sql = "SELECT user_id FROM session WHERE id = {$row['session_id']}";
    $result = $conn ->query($sql);
    if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $userid = $row['user_id'];
    }
  }

  if ($userid == '0') {
    throw new Exception ("Unable to get user ID");
  }

  // Get list of corpora, regardless of whether they have categories
  $sql = "SELECT DISTINCT corpus.id FROM corpus LEFT OUTER JOIN corpus_share ON corpus_share.corpus_id = corpus.id
    WHERE corpus.owner = $userid OR corpus_share.user_id = $userid OR owner IS NULL";
  $result = $conn ->query($sql);
  while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo ".category{$row['id']}, ";
  }

  // Provide lookup CSS
  echo ".categoryend {
              border: 2px solid #CCCCCC;
              border-radius: 8px 8px 8px 8px;
              font-size: 24px;
              height: 45px;
              line-height: 30px;
              outline: medium none;
              padding: 8px 12px;
              width: 400px;
          }
  ";
  $display = 'none';
}
catch (Exception $e) {
  $display = 'inline';
  if ($debug) {echo $sql . '<P>' . $e->getMessage();}
}
echo '.csswarning {display: ' . $display . '; color: red}';
?>
