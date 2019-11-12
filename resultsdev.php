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
	} // end $tabular

  // process results one at a time
  $timedOut = false;
	while(true) {
		if (!$timedOut  &&  $row = $result->fetch(PDO::FETCH_ASSOC)) {
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
		} else {
			$oneword = '';
			$matched = true;
		}

		if ($matched) {
			// Figure the display format for the match
			$echo = (getCheckbox ('lettersonly')  ||  $row['whole'] != 'Y') ? $oneword : $entry;
			$ascii = preg_match ("/^[-a-z' 0-9]*$/i", $echo);
			switch ($_GET['wordcase']) {
				case 'U':
				if ($ascii) {
					$echo = strtoupper ($echo); // works fine for ASCII-128 stuff
				} else {
					$echo = "<span style='text-transform: uppercase'>$echo</span>"; // better for accented characters than strtoupper
				}
				break;

				case 'L':
				if ($ascii) {
					$echo = strtolower ($echo);
				} else {
					$echo = "<span style='text-transform: lowercase'>$echo</span>";
				}
				break;
			}

			if ($oneword == $previous) {
				$same = true;
				if (!$oneword) {
					break;
				}
				// no change to sort key
			} else {
				if ($previous <> '') {
				  $output .= $tde . font ($details, '+details') . ($tabular ? '</tr>' : '<br>');
					$found [++$counter] = array ('text' => $entry, 'corpus' => $corpus, 'sort' => $sortkey, 'output' => $output);
					if (!$oneword) {
						break;
					}
				}
				$output = $tabular ? '<tr>' : '';
				unset ($rowMore);
				$rowMore ['L'] = strlen ($oneword);
				$sorted = stringSort ($oneword);
				$baseURL = "http://alfwords.com/search$type.php";
				if (getCheckbox ('letterauc')) {
					$sorted = strtoupper ($sorted);
				}
				$sortedOutput = '';
				if (getCheckbox ('letteralpha')) {
					$sortedecho = font ($sorted, '-lettera');
					if (getCheckbox ('letteralinks')) {
						$sortedOutput .= "$td<A HREF='$baseURL?pattern=$sorted&anyorder=on&$security$moreParms' target='_blank'>$sortedecho</A>$tde";
					} else {
						$sortedOutput .= "$td$sortedecho$tde";
					}
					$rowMore ['A'] = $sorted;
				}
				if (getCheckbox ('letterabank')) {
					$sorted = noDupes ($sorted);
					$sortedecho = font ($sorted, '-lettera');
					if (getCheckbox ('letteralinks')) {
						$sortedOutput .= "$td<A HREF='$baseURL?pattern=$sorted&anyorder=on&repeat=on&$security$moreParms' target='_blank'>$sortedecho</A>$tde";
					} else {
						$sortedOutput .= "$td$sortedecho$tde";
					}
					$rowMore ['B'] = $sorted;
				}
				$output .= font ($sortedOutput, '+lettera');
				$previous = $oneword;
				$same = false;
			}
			$output .= $same ? ' ' : $td;
			$echo = font ($echo, 'word');
			if ($row['whole'] == 'Y') {
				// If this is the whole entry, set up a link
				if ($link == '*') {
					$output .= $corpusObjects[$corpus]->answerLink ($entry, $echo) . ' ';
				} else if ($link) {
					$entryLink = str_replace ('@', urlencode ($entry), $link);
					$output .= "<A HREF='$entryLink' target='_blank'>$echo</A>  ";
				} else {
					$output .= "$echo  ";
				}
			} else {
				// Else if part of an entry name, set up a link to our page to list phrases
				if (!$same) {
					if ($link) {
						$entryLink = str_replace ('@', urlencode ($oneword), $link);
						$output .= "<A HREF='$entryLink' target='_blank'>$echo</A>  ";
					} else {
						$output .= "$echo  ";
					}
				}
				if ($corpusObjects[$corpus]->phrases()) {
					$output .= " <A target='_blank'
						HREF='phrases$type.php?base=$oneword&corpus=$corpus&type=$type&level=$level&link=$linkencoded'><i>phrases</i></A> ";
				}
			}

			if (!$same) {
				$details = '';
				foreach ($consObjects as $rowNumber => $thisConsObject) {
					if ($thisConsObject->detailsEnabled()) {
						$value = $row["cv$rowNumber"];
						if ($tabular  &&  is_numeric ($value)) {
							$tdx = "<td style='text-align:right'>";
						} else {
							$tdx = $td;
						}
						$value = font ($value, '-details');
						$details .= "$tdx$value$tde";
					}
				}
			}

			$prevword = strtolower ($oneword);
		}

		if (microtime (true) > $timeout) {
			$timedOut = 'T';
		}
		if ($counter == $pagelimit) {
			$timedOut = 'C';
		}
		$row += $rowMore;
		$sortkey = sorter ($row, 1) . ' ' . sorter ($row, 2);
	} // end while

  // We have everything, now sort it
	usort($found, function($a, $b){
		return strcmp ($a['sort'], $b['sort']);
		});

	// Dump the sorted entries
	$dumpCounter = 0;
	foreach ($found as $entry) {
		if ($tabular  &&  $dumpCounter++ % 30 == 0) {
			echo $header;
		}
		echo $entry ['output'];
	}
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

function sorter ($row, $level) {
	$column = "sort$level";
	if (isset($_GET[$column])) {
		$value = $row[$_GET[$column]];
		if (is_numeric ($value)) { // Put a letter in front of numbers so they sort properly
			$value = chr (strlen ($value) + ord ('a')) . $value;
		}
		if (getCheckbox ("desc$level")) {
			$newValue = '';
			for ($pos = 0; $pos < strlen ($value); $pos++) {
				$newValue .= chr (158 - ord (substr ($value, $pos, 1))); // 158 = asc(' ') + highest ASCII value
			}
			$value = $newValue;
		}
		return $value;
	} else {
		return '';
	}
}

function font ($text, $type) {
	if ($text == '') {
		return $text;
	}
	$tabular = getCheckbox ('usetable');
	switch (substr ($type, 0, 1)) {
		case '-':
		if (!$tabular) {
			return $text; // will do font for the whole block
		}
		$type = substr ($type, 1);
		break;

		case '+':
		if ($tabular) {
			return $text; // will do font for each cell
		}
		$type = substr ($type, 1);
		break;
	}
	if (getCheckbox ("font$type")) {
		if (($face = $_GET ['font']) == 'N'  ||  $face == '') {
			return $text;
		} else {
			if ($face == 'C') {
				$family = "\"{$_GET['fontname']}\"";
			} else {
				$family = array ('M' => 'monospace', 'S' => 'serif', 'W' => 'sans-serif')[$face];
			}
			return "<span style = 'font-family:$family'>$text</span>";
		}
	} else {
		return $text;
	}
}
?>
