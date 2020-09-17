<?php
// Display results (in $result)
function showResults ($result, $consObjects, $corpusObjects) {
	$type = $_GET['type'];
	$level = $_GET['level'];
	if ($level == 3) {
		$timeout = 200;
	} else {
		$timeout = 30;
	}
	$timeout = $timeout + microtime (true);

	if (get_class ($result) <> "PDOStatement") {
		echo "<span class='error'>$result</span>"; // Error message
		return;
	}

		// Loop through results from database
	$found = false;
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$oneword = $row['word'];
		$corpus = $row['corpus'];
		$entry = $row['entry'];
		$entry_id = $row['entry_id'];
		$matched = true;
		// Check any constraints that require client-side work
		foreach ($consObjects as $thisConsObject) {
			if (!$thisConsObject->localFilter ($oneword, $entry, $entry_id)) {
				$matched = false;
				break;
			}
		}

		if ($matched) {
			if ($oneword == $previous) {
				$same = true;
			} else {
				if ($previous <> '') {
					echo "<BR>";
				}
				$previous = $oneword;
				$same = false;
			}
			if ($row['whole'] == 'Y') {
				// If this is the whole entry, set up a link
				echo $corpusObjects[$corpus]->answerLink ($entry);
			} else {
				// Else if part of an entry name, set up a link to our page to list phrases
				if (!$same) {
					Echo "$oneword  ";
				}
				if ($corpusObjects[$corpus]->phrases()) {
					Echo "<A target='_blank' " .
						"HREF='phrases$type.php?base=$oneword&corpus=$corpus&type=$type&level=$level'>" .
						"<i>phrases</i></A>";
				}
			}
			$prevword = strtolower ($oneword);
			$found = true; // We found something, so don't tell them later that we didn't
		}
		if (microtime (true) > $timeout) {
			return "time^$oneword";
			break;
		}
	} // end while

  if ($found) {
		return 'ok';
	} else {
		Echo "No matches found.<BR>";
		return 'none';
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
