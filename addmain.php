<?php
include 'asciitize.php';

function cleanSQL ($text) {
    return str_replace ('\'', '\\\'', str_replace ('\\', '\\\\', $text));
}

function newEntry ($conn, $entry, $flags, $corpus, $helper) {
  $helper->userText ("<BR>newEntry ('$entry', '$flags', $corpus)");
  if (isset ($_GET["debug"])) {
    echo ' {';
      for ($here = 0; $here < strlen ($entry); ++$here) {
        $char = substr ($entry, $here, 1);
        echo "$here:$char:" . ord ($char) . ' ';
      }
    echo '} ';
  }

  // Ignore stuff after a paren, because Wikipedia uses it for
  // disambiguation
  $paren = strpos ($entry, '(');
  if ($paren > 0) {
     $entry = substr ($entry, 0, $paren);
  }
  $entry = chop ($entry);

  // If it's already there, don't add it again
  $bank = '';
  $stripped = strtolower ($ascii = asciitize ($entry));
  if ($ascii != $entry) {
    $helper->userText ("-->$ascii ");
  }
  for ($ord = 97; $ord < 123; $ord++) {// a to z
    $onechar = chr ($ord);
    if (strpos ($stripped, $onechar) !== False) {
        $bank = $bank . $onechar;
    }
  }
  $stmt = $conn->prepare('SELECT entry.id, flags FROM entry INNER JOIN word_entry ON entry.id = word_entry.entry_id
    INNER JOIN words ON word_entry.word_id = words.id WHERE name = ? AND corpus_id = ? AND bank = ?');
  $stmt->execute(array ($entry, $corpus, $bank));
  if ($conn->errorCode() <> "fake") {
    if (($result = $stmt->fetch (PDO::FETCH_ASSOC)) !== false) {
      $id = $result['id'];
      // if this is not a redirect or foreign entry and that one is, update it
      $dbflags = $result['flags'];
      if ($dbflags > ''  &&  $flags == '') {
        $conn->exec ("UPDATE entry SET flags = '' WHERE id = $id");
      }
      return;
    }
  }

  // We only want to process words with at least three letters in a row
  if (!preg_match ('/[a-z]{3}/', strtolower ($ascii))) {
	   $helper->userText ("No three letters in $entry<br>");
	   return;
  }

  // insert entry
  $clean = cleanSQL ($entry);

  $entry_id = sqlInsert ($conn, "INSERT entry (name, flags, corpus_id) VALUES ('$clean', '$flags', $corpus)");
  $helper->userText ("...$entry_id");
  newWord ($conn, $entry_id, $ascii, True) ;

  // insert each word
  $words = explode (' ', str_replace ('"', '', $ascii));
  if (count ($words) > 1) {
	   foreach ($words as $oneword) {
    	 newWord ($conn, $entry_id, $oneword, False);
	     $subwords = explode ('-', $oneword);
       if (count ($subwords) > 1) {
		      foreach ($subwords as $piece) {
            newWord ($conn, $entry_id, $piece, False);
		      }
	     }
     }
   }

  $ret = array ('id' => $entry_id);
  if (strpos ($flags, 'R') === false) {
    foreach ($helper->getCategories ($conn, $entry) as $category) {
      $cat_id = $helper->getCatID ($conn, $category, $corpus, 0);
      sqlInsert ($conn, "INSERT entry_cat (entry_id, cat_id) VALUES ($entry_id, $cat_id)");
      $helper->category ($category, $ret);
    }
  }

  $helper->postProcess ($conn, $ret);
  return $ret;
} // end newEntry

function newWord ($conn, $entry_id, $word, $full) {
  debug ("newWord ($entry_id, $word, $full)");
  $weight = "01110212032010013000022323";
  $stripped = '';
  for ($pos = 0; $pos < strlen ($word); $pos++) {
    $onechar = substr ($word, $pos, 1);
	  // Skip over HTML codes such as &amp;
	  if ($onechar == '&') {
	    $semi = strpos ($word, ';', $pos);
	    if ($semi > 0) {
		      $pos = $semi;
		      continue;
	    }
  	}
    if (preg_match ('/[a-zA-Z]/', $onechar)) {
	    $stripped = $stripped . $onechar;
	  }
  }
  // we don't care about one- and two-letter words or TLAs
  $length = strlen ($stripped);
  if ($length < 3) {return;}
  if ($length == 3  &&  !preg_match ('/[a-z]/', $stripped)) {return;}
  $stripped = strtolower ($stripped);

  if ($conn->errorCode() == "fake") {
	  $word_id = 0;
  } else {
  	// See if word already exists
  	$stmt = $conn->prepare('SELECT id FROM words WHERE text = ?');
  	$stmt->execute(array ($stripped));
   	$word_id = $stmt->fetchColumn ();
  }
  debug ("word_id=$word_id");

  if ($word_id > 0) {}
  else {
    $bank = '';
	  for ($ascii = 97; $ascii < 123; $ascii++) {// a to z
	    $onechar = chr ($ascii);
	    if (strpos ($stripped, $onechar) !== False) {
		      $bank = $bank . $onechar;
	    }
  	}
    $word_id = sqlInsert ($conn, "INSERT words (text, length, bank) VALUES ('$stripped', $length, '$bank')");

  	for ($pos = 0; $pos + 2 < $length; $pos ++) {
      sqlInsert ($conn, "INSERT word_four (word_id, quartet) VALUES ($word_id, '" . substr ("$stripped#", $pos, 4) . "')");
    }

  	// Track positions of rare letters
  	for ($s1 = 0; $s1 < $length; $s1++) {
      $c1 = substr ($stripped, $s1, 1);
      $score1 = substr ($weight, ord ($c1) - 97, 1);
      if ($score1 > 0) {
      	if ($score1 == 3) {
    	    sqlInsert ($conn, "INSERT word_position (word_id, position, length, letter) VALUES ($word_id, " .	($s1 + 1) . ", $length, '$c1')");
		    }
      }
   	} // end for

  	// Track words with unusual combinations
  	$lb = strlen ($bank);
  	for ($s1 = 0; $s1 < $lb; $s1++) {
      $c1 = substr ($bank, $s1, 1);
      $score1 = substr ($weight, ord ($c1) - 97, 1);
      if ($score1 > 0) {
        for ($s2 = $s1 + 1; $s2 < $lb; $s2++) {
      	  $c2 = substr ($bank, $s2, 1);
      	  $score2 = substr ($weight, ord ($c2) - 97, 1);
          if ($score1 + $score2 > 3) {
            sqlInsert ($conn, "INSERT word_pair (word_id, pair) VALUES ($word_id, '$c1$c2')");
          }
	      }
	    }
	  } // end for
  } // end else

 // Point from word back to entry
 $whole = ($full) ? 'Y' : 'N';
 $solid = (strpos (strtolower($word), $stripped) === False) ? 'N' : 'Y';
 $caps = (preg_match ('/[A-Z]/', $word)) ? 'Y' : 'N';
 sqlInsert ($conn, "INSERT word_entry (word_id, entry_id, whole, solid, caps) VALUES ($word_id, $entry_id, '$whole', '$solid', '$caps')");
} // end newWord

function sqlInsert ($conn, $sql) {
  try {
  	$conn->exec ($sql);
  	$id = $conn->lastInsertId();
  }

  catch(PDOException $e)
  {
  	$helper->userText ("SQL<!--$sql--> failed: " . $e->getMessage());
  	$id = -99;
  }
  return $id;
}

class loadHelper {
  public function getTitle () {
  	return "No class";
  }

  public function getDomain () {
  	return "www.aprilfools.com";
  }

  public function postProcess ($conn, $ret) {
  }

  public function category ($category, &$ret) {
  }

  public function getCategories ($conn, $title) {
    return array();
  }

  public function getCatID ($conn, $title, $corpus, $depth) {
    return '';
  }

  public function userText ($text) {
    if (isset ($_GET["debug"])) {echo " (($text))";}
  }
} // end class loadHelper

class dummyConnection extends PDO {
  private $prepared;

  public function exec ($sql) {
    echo "<BR>$sql";
  }

  public function lastInsertId() {
    $id = rand (10000, 99999);
    echo " / id=$id";
    return $id;
  }

  public function prepare ($sql) {
    // echo "Preparing $sql";
    $this->prepared = str_replace ('\n', '', $sql);
    return $this;
  }

  public function execute ($parms) {
    // echo "executing " . implode (',', $parms);
    $sql = $this -> prepared;
    foreach ($parms as $oneParm) {
      $here = strpos ($sql, '?');
      $sql = substr ($sql, 0, $here) . "'" . $oneParm . "'" . substr ($sql, $here + 1);
    }
    $this->exec ($sql);
  }

  public function errorCode() {
    return "fake";
  }

  public function __construct() {
    // nothing needed
  }

  public function fetch ($ignored) {
    return rand(0,6) == 3;
  }
} // end class

function openConn ($corpus) {
  include "/usr/home/adf/credentials_w.php";
  if (substr ($corpus, 0, 1) == "x") {
	  $conn = new dummyConnection();
  } else {
	  $conn = new PDO("mysql:host=$servername;dbname=adf_words", $username, $password);
    // set the PDO error mode to exception
	  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  return $conn;
} // end openConn

function debug ($message) {
  if (isset ($_GET['debug'])) {
    echo "<BR>$message";
  }
}

function deleteAll ($connw, $corpusid) {
  $sql = "SELECT id FROM entry WHERE corpus_id = $corpusid";
  $result = $connw->query($sql);
  while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    deleteEntry ($connw, $row['id']);
  }
}

function deleteEntry ($connw, $entry) {
  $connw->exec ("DELETE FROM entry WHERE id = $entry");
  $connw->exec ("DELETE FROM entry_cat WHERE entry_id = $entry");

  $stmt = $connw->prepare('SELECT word_id FROM word_entry WHERE entry_id = ?');
  $stmt->execute(array ($entry));
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $words [$row['word_id']] = '';
  }
  $connw->exec ("DELETE FROM word_entry WHERE id = $entry");
  foreach ($words as $word_id => $dummy) {
    $count = ($connw -> query ("SELECT count(1) FROM word_entry WHERE word_id = $word_id")->fetch(PDO::FETCH_NUM))[0];
    if ($count == 0) {
      foreach (array ('word_four', 'word_pair', 'word_position') as $table) {
        $connw->exec ("DELETE FROM $table WHERE word_id = $word_id");
      }
    }
  }
}
?>
