<?php
// Create SQL to implement $pattern
// $consObjects is an array of constraint objects
// returns complete SQL string
// also echoes a description of the query to the web page
function parseQuery ($pattern, &$consObjects, &$corpusObjects) {
	$pattern = expandSpecial (strtolower ($pattern)); // Convert # @ & to [groups]
	$required = '';

	// Prepare for anagram search
	$prewhere = '/* premain */';
	$sql = "SELECT PW.text AS word, Min(entry.name) AS entry, entry.id as entry_id," .
			   " word_entry.whole AS whole, entry.corpus_id AS corpus FROM words PW" .
			   " INNER JOIN word_entry ON word_entry.word_id = PW.id " .
			   " INNER JOIN entry ON entry.id = word_entry.entry_id " .
			   " $prewhere WHERE 1=1 "; // End with dummy condition so we can keep appending AHD clauses

	regexBounds (patternToRegex ($pattern, 'P'), $minlen, $maxlen);

	// Set up additional constraints
	$count = $_GET['count'];
	for ($num = 2; $num <= $count; $num++) {
		if (!isset($_GET["query$num"])) { // This can happen if there are three constraints and you delete the second one
				$skip++;
				continue;
		}
		$classname = "cons" . $_GET["radio$num"]; // subclass name based on user choice of constraint type
		$consObjects[$num] = new $classname($_GET["query$num"], $num, getCheckbox ("not$num"));
		$required = $required . $consObjects[$num]->required(); // Do this now because needed below
		// Not done for corpus ones, but they shouldn't ever need it.
	} // end for

	// Identify pattern when we're doing an Any Order search.
	if ($anyorder = getCheckbox ('anyorder')) {
		echo '  Any order ';
		$sql = $sql . doAnyOrder ($pattern, $maxlen, $required);
		$position = array ();
	} else { // Not anyorder
		if (strpos ($pattern, ']') > 0) {
			// Unlike Sybase, MSSQL doesn't support groups in a simple LIKE, but it does support (limited) regular expressions
			// with the special RLIKE operator
			$sql = $sql . " AND PW.text RLIKE '" . patternToRegex ($pattern, 'S') . "'";
		} else {
			$sql = $sql . " AND PW.text LIKE '" . patternToSQL ($pattern) . "'";
			if (preg_match ('/^[a-z]*/', $pattern, $matches)) {
				$sql = $sql . " AND PW.text BETWEEN '$matches[0]' AND '$matches[0]zzz'";
			}
		}
		$position = patternPosition ($pattern);
		// if there's not a strong leading subset, the rest of the letters may help
		if (!preg_match ("/^[a-z][a-z[/", $pattern)) {
			$sql = $sql . doAnyOrder (groupToWildcard ($pattern), $maxlen, $required);
		}
	}
	foreach (corpusInfo ('', 'L') as $thisCorpus) {
		$corpusObjects [$thisCorpus] = corpus::factory ($thisCorpus);
		$corpusObjects [$thisCorpus]->builder ($consObjects);
		if ($corpusObjects [$thisCorpus] -> requireWhole()) {
			$_GET['whole']='on';
		}
	}

  // WHERE clause for corpus and flags
	$sql = insertSql ($sql, corpusInfo ('entry', 'W'));

	$counter = 0;
	// Handle additional constraints
	foreach ($consObjects as $thisConsObject) {
		if (++$counter % 2 == 0) { // Display constraint info, two per line
				echo "<BR>";
		}
		if ($explain = $thisConsObject->explain ()) {
			echo " </span>and<span class='specs'> $explain ";
		}
		$sql = insertSql ($sql, $thisConsObject->parse ());
		$thisConsObject->setlengths ($minlen, $maxlen);
		$position += $thisConsObject->position();
		// keep object around for post-filtering and for rebuilding form
	} // end foreach

	$sql = $sql . doWordTypes ();
	$sql = $sql . doLength ('PW', $minlen, $maxlen, true);
	foreach (array (doPairs ($required . patternRequired ($pattern)),
					doPosition ($position, $minlen, $maxlen)) as $join) {
		if ($join > '') {
			$sql = str_replace ($prewhere, "$join $prewhere", $sql);
		}
	}
	$fourlist = $anyorder ? '' : $pattern;
	foreach ($consObjects as $thisConsObject) {
		$fourlist .= '|' . $thisConsObject->fourPattern();
	}

	if ($fourjoin = doFour ($fourlist, '')) {
		$sql = str_replace ($prewhere, "$fourjoin $prewhere", $sql);
	}

	$by = "PW.text, word_entry.whole DESC, entry.corpus_id";
	$orderby = " GROUP BY " . str_replace('DESC', '', $by) . " ORDER BY $by";
	return $sql . $orderby;
}

