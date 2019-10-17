<?php
// Display results (in $result)
function showResults ($result, $consObjects, $corpusObjects) {
	$type = $_GET['type'];
	$level = $_GET['level'];
	$counter = 0;
	$timedOut = false;
	if ($level == 3) {
		$timeout = 110;
	} else {
		$timeout = 30;
	}
	$timeout = $timeout + $GLOBALS['time']['top.int'];

	if (get_class ($result) <> "PDOStatement") {
		echo "<span class='error'>$result</span>"; // Error message
		return;
	}

		// Loop through results from database
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$oneword = $row['word'];
		$corpus = $row['corpus'];
		$entry = $row['entry'];
		$entry_id = $row['entry_id'];
		$matched = true;
		// Check any constraints that require client-side work
		foreach ($consObjects as $thisConsObject) {
			if (!$thisConsObject->localFilterArray ($row)) {
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
			$found [++$counter] = array ('text' => $entry, 'corpus' => $corpus);
			if ($row['whole'] == 'Y') {
				// If this is the whole entry, set up a link
				echo $corpusObjects[$corpus]->answerLink ($entry) . ' ';
			} else {
				// Else if part of an entry name, set up a link to our page to list phrases
				if (!$same) {
					Echo "$oneword  ";
				}
				if ($corpusObjects[$corpus]->phrases()) {
					Echo "<A target='_blank'
						HREF='phrases$type.php?base=$oneword&corpus=$corpus&type=$type&level=$level'><i>phrases</i></A>";
				}
			}
			$prevword = strtolower ($oneword);
		}
		if (microtime (true) > $timeout) {
			$timedOut = true;
			break;
		}
	} // end while

  try {
		if ($counter > 0  &&  $level > 1){
			$connw = openConnection (true);
			if ($row = (SQLQuery ($connw, "SELECT id FROM session WHERE session_key = '{$_GET['sessionkey']}'")->fetch(PDO::FETCH_ASSOC))) {
				$sessionID = $row['id'];
			} else {
				THROW Exception ("No results getting session ID for $sessionKey");
			}

			// Delete previous list unless we're continuing
			if (!isset ($_GET['from'])) {
				$connw -> exec ("DELETE FROM session_words WHERE session_id = $sessionID");
			}
			foreach ($found as $entry) {
				$stmt = $connw->prepare('INSERT session_words (session_id, entry, corpus_id) VALUES (?, ?, ?)');
				$stmt->execute(array ($sessionID, $entry ['text'], $entry ['corpus']));
			}
			$ret ['save'] = $sessionID;
		}
	} catch (Exception $e) {
		errorMessage ($e->getMessage());
	} finally {
		unset ($connw);
	}

  if ($timedOut) {
		$ret ['code'] = 'time';
		$ret ['restart'] = $oneword;
	} else if ($counter > 0) {
		$ret ['code'] = 'ok';
	} else {
		$ret ['code'] = 'none';
  }
	return $ret;
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
			echo "<P>Sorry, currently unable to show possible indexes.  Please edit resultsdev.php.<P>";
			// sqlDump ("SHOW index FROM " . $table);
		}
	}
}
?>
