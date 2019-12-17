<?php
// Display results (in $result)
function showResults ($result, $consObjects, $corpusObjects) {
	$type = $_GET['type'];
	$level = $_GET['level'];
	$urlkey = urlencode ($_GET['sessionkey']);
	if (!$pagelimit = ($_GET['pagelen'] ?? 0)) {
		$pagelimit = 1E9;
	}
	$moreParms = getParmsForLinks ();
	$counter = 0;
	$timedOut = false;
	$timeout = (($level == 3) ? 110 : 30) + $GLOBALS['time']['top.int'];
	foreach ($corpusObjects as $corpus => $corpusObject) {
		if ($corpusObject->phrases()) {
			$phraseCorpora .= ",$corpus";
		}
	}
	$phraseCorpora = substr ($phraseCorpora, 1);

	if (get_class ($result) <> "PDOStatement") {
		echo "<span class='error'>$result</span>"; // Error message
		return;
	}

	$link = getMainLink ();
	$linkencoded = urlencode ($link);

		// Loop through results from database
	$tabular = getCheckbox ('usetable');
	$td = $tabular ? '<td>' : '';
	$tde = $tabular ? '</td>' : ' ';
	if ($tabular) {
		echo '<table>';
		$header = buildTableHeader ($consObjects);
	}

	$formatInfo = getFormatInfo ($consObjects);

  // process results one at a time
  $timedOut = false;
	while (true) {
		if (!$timedOut  &&  $row = $result->fetch(PDO::FETCH_ASSOC)) {
			$oneword = $row['word'];
			$corpus = $row['corpus'];
			$entry = $row['entry'];
			$entry_id = $row['entry_id'];
			$matched = true;
			// Check any constraints that require client-side work
			unset ($consMatch);
			foreach ($consObjects as $num => $thisConsObject) {
				$thisMatch = $thisConsObject->localFilterArray ($row);
				if ($thisConsObject->postFormat()) {
					if ($thisMatch) {
						$consMatch[$num] = true;
					}
				} else if (!$thisMatch) {
					$matched = false;
					break;
				}
			}
		} else {
			$oneword = '';
			$matched = true;
		}

		if ($matched) {
			$echo = wordDisplayFormat ($row);

			if ($oneword == $previous) {
				$same = true;
				if (!$oneword) {
					break;
				}
				// no change to sort key
			} else {
				if ($previous <> '') {
				  $output .= $tde . font ($details, '+details');
					foreach ($formatInfo as $oneFormat) {
						if (isMatch ($info, $oneFormat)) {
							if ($tabular) {
								$output = applyFormatHTML ($output, 'td', $oneFormat);
							} else {
								$output = applyFormat ($output, $oneFormat);
							}
						}
					}
					$found [++$counter] = array ('text' => $entry, 'corpus' => $corpus, 'sort' => $sortkey, 'output' => $output);
					if (!$oneword) {
						break;
					}
				}

				// Start on the new one
				unset ($rowMore);
				$rowMore ['L'] = strlen ($oneword);
				$output = sortedOutput ($oneword, $rowMore, $td, $tde);
				$previous = $oneword;
				$same = false;
				unset ($info);
				$info['V'] = $consMatch;
				}
			foreach (str_split ($row['flags']) as $flag) {
				$info ['F'][$flag] = true;
			}
			$output .= $same ? ' ' : $td;
			$echo = font ($echo, 'word');
			$info ['C'][$corpus] = true;
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
				if ($corpusObjects[$corpus]->phrases()  &&  $lastPhraseWord != $oneword) {
					$output .= " <A target='_blank'	HREF='phrases$type.php?base=$oneword&corpus=$phraseCorpora&type=$type&level=$level&link=$linkencoded'><i>phrases</i></A> ";
					$lastPhraseWord = $oneword;
				}
			}

			if (!$same) {
				$details = '';
				foreach ($consObjects as $rowNumber => $thisConsObject) {
					if ($thisConsObject->detailsEnabled()) {
						$value = $row["cv$rowNumber"];
						if ($value == '0'  &&  $thisConsObject->postFormat()) {
							$value = ''; // displaying even on mismatch, so don't show dummy zeroes
						}
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
			$row += $rowMore;
			$sortkey = sorter ($row, 1) . ' ' . sorter ($row, 2);
		} // $matched

		if (microtime (true) > $timeout) {
			$timedOut = 'T';
		}
		if ($counter == $pagelimit) {
			$timedOut = 'C';
		}
	} // end while

  // We have everything, now sort it
	usort($found, function($a, $b){
		return strcmp ($a['sort'], $b['sort']);
		});

	// Dump the sorted entries
	$dumpCounter = 0;
	$rowMulti = $_GET['rowmulti'];
	foreach ($found as $entry) {
		dumpOne ($entry, $header, $dumpCounter++, $tabular, $rowMulti);
	}
	if ($rowMulti  &&  $tabular  &&  $dumpCounter % 3 > 0) {
		echo '</tr>';
	}
	echo $tabular ? '</table>' : '';

	if ($counter > 0  &&  $level > 1) {
  	$ret ['save'] = saveResults ($found);
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

function getMainLink () {
	switch ($_GET['linkoption']) {
	case 'suppress':
		return '';
	case 'source':
		return '*';
	case 'google':
		return 'https://www.google.com/search?q=@';
	case 'bing':
		return 'https://www.bing.com/search?q=@';
	case 'yahoo':
		return 'https://search.yahoo.com/search?p=@';
	case 'ngramviewer':
		return 'https://books.google.com/ngrams/graph?content=@&year_start=1800&year_end=2000';
	case 'imdb':
		return 'https://www.imdb.com/find?q=@&s=all';
	case 'custom':
		return $_GET['customlink'];
	default:
		throw new Exception ("Invalid link option: {$_GET['linkoption']}");
	}
}
function getParmsForLinks () {
	$moreParms = "sessionkey=$urlkey&level=$level&type=$type&version={$_GET['version']}";
	foreach ($_GET as $key => $parm) {
		if (substr ($key, 0, 6) == 'corpus'  ||  preg_match ('/^c([0-9]+)flag(.)(.*)$/', $key)  ||
				$key == 'phrase' || $key == 'single' || $key == 'whole') {
			if ($parm == 'on') {
				$moreParms .= "&$key=on";
			}
		} elseif ($key == 'linkoption') {
			$moreParms .= "&$key=$parm";
		}
	}
	return $moreParms;
}

function buildTableHeader ($consObjects) {
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
	return $header;
}

function wordDisplayFormat ($row) {
	// Figure the display format for the match
	$echo = (getCheckbox ('lettersonly')  ||  $row['whole'] != 'Y') ? $row['word'] : $row['entry'];
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
	return $echo;
}

function sortedOutput ($oneword, &$rowMore, $td, $tde) {
	$sorted = stringSort ($oneword);
	$baseURL = "http://alfwords.com/search{$_GET['type']}.php";
	$moreParms = getParmsForLinks ();
	if (getCheckbox ('letterauc')) {
		$sorted = strtoupper ($sorted);
	}
	$sortedOutput = '';
	if (getCheckbox ('letteralpha')) {
		$sortedecho = font ($sorted, '-lettera');
		if (getCheckbox ('letteralinks')) {
			$sortedOutput .= "$td<A HREF='$baseURL?pattern=$sorted&anyorder=on&$moreParms' target='_blank'>$sortedecho</A>$tde";
		} else {
			$sortedOutput .= "$td$sortedecho$tde";
		}
		$rowMore ['A'] = $sorted;
	}
	if (getCheckbox ('letterabank')) {
		$sorted = noDupes ($sorted);
		$sortedecho = font ($sorted, '-lettera');
		if (getCheckbox ('letteralinks')) {
			$sortedOutput .= "$td<A HREF='$baseURL?pattern=$sorted&anyorder=on&repeat=on&$moreParms' target='_blank'>$sortedecho</A>$tde";
		} else {
			$sortedOutput .= "$td$sortedecho$tde";
		}
		$rowMore ['B'] = $sorted;
	}
	return font ($sortedOutput, '+lettera');
}

function dumpOne ($entry, $header, $dumpCounter, $tabular, $rowMulti) {
	if ($dumpCounter % 30 == 0  &&  $tabular  &&  !$rowMulti) {
		echo $header;
	}
	if ($rowMulti) {
		if ($tabular) {
			$before = ($dumpCounter % 3 == 0) ? '<tr>' : '';
			$after = ($dumpCounter % 3 == 2) ? '</tr>' : '';
		} else {
			$before = "";
			$after = "<span style='color:green'> | </span>";
		}
	} else {
		if ($tabular) {
			$before = "<tr>";
			$after = "</tr>";
		} else {
			$before = "";
			$after = "<br>";
		}
	}
	echo "$before{$entry ['output']}$after\n";
}

function saveResults ($found) {
	try {
		$connw = openConnection (true);
		if ($row = (SQLQuery ($connw, "SELECT id FROM session WHERE session_key = '{$_GET['sessionkey']}'")->fetch(PDO::FETCH_ASSOC))) {
			$sessionID = $row['id'];
		} else {
			THROW new Exception ("No results getting session ID for {$_GET['sessionkey']}");
		}

		// Delete previous list unless we're continuing
		if (!isset ($_GET['from'])) {
			$connw -> exec ("DELETE FROM session_words WHERE session_id = $sessionID");
		}
		foreach ($found as $entry) {
			$stmt = $connw->prepare('INSERT session_words (session_id, entry, corpus_id) VALUES (?, ?, ?)');
			$stmt->execute(array ($sessionID, $entry ['text'], $entry ['corpus']));
		}
	} catch (Exception $e) {
		errorMessage ($e->getMessage());
	} finally {
		unset ($connw);
	}
	return $sessionID;
}

function getFormatInfo ($consObjects) {
	foreach ($_GET as $key => $parm) {
		if (substr ($key, 0, 8) == 'rdispdiv'  &&  $parm <> 'P') {
			$ret [] = array ('key' => $subkey = substr ($key, 8), 'parm' => $parm, 'more' => $_GET["dispdiv{$subkey}x"] ?? '',
					'not' => getCheckbox ("dispdiv{$subkey}not"));
		}
	}
	return $ret;
}

function isMatch ($info, $oneFormat) {
	if (!preg_match ("/^([a-z]+)([0-9]*)_*([a-z]*)([0-9]*)$/i", $oneFormat ['key'], $matches)) {
		throw New Exception ("Bad format format: {$oneFormat['key']}");
	}
	switch ($matches[1]) {
		case 'v': // regular constraint
		$ret = isset ($info ['V'][$matches[2]]);
		break;

		case 'cv':
		$ret = isset ($info ['V']["{$matches[2]}_{$matches[4]}"]);
		break;

		case 'cf': // Corpus flag
		$ret = isset ($info ['F'][$matches[3]]);
		break;

		case 'cp': // Corpus
		$ret = isset ($info ['C'][$matches[4]]);
	}
	return $ret  XOR  $oneFormat ['not'];
}

function applyFormat ($output, $oneFormat) {
	$more = $oneFormat['more'];
	switch ($parm = $oneFormat['parm']) {
		case 'U':
		case 'B':
		case 'I':
		$output = "<$parm>$output</$parm>";
		break;

		case 'BB':
		$output = "<span class='extrabold'>$output</span>";
		break;

		case 'S';
		$output = "<strike>$output</strike>";
		break;

		case 'L';
		$output = "<span style='font-size:larger'>$output</span>";
		break;

    case 'CF':
		// Apply color to the innerHTML of each anchor
		$output = applyFormatHTML ($output, 'a', $oneFormat);
		if (!preg_match ('/^<A[^>]*>[^<]*</A>$/i', $output)) { // Is there anything beyond a single anchor?
			$output = "<span style='color:$more'>$output</span>";
		}
		break;

		case 'CB':
		$output = "<span style='background-color:$more'>$output</span>";
		break;

		default:
			throw new Exception ("Bad format parameter: $parm");
	}
  return $output;
}

// Apply formatting to innerHTML of <entity>stuff</entity>
function applyFormatHTML ($string, $entity, $oneFormat) {
	setTemp ('oneFormat', $oneFormat);
	return preg_replace_callback ('/(<' . $entity . '[^>]*>)(.*?)(<\/' . $entity . '>)/i',
		function ($matches) {
			return $matches[1] . applyFormat ($matches[2], getTemp ('oneFormat')) . $matches[3];
		},
		$string);
	}
?>