// Processing of "any order" searches
function doAnyOrder ($pattern, &$maxlen, $required) {
	$more = '';
	$wild = 0;
	$patternStripped = $pattern;
	// See what letters we have after removing wild cards
	if (strpos ($pattern, '?') !== false) {
		$patternStripped = str_replace ('?', '', $patternStripped);
		$wild = 1;
	}
	if (strpos ($pattern, '*') !== false) {
		$patternStripped = str_replace ('*', '', $patternStripped);
		$wild = 2;
	}
	$sortpat = stringSort ($patternStripped);
	if ($repeat = getCheckbox ('repeat')) {
		echo '  with repeats allowed';
		$maxlen = 99;
	}
	// If there is a range, create an array of search strings (e.g., any[one] --> {anoy, anny, aeny})
	if (($rbrack = strpos ($patternStripped, ']')) > 0) {
		$lbrack = strpos ($patternStripped, '[');
		$left = substr ($patternStripped, 0, $lbrack);
		$right = substr ($patternStripped, $rbrack + 1);
		$choices = '/' . substr ($patternStripped, $lbrack, $rbrack - $lbrack + 1) . '/' ; // /[abf-m]/
		unset ($sortpat); // So we can rebuild it as an array.
		$opt = 0;
		for ($let = 1; $let <= 26; $let++) {
			$char = chr (ord ('a') + $let - 1); // Generate letters from a to z.
			if (preg_match ($choices, $char)) {
				$sortpat [$opt] = stringSort ($left . $char . $right);
				$opt = $opt + 1;
			}
		}

		// Establish common pattern for efficiency of initial scan
		$more = $more . " AND " . bankSQL (noDupes (stringSort ($left . $right . $required)), true, $wild > 0);
	} else {
 		$sortpat = array ($sortpat); // No range, but make it an array for consistency
	}
	$more = $more . " AND (";

	// Loop through possible patterns
	for ($opt = 0; $opt < sizeof ($sortpat); $opt++) {
		$ndsortpat = noDupes ($sortpat[$opt]); // Remove duplicates so we can match...
		$bankpat = noDupes (stringSort ($ndsortpat . $required)); // against the bank column
		if ($wild > 0) {
			$thissql = bankSQL ($bankpat, sizeof ($sortpat) == 1, $wild == 2  ||  preg_match ('/\?.*\?/', $pattern));
		} else {
			$thissql = "PW.bank = '" . patternToSQL ($bankpat) . "'";
		}
		// Repeated letters are not reflected in bank search, so do that here.
		for ($num = 0; $num < strlen ($ndsortpat); $num++) {
			$char = substr ($ndsortpat, $num, 1);
			$instances = count (explode ($char, $sortpat[$opt])) - 1;
			if ($instances > 1) {
				$repeated = str_repeat ($char . '%', $instances);
				$thissql = $thissql . " AND PW.text LIKE '%$repeated'"; // Continuing, anny --> LIKE "%n%n%"
			}
		}
		if ($opt > 0) {
			$more = $more . ' OR ';
		}
		$more = $more . " ($thissql) ";
	  }
	$more = $more . ')';

  if (isset ($_GET['from'])) {
		// Handle continuation when original query timed out
		$more = $more . " AND PW.text > '{$_GET['from']}'";
	}

	return $more;
}

