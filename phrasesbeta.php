<HTML>
<HEAD>
<TITLE>
<?php
$base = $_GET['base'];
Echo "$base Phrases";
?>
</TITLE>
</HEAD>
<BODY>
<H2>
<?php
  $corpusList = $_GET['corpus'];
  $type = $_GET['type'];
  $level = $_GET['level'];
Echo "<link rel='stylesheet' href='styles$type.css'>
  Phrases containing <span class='specs'>$base</span></H2>";
// Prepare for phrase search
$sql = "SELECT entry.name AS entry, " .
      " entry.corpus_id AS corpus FROM words PW".
	" INNER JOIN word_entry ON word_entry.word_id = PW.id " .
	" INNER JOIN entry ON entry.id = word_entry.entry_id " .
	" WHERE entry.corpus_id IN ($corpusList)" .
	" AND PW.text = '$base'" .
	" AND char_length(entry.name) > " . strlen($base) .
	" ORDER BY entry.name";

include "utility$type.php";
include "cons$type.php";
include "corpus$type.php";

try {
  comment ($sql);
  $conn = openConnection (false);
  $result = $conn->query($sql);
  $m = $result->rowCount();
  comment ("Got $m rows for $corpusList");

  foreach (explode (',', $corpusList) as $corpus) {
    $corpusObject[$corpus] = corpus::factory($corpus);
  }

  // Loop through words and display results
  $link = $_GET['link'];
  while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $entry = $row['entry'];
    if ($link == '*') {
      echo $corpusObject[$row['corpus']]->answerLink ($entry);
    } elseif ($link) {
      $entryLink = str_replace ('@', urlencode ($entry), $link);
      Echo "<A HREF='$entryLink' target='_blank'>$entry</A>  ";
    } else {
      echo $entry;
    }
    echo "<BR>\n";
  } // end while
}
catch(PDOException $e) {
  errorMessage ("SQL failed: $sql... " . $e->getMessage());
}
?>
</BODY>
