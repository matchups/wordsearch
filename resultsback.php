<?php
// Display results (in $result)
function showResults ($result, $consobj) {
	$corpusinfo = $GLOBALS['conn']->query("SELECT id, url FROM corpus");
	while($row = $corpusinfo->fetch(PDO::FETCH_ASSOC)) {
		$url[$row['id']] = $row['url'];
	}

	$type = $_GET['type'];
	$level = $_GET['level'];

	if (get_class ($result) <> "PDOStatement") {
		echo "<span class='error'>$result</span>"; // Error message
		return;
	}

	$count = count ($consobj) + 1;

	// Loop through results from database
	$found = false;
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$oneword = $row['word'];
		$matched = true;
		// Check any additional regexes
		for ($num = 2; ($num <= $count) && $matched; $num++) {
			$matched = $consobj[$num]->localFilter ($oneword);
		}
		if ($matched) {
			$corpus = $row['corpus'];
			$entry = $row['entry'];
			$debug = "corpus=$corpus ow=$oneword prev=$previous";
			if ($oneword == $previous) {
				$same = true;
			} else {
				if ($previous <> '') {
					echo "<BR>";
				}
				$previous = $oneword;
				$same = false;
			}
			if ($url[$corpus] == '') {
				Echo "$entry  ";
			} elseif ($row['whole'] == 'Y') {
				// If this is the whole entry, set up a link
				echo answerLink ($entry, $corpus, $url[$corpus]);
			} else {
				// Else if part of an entry name, set up a link to our page to list phrases
				if (!$same) {
					Echo "$oneword  ";
				}
				Echo "<A target='_blank' " .
					"HREF='phrases$type.php?base=$oneword&corpus=$corpus&type=$type&level=$level'>" .
					"<i>phrases</i></A>";
			}
			$prevword = strtolower ($oneword);
			$found = true; // We found something, so don't tell them later that we didn't
		}
	} // end while

  if (!$found) {
		Echo "No matches found.<BR>";
  }
} // end showResults

include 'sqldump.php';

function showExplain ($result) {
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo '<BR>';
		foreach ($row as $key => $value) {
			echo "$key=$value / ";
		}
		if (!isset($row['key'])) {
			$table = $row['table'];
			if ($table == 'PW' || $table == 'SW') {
				$table = 'words';
			}
			sqlDump ("SHOW index FROM " . $table);
		}
	}
}
?>