function doLength ($table, $minlen, $maxlen, $echo) {
	// If the length range inferred from the specs is narrower than those specified by the user,
	// adjust accordingly.
	// Length range, if provided
	$usermin = $_GET['minlen'];
	$usermax = $_GET['maxlen'];
	if ($usermax < 2) {
		$usermax = 99;
	}
	if ($echo) {
			if ($usermin > 0) {
			echo "<BR>Length $usermin";
			if ($usermax >= 99) {
					echo " and up ";
			} else if ($usermax > $usermin) {
					echo " to $usermax ";
			}
			} else if ($usermax < 99) {
			echo "<BR>Length up to $usermax ";
			}
	}
	if ($minlen > $usermin) {
		$usermin = $minlen;
	}
	if ($maxlen < $usermax) {
		$usermax = $maxlen;
	}

	$more = '';
	if ($usermin > 0) {
		$more = $more . " AND $table.length >= $usermin";
	}
	if ($usermax > 0  &&  $usermax < 99) {
		$more = $more . " AND $table.length <= $usermax";
	}
	return $more;
}

// Return logic for required pairs, where a pair is required if the sum of their values is >3: BCDGMP=1  FHKVWY=2  JQXZ=3
function doPairs ($letters) {
	$letters = noDupes ($letters);
	$more = "";
	foreach (str_split ($letters) as $first) {
		foreach (str_split ($letters) as $second) {
			if ($second > $first) {
				$bigram = $first . $second;
				$weights = preg_replace (array ('/[bcdgmp]/', '/[fhkvwy]/', '/[jqxz]/'), array (1, 2, 3), $bigram);
				if (substr ($weights, 0, 1) + substr ($weights, 1, 1) > 3) {
					$subtable = 'WP' . strtoupper ($bigram);
					$more = $more . " INNER JOIN word_pair $subtable ON $subtable.word_id = word_entry.word_id " .
							" AND $subtable.pair = '$bigram' ";
				}
			}
		}
	}
	return $more;
}

// Return logic for required rare letters in specific positions
function doPosition ($position, $minlen, $maxlen) {
	// $position is array [position] = letter
	$more = "";
	foreach ($position as $here => $letter) {
		if (strpos ('jqxz', $letter) !== false) {
			$subtable = 'WPOS' . $here;
			$more = $more . " INNER JOIN word_position $subtable ON $subtable.word_id = word_entry.word_id " .
				" AND $subtable.position = $here AND $subtable.letter = '$letter' " . doLength ($subtable, $minlen, $maxlen, false);
		}
	}
	return $more;
}

// Filter on a tetragram or trigram, if possible
function doFour ($pattern, $suffix) {
	$pattern = groupToWildcard ($pattern);
	$ret = '';
	$bestScore = 0;
	for ($first = 0; $first + 2 < strlen ($pattern); ++$first) {
		for ($length = 3; $length < 5; ++$length) {
			if (preg_match ('/^[a-z]*$/', $substring = substr ($pattern, $first, $length))) {
				$newScore = 1;
				foreach (str_split (strtoupper ($substring)) as $char) {
					$newScore *= (strpos ("..ETAOINSRHDLUCMFYWGPBVKXQJZ", $char));
				}
				if (strpos (strtoupper ($substring), 'QU')) {
					$newscore /= 10; // Don't overvalue this based on high scores of both letters.
				} else if (preg_match ('/[^aeious][^aeiohlrs]/i', $substring)) {
					$newScore *= 2;
				}
				if ($newScore > $bestScore) {
					$bestScore = $newScore;
					if ($length == 4) {
						$verb = '=';
						$target = "'$substring'";
					} else {
						$verb = 'BETWEEN';
						$target = "'$substring' AND '$substring~'";
					}
					$ret = "INNER JOIN word_four WF$suffix ON WF$suffix.word_id = word_entry.word_id AND WF$suffix.quartet $verb $target";
				}
			}
		}
	}
	return $ret;
}

