<?php
class conssubword extends constraint {
	public function parse() {
	// boilerplate to look for subword
	$more = " AND " . $this->maybeNot() . " EXISTS (SELECT 1 FROM words SW INNER JOIN word_entry SWE ON SWE.word_id = SW.id " .
		   " INNER JOIN entry SE ON SE.id = SWE.entry_id " .
		   " WHERE SW.text = " . $this->columnSyntax ();
	$more = insertSql ($more, corpusInfo('SE', 'W', $dummy)) . ')'; // subword has to be in same corpus as main word
	return $this->parseWhere ($more);
} // end function

public static function isColumnSyntax () {
	return true;
}

public function columnSyntax () {
	$mode = '';
	$substr = '';
	$fromend = false;
	$spec = $this->spec . '}';
	// loop through characters in spec and convert to SQL-usable reference to substrings of base word
	while ($spec > '') {
		$ch = substr ($spec, 0, 1);
		$spec = substr ($spec, 1);
		$newmode = $mode;

		if (preg_match ('/[1-9]/', $ch)) { // Digit represents a letter position
			if (preg_match ('/[0-9]/', substr ($spec, 0, 1))) { // Next character is also a digit, so
				$ch = $ch . substr ($spec, 0, 1); // pull that off and make it a two-digit number
				$spec = substr ($spec, 1);
			}
			if ($fromend) { // It was a negative number, so generate SQL accordingly
				$fromend = false;
				$here = "char_length(PW.text)-" . ($ch - 1);
				$herecount = "-$ch";
			} else {
				$here = $ch;
				$herecount = $ch;
			}
			$newmode = 'N';
			if ($start != "") { // Is this the end of a range where a start was already established?
				if (($here > 0 && $here < $start) ||
						 ($start < 0 && $here < $start)) { // Backwards range, so swap start and end
					$swap = $here;
					$here = $start;
					$start = $swap;
					$swap = $herecount;
					$herecount = $startcount;
					$startcount = $swap;
					$reverse = true;
				}
				$piece = "substring(PW.text, $start, ";
				// Figure the length, which depends on whether the string spans from a left-based spot to a right-based
				// spot, or if both termini are on the same side.
				if ($startcount * $herecount > 0) {
					 $piece = $piece .
						($herecount - $startcount + 1) . ')';
				} else {
					$piece = $piece . "$here - $startcount + 1)";
				}
				if ($reverse) {
					$piece = "reverse($piece)";
				}
				// File this piece away and reset everything for the next one.
				$substr = $substr . ", " . $piece;
				$start = '';
				$newmode = '';
				$mode = '';
				$reverse = false;
			}
		} elseif ($ch == '-') { // Dash means we are in numeric mode and counting from the end.
			$newmode = 'N';
			$fromend = true;
		} elseif (preg_match ('/[a-z]/i', $ch)) {
			if ($mode == 'A') { // Already in alpha mode, so stick this character on the end.
				$chars = $chars . $ch;
			} else {
				$newmode = 'A'; // Otherwise, start alpha mode.
				$chars = $ch;
			}
		} elseif ($ch == ':') { // Indicates a range; now looking for end
			$start = $here;
			$startcount = $herecount;
		} elseif ($ch == '}') { // I don't think this is actually used
			$newmode = '';
		} elseif ($ch == ',') { // Separating numbers
			if ($herecount <> 0) {
				$newmode = '';
			}
		} else {
			throw new Exception ("Bad character $ch in substring spec"); // Should have been prevented on front end
		}

		if ($newmode <> $mode) { // Mode has changed, so capture the last piece and get going on new one.
			if ($mode == 'A') {
				$substr = $substr . ", '$chars'";
			} elseif ($mode == 'N') {
				$substr = $substr . ", substr(PW.text, $here, 1)";
			}
			$mode = $newmode;
		}
	} // end while
	return 'concat(' . substr ($substr, 2) . ')'; // Combine all the pieces on the database side.
}
	public static function getLabel () {
		return 'subword';
	}

	public static function getValidateConstraintCode () {
		return "	if (/[^-a-z0-9:,]/i.test(thisValue)) {
			 return 'Invalid character in subword specification ' + thisOption;
		 }";
	 }

	public static function getHint () {
		return "This option allows you to require that a second, related word also exists.  Enter a series of letters and numbers.  Each
			letter represents itself; a number represents a position in the original word.  For example, with the pattern D123, if the
			original word is ISCHEMIA, the program will check for the existence of DISC.  Numbers can be negative, to count from the end
			of the word; two digits, in which case they must be separated by commas; or ranges, such as 3:-1 to indicate the all but the
			first two letters of the word.  Out-of-order ranges indicate that the letter sequence will be reversed; again with ISCHEMIA,
			the pattern 8:5D will represent AIMED.";
	}
} // end class conssubword
?>
