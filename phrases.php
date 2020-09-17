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
<link rel="stylesheet" href="styles.css">
<H2>
  <?php
Echo "Phrases containing <span class='specs'>$base</span></H2>";
// Prepare for phrase search
$corpus = $_GET['corpus'];
$type = $_GET['type'];
$level = $_GET['level'];
$sql = "SELECT entry.name AS entry, " .
      " entry.corpus_id AS corpus FROM words PW".
	" INNER JOIN word_entry ON word_entry.word_id = PW.id " .
	" INNER JOIN entry ON entry.id = word_entry.entry_id " .
	" WHERE entry.corpus_id IN ($corpus)" .
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
  comment ("Got $m rows for $corpus");

  $corpusObject = corpus::factory($corpus); // need to handle multiple corpora

  // Loop through words and display results
  while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $corpusObject->answerLink ($row['entry']) . "<BR>\n";
  } // end while
}
catch(PDOException $e) {
  errorMessage ("SQL failed: $sql... " . $e->getMessage());
}
?>
</BODY>