// Handle filtering by whole words, phrases, etc.
function doWordTypes() {
	$more = '';
	if (getCheckbox ('whole')) {
		$more = $more . " AND word_entry.whole = 'Y'";
		echo "<BR>Whole entries only";
	}
	$single = getCheckbox ("single");
	$phrase = getCheckbox ("phrase");
	if ($single && $phrase) {
		// all entries
	} else {
		if ($single) {
			$more = $more . " AND word_entry.solid = 'Y'";
			echo "<BR>Single words only";
		} else {
			$more = $more . " AND word_entry.solid = 'N'";
			echo "<BR>Phrases only";
		}
	}
	return $more;
}

// Create SQL to search on the bank
function bankSQL ($pattern, $main, $wild) {
	// $main is true if we expect this to be the main search and we should try to be efficient
	// $wild is true if we need to account for multiple unknown letters
	$like = '*' . implode ('*', str_split ($pattern)) . '*';
	if (substr ($like, 0, 2) == '*a') {
		$like = substr ($like, 1);
	}
	for ($here = strlen ($like); $here > 0; $here--) {
		if (substr ($like, $here, 1) == '*'  &&  ord (substr ($like, $here + 1)) == ord (substr ($like, $here - 1)) + 1) {
			$like = substr ($like, 0, $here) . substr ($like, $here + 1);
		}
	}
	if (!$main) {
		// no point in complexly trying to be efficient
		$more = "PW.bank LIKE '" . patternToSQL ($like) . "'";
	} else if (substr ($like, 0, 2) == 'a*') {
		// stop looking if we get past the range of possible banks
		$more = "PW.bank LIKE '" . patternToSQL ($like) . "' AND PW.bank < 'a" . substr ($like, 2, 1) . "zz'";
	} else if (substr ($like, 0, 2) == '*b') {
		// with or without A
		$more = "(PW.bank LIKE '" . patternToSQL (substr ($like, 1)) . "' OR PW.bank LIKE '" . patternToSQL ('a' . substr ($like, 1)) . "')";
	} else if ($wild) {
		// as with a*, stop when we can
		$more = "PW.bank LIKE '" . patternToSQL ($like) . "' AND PW.bank < '" . explode ('*', $like)[1] . "zz'";
	} else {
		// only one wildcard, so enumerate the options
		$basic = patternToSQL (substr ($like, 1));
		$more = "(PW.bank LIKE '$basic'";
		for ($ord = ord ('a'); $ord < ord (substr ($like, 1)); $ord++) {
			$more = "$more OR PW.bank LIKE '" . chr ($ord) . "$basic'";
		}
		$more = $more . ')';
	}
	return $more;
}

