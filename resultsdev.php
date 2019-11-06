<?php
// Display results (in $result)
function showResults ($result, $consObjects, $corpusObjects) {
	$type = $_GET['type'];
	$level = $_GET['level'];
	$urlkey = urlencode ($_GET['sessionkey']);
	$security = "sessionkey=$urlkey&level=$level&type=$type&version={$_GET['version']}";
	$moreParms = '';
	if (!$pagelimit = ($_GET['pagelen'] ?? 0)) {
		$pagelimit = 1E9;
	}
	foreach ($_GET as $key => $parm) {
		if (substr ($key, 0, 6) == 'corpus'  ||  preg_match ('/^c([0-9]+)flag(.)(.*)$/', $key)  ||
				$key == 'phrase' || $key == 'single' || $key == 'whole') {
			if ($parm == 'on') {
				$moreParms .= "&$key=on";
				}
			}
		}
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

	switch ($_GET['linkoption']) {
		// foreach (array ('suppress', 'source', 'Google', 'Bing', 'Yahoo', 'nGram viewer', 'IMDB', 'custom') as $linkOption) {
		case 'suppress':
		  $link = '';
			break;
		case 'source':
		  $link = '*';
			break;
		case 'google':
		  $link = 'https://www.google.com/search?q=@';
			break;
		case 'bing':
		  $link = 'https://www.bing.com/search?q=@';
			break;
	  case 'yahoo':
		  $link = 'https://search.yahoo.com/search?p=@';
			break;
		case 'ngramviewer':
		  $link = 'https://books.google.com/ngrams/graph?content=@&year_start=1800&year_end=2000';
			break;
		case 'imdb':
		  $link = 'https://www.imdb.com/find?q=@&s=all';
			break;
		case 'custom':
		  $link = $_GET['customlink'];
			break;
	}
	$linkencoded = urlencode ($link);

		// Loop through results from database
	$tabular = getCheckbox ('usetable');
	$td = $tabular ? '<td>' : '';
	$tde = $tabular ? '</td>' : ' ';
	if ($tabular) {
		echo '<table>';
		$header = '<tr>' .
			(getCheckbox ('letteralpha') ? '<th>Letters in Order</th>' : '') .
			(getCheckbox ('letterabank') ? '<th>Without Dupes</th>' : '') .
			'<th />'; // No title for the word itself
		foreach ($consObjects as $rowNumber => $thisConsObject) {
			if ($thisConsObject->detailsEnabled()) {
				$classCounter[get_class ($thisConsObject)]++;
			}
		}
		foreach ($consObjects as $rowNumber => $thisConsObject) {
			if ($thisConsObject->detailsEnabled()) {
				$title = $thisConsObject->tableTitle ($classCounter[get_class ($thisConsObject)] > 1);
				$header .= "<th>$title</th>";
			}
		}
		$header .= '</tr>';
	}
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
					echo $tabular ? '</tr>' : '<br>';
				}
				if ($tabular  &&  $counter % 30 == 0) {
					echo $header;
				}
				echo $tabular ? '<tr>' : '';
				$sorted = stringSort ($oneword);
				$baseURL = "http://alfwords.com/search$type.php";
				if (getCheckbox ('letteralpha')) {
					if (getCheckbox ('letteralinks')) {
						echo "$td<A HREF='$baseURL?pattern=$sorted&anyorder=on&$security$moreParms' target='_blank'>$sorted</A>$tde";
					} else {
						echo "$td$sorted$tde";
					}
				}
				if (getCheckbox ('letterabank')) {
					$sorted = noDupes ($sorted);
					if (getCheckbox ('letteralinks')) {
						echo "$td<A HREF='$baseURL?pattern=$sorted&anyorder=on&repeat=on&$security$moreParms' target='_blank'>$sorted</A>$tde";
					} else {
						echo "$td$sorted$tde";
					}
				}
				$previous = $oneword;
				$same = false;
			}
			$found [++$counter] = array ('text' => $entry, 'corpus' => $corpus);
			$echo = getCheckbox ('lettersonly') ? $oneword : $entry;
			echo $td;
			if ($row['whole'] == 'Y') {
				// If this is the whole entry, set up a link
				if ($link == '*') {
					echo $corpusObjects[$corpus]->answerLink ($entry, $echo) . ' ';
				} else if ($link) {
					$entryLink = str_replace ('@', urlencode ($entry), $link);
					Echo "<A HREF='$entryLink' target='_blank'>$echo</A>  ";
				} else {
					Echo "$echo  ";
				}
			} else {
				// Else if part of an entry name, set up a link to our page to list phrases
				if (!$same) {
					if ($link) {
						$entryLink = str_replace ('@', urlencode ($oneword), $link);
						Echo "<A HREF='$entryLink' target='_blank'>$oneword</A>  ";
					} else {
						Echo "$oneword  ";
					}
				}
				if ($corpusObjects[$corpus]->phrases()) {
					Echo " <A target='_blank'
						HREF='phrases$type.php?base=$oneword&corpus=$corpus&type=$type&level=$level&link=$linkencoded'><i>phrases</i></A> ";
				}
			}

			if (!$same) {
				echo $tde;
				foreach ($consObjects as $rowNumber => $thisConsObject) {
					if ($thisConsObject->detailsEnabled()) {
						$value = $row["cv$rowNumber"];
						if ($tabular  &&  preg_match ('/^[0-9]*$/', $value)) {
							$tdx = "<td style='text-align:right'>";
						} else {
							$tdx = $td;
						}
						echo "$tdx$value$tde";
					}
				}
			}

			$prevword = strtolower ($oneword);
		}

		if (microtime (true) > $timeout) {
			$timedOut = 'T';
			break;
		}
		if ($counter == $pagelimit) {
			$timedOut = 'C';
			break;
		}
	} // end while
	echo $tabular ? '</table>' : '';

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
		$ret ['code'] = 'limit';
		$ret ['subcode'] = $timedOut;
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