// Create SQL for filtering by corpus
function corpusInfo ($table, $option) {
	// $option is either L for a list of corpora ($table is ignored)
	// or W for a WHERE clause relating to $table.
	$moreSQL = '';
	$ret ['pre'] = '';
	$alias = false;
	foreach ($_GET as $key => $parm) {
    if (substr ($key, 0, 6) == 'corpus') {
	    if ($parm == 'on') {
        $corpus = substr ($key, 6);
        $corpusList [$corpus] = $corpus;
				if (!isset($flags [$corpus])) {
					$flags [$corpus] = '';
					$nonflags [$corpus] = '';
				}
				if ($option == 'W') {
					if ($join = $GLOBALS['corpusObjects'][$corpus] -> getSpecialEntry ($newTable)) {
						$corpusFilter[$corpus] = "$newTable.corpus_id IS NULL";
						$corpusTable [$corpus] = $newTable;
						$alias = true;
						if ($table == 'entry') {
							$ret ['pre'] = $ret ['pre'] . $join; // add outer join to 'like' corpus for main SELECT
						}
					} else {
						$corpusFilter[$corpus] = "$table.corpus_id <> $corpus";
						$corpusTable [$corpus] = $table;
					}
				}
      }
    } else if (preg_match ('/^c([0-9]+)flag(.)(.*)$/', $key, $matches)  &&  $option == 'W') {
			$corpus = $matches[1];
			if ($matches [2] == '@') { // fake item set in code
				$moreSQL = $moreSQL . $GLOBALS['corpusObjects'][$corpus] -> moreSQL ($corpusTable [$corpus], $matches[3], $parm);
			} else {
				$flags[$corpus] = ($flags[$corpus] ?? '') . $matches [2];
			}
		}
  }

	if ($option == 'L') {
		return $corpusList;
	}

	$list = implode (',', $corpusList);
	if (count ($corpusList) == 1) {
		$clause = " = $list";
	} else {
		$clause = " IN ($list)";
	}

	preg_match_all ('/c([0-9]+)flag(.)/', $_GET['morecbx'], $matches);
	foreach ($matches[1] as $counter => $thisCorpus) {
		if (strpos ($flags[$thisCorpus], $matches[2][$counter]) === false) {
			$nonflags [$thisCorpus] = $nonflags [$thisCorpus] . $matches[2][$counter];
		}
	}

	$flagClause = ''; // for now
	foreach ($corpusList as $thisCorpus) {
		if ($nonflags[$thisCorpus]) {
			$rlike = "{$corpusTable[$corpus]}.flags NOT RLIKE '[{$nonflags[$thisCorpus]}]' ";
			if (count ($corpusList) == 1  &&  !$alias) {
				$flagClause = " AND $rlike";
			} else {
				$flagClause = $flagClause . " AND ({$corpusFilter[$corpus]} OR $rlike) ";
			}
		}
	}

	$ret ['where'] = " AND $table.corpus_id $clause $flagClause $moreSQL";
	return $ret;
}

// Convert special characters to letter groups
function expandSpecial ($string) {
	return str_replace (array ('#', '@', '&'), array ('[aeiou]', '[^aeiou]', '[cdilmvx]'), $string);
}

// Replace groups and repeat counts with wildcards when we only care about fixed letters
function groupToWildcard ($pattern) {
	$debug = 0;
	while (($left = strpos ($pattern, '[')) !== false) {
		$right = strpos ($pattern, ']');
		$pattern = substr ($pattern, 0, $left) . '?' . substr ($pattern, $right + 1);
		if (++$debug > 10) {break;}
	}

	// Replace repeat counts with wildcards
	$pattern = preg_replace_callback ('/{[0-9,]+}/',
		function ($matches) {
			$match = $matches[0];
			if (strpos ($match, ',') !== false) {
				return '*'; // unknown number of repeats
			} else {
				return str_repeat ('?', substr ($match, 1, strlen ($match) - 2) - 1);
			}
		},
		$pattern);

  return $pattern;
}

// Return input string (assumed to be sorted) with duplicates removed
function noDupes ($pat) {
	$pat = stringSort ($pat);
	for ($here = strlen ($pat) - 2; $here >= 0; $here--) {
		if (substr ($pat, $here, 1) == substr ($pat, $here + 1, 1)) {
			$pat = substr ($pat, 0, $here) . substr ($pat, $here + 1);
		}
	}
	return $pat;
}

// Convert a pattern, as entered by the user, into a regular expression suitable for
// the selected language (P for PHP or S for SQL).  Can also pass U for an unarchored pattern.
function patternToRegex ($pat, $lang) {
	$letterpat = '.';
	$regex = str_replace ('?', $letterpat, str_replace ('*', $letterpat . '*', $pat));
	if (strpos ($lang, 'U') === false) {
		$regex = '^' . $regex . '$';
	}
	if (strpos ($lang, 'P') !== false) {
		$regex = "/" . $regex . "/i";
	}
	return $regex;
}

// Find what letters in a pattern (could be a regex) are required to be in a specific place
function patternPosition ($pattern) {
	// Some special things for regular expressions
	if (substr ($pattern, 0, 1) == '/') {
		$pattern = str_replace ('?', '*', $pattern); // distinguish from the single-character meaning of ? for non-regexes
		// If there is alternation or negation or no anchor to beginning, we're going to assume nothing is fixed

		if (strpos ($pattern, '|') !== false  ||  strpos ($pattern, '!') !== false  ||  substr ($pattern, 1, 1) != '^') {
			return array ();
		}
		$pattern = preg_replace ('/\\[0-9]/', '*', $pattern); // Because we don't know how long the backreference is
	}

	$pattern = groupToWildcard ($pattern);

	// Ignore everything after an asterisk or plus or (if it's a regex) question mark
	$pattern = (explode ('*', $pattern))[0];
	$pattern = (explode ('+', $pattern))[0];

	// Eliminate other metacharacters
	$pattern = str_replace (str_split ('()/^'), '', $pattern);

	// Finally create the return array
	$ret = array ();
	foreach (str_split ($pattern) as $position => $char) {
		if (preg_match ('/[a-z]/', $char)) {
			$ret [$position + 1] = $char;
		}
	}
	return $ret;
}

// Find what letters in a pattern (could be a regex) are required
function patternRequired ($pattern) {
	// If there is alternation or negation, we're going to assume nothing is fixed
	if (strpos ($pattern, '|') !== false || strpos ($pattern, '!') !== false) {
		return '';
	}

	// Remove groups, leaving an underscore to simplify subsequent processing
	while (($left = strpos ($pattern, '[')) !== false) {
		$right = strpos ($pattern, ']');
		$pattern = substr ($pattern, 0, $left) . '_' . substr ($pattern, $right + 1);
	}
	// In regexes, skip characters with repeat counts which could include zero
	if (substr ($pattern, 0, 1) == '/') {
		$pattern = preg_replace ('/.(\{0|\{,|\?|\*)/', '', $pattern);
	}
	preg_match_all ("/([a-z])/", $pattern, $matches); // find just the letters
	return implode ('', $matches[0]); // we'll remove dupes when we have the whole collection
}

// Convert a simple pattern, as entered by the user, into a SQL pattern
function patternToSQL ($pat) {
	return str_replace ('?', '_', str_replace ('*', '%', $pat));
}

// Find the minimum and maximum lengths of strings matching a regular expression
function regexBounds ($pattern, &$min, &$max) {
	$final = substr ($pattern, strlen ($pattern) - 1); // Preserve final /i from translation.
	for ($let = 2; $let <= 26; $let++) {
		$pattern = str_replace (chr (ord ('a') + $let - 1), 'a', $pattern);
	}
	$pattern = substr ($pattern, 0, strlen ($pattern) - 1) . $final;
	$pattern = str_replace ('[^', '[', $pattern); // don't negate patterns or A will never match
	$min = 999;
	$max = 0;
	$tester = "";
	while (strlen ($tester) < 100) {
		$tester = $tester . 'a';
		if (preg_match ($pattern, $tester)) {
			$max = strlen ($tester);
			if ($min == 999) {
				$min = $max;
			}
		}
	}
	return true;
}

// Return a string with its characters sorted (e.g., unsorted --> denorstu).
function stringSort ($string) {
	$strarray = str_split ($string);
	sort ($strarray);
	return implode ($strarray);
}

// Insert two-forked SQL into appropriate places
function insertSql ($sql, $more) {
	$prewhere = '/* premain */';
	if (isset ($more['pre'])) {
		$sql = str_replace ($prewhere, "{$more['pre']} $prewhere", $sql);
	}
	if (isset ($more['where'])) {
		$sql = $sql . $more['where'];
	}
	return $sql;
}
?>
