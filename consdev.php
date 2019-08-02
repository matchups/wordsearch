<?php
class constraint {
	// Base class for additional constraints
	protected $spec; 		// what the user typed
	protected $num;		// constraint sequence number
	protected $not;		// true if the "Not" checkbox is checked

	public function __construct ($spec, $num, $not) {
		$this->spec = strtolower ($spec);
		$this->num = $num;
		$this->not = $not;
		$this->init();
	}

	protected function init() {
		// additional initialization code for specific subclasses
		}

	public function explain() {
		if ($this->not) {
			$prefix = 'not ';
		} else {
			$prefix = '';
		}
		return $prefix . $this->explainSub();
	}

	protected function explainSub() {
		// Provides explanation of constraint, displayed on results screen, not including possible Not.
		// Called only from explain
		return $this::getLabel() . " $this->spec";
	}

	public function parse() {
		// Parse specification and create SQL
		// returns AND stuff for WHERE clause and/or an inner join
		throw new Exception ("Base parse--needs to be overridden");
	}

	protected function parseWhere ($more) {
		return array ('where' => $more);
	}

	public function setlengths(&$consmin, &$consmax) {
		// For cryptograms, the word length must match the pattern length
		// &$consmin and &$consmax -- word length boundar`ies
		// Do nothing by default
	}

	public function localFilter($oneword, $entry, $entry_id) {
		// Do any additional filtering that can't be done in SQL
		// $oneword = the word to check
		// $entry = external name; useful for literal or source checks
		// returns true if okay or false if bad
		return true;
	}

	public function position() {
		// Array of letters in known positions
		return array();
	}

	public function required() {
		// List of required letters
		return '';
	}

	public function fourPattern () {
		// String with known trigrams
		return '';
	}

	public function rebuildForm($realNumber) {
		Echo "addOption();\n";
		if ($this->not) {
			Echo "theForm['not$realNumber'].checked = true;\n";
		}
		Echo "theForm['query$realNumber'].value = '" . addslashes ($this->spec) . "';\n";
		// Set the radio button corresponding to the selected option
		Echo "theForm['r" . $_GET["radio$this->num"] . "$realNumber'].checked = true;\n";
		Echo "radioClicked ($realNumber);\n";
	}

	protected function maybeNot () {
		if ($this->not) {
			return ' NOT ';
		} else {
			return '';
		}
	}

  // This value is used to segregate counters
  public function parentID () {
		return 'F'; // main form
	}

  // ** Begin Static functions **

	// List of normal (not corpus-linked) constraint classes
  public static function list () {
		$classes = array ("conspattern", "consregex", "conssubword", "consweights", "conscharmatch", "conscrypto");
		IF ($_GET['level'] == 3) {
			array_push ($classes, "conscustomsql");
		}
		return $classes;
	}

  // Does this constraint support a wizard?
	public static function wizard () {
		return false;
	}

	public static function getWizardValue () {
		// code to build newValue in wizard based on form fields
		throw new Exception ("Must override getWizardValue");
	}

	public static function getWizardOpenCode () {
		// code to set up wizard UI
		throw new Exception ("Must override getWizardOpenCode");
	}

	public static function getLabel () {
		// Label for radio button
		throw new Exception ("Must override getLabel");
	}

	public static function getButtonCode () {
		// Code to add and remove controls when button selected/deselected
		// Should be array (add->stuff, del->stuff)
		return '';
	}

	public static function getMoreCode () {
		// any self-contained Javascript (such as functions called by code in other get...Code scripts) that doesn't belong anywhere else
		return '';
	}

	public static function getValidateConstraintCode () {
		// Code to validate specifications for constraint.  Can access thisSpec
		throw new Exception ("Must override getValidateConstraintCode");
	}

	public static function getHint () {
		return 'Dummy hint';
	}


	public function debug () {return "[" . get_class() . "#$this->num=$this->spec]";}

} // end class constraint

class conspattern extends constraint {
	// Simple pattern
	public function parse() {
		// Convert to regular expression
		$spec = patternToRegex (expandSpecial ($this->spec), 'S');
		return $this->parseWhere ("AND PW.text " . $this->maybeNot() . " RLIKE '$spec' ");
	}

	public function position() {
		if ($this->not) {
			return array();
		} else {
			return patternPosition ($this->spec);
		}
	}

	public function required() {
		if ($this->not) {
			return '';
		} else {
			return patternRequired ($this->spec);
		}
	}

	public function fourPattern () {
		if ($this->not) {
			return '';
		} else {
			return groupToWildcard ($this->spec);
		}
	}

	public static function getLabel () {
		return 'pattern';
	}

	public static function getValidateConstraintCode () {
		return "  // Same validation as with the main pattern
			if (!/^[a-z?*@#&\[\-\]]+$/i.test (thisValue)) {
				return 'Invalid character in pattern ' + thisOption;
			}
			if (badGroups (thisValue)) {
				return 'Invalid letter group in pattern ' + thisOption;
			}";
	}

	public static function getHint () {
		return "Enter a simple pattern, as with the main search box.";
	}
} // end class conspattern

class conssubword extends constraint {
	public function parse() {
	// boilerplate to look for subword
	$more = " AND " . $this->maybeNot() . " EXISTS (SELECT 1 FROM words SW INNER JOIN word_entry SWE ON SWE.word_id = SW.id " .
		   " INNER JOIN entry SE ON SE.id = SWE.entry_id " .
		   " WHERE SW.text = ";
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
	$more = $more . ' concat(' . substr ($substr, 2) . ')'; // Combine all the pieces on the database side.
	$more = $more . ' AND ' . corpusInfo('SE', 'W') . ')'; // subword has to be in same corpus as main word
	return $this->parseWhere ($more);
} // end function

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

class consweights extends constraint {
	protected function explainSub() {
		return (($_GET["wttype$this->num"] == "SCR") ? 'Scrabble&reg;' : "Alphabet") . " weight $this->spec";
	}

	public function parse() {
		preg_match ('/^([0-9]*)([-+]?)([0-9]*)([<=>][0-9]*)$/', $this->spec, $matches);
		// $left (digits) / + or - / $right (digits) / $compare (like >30)
		$compare = $matches [4];
		if ($this->not) {
			$compare = str_replace (array ('<', '=', '>'), array ('>=', '!=', '<='), $compare);
		}
		$left = $matches [1];
		if ($matches [2] == '+') {
			$default = 1;
		} else {
			$default = 0;
		}
		$right = $matches [3];
		// Compute multiplier, based on character position in beginning, middle, or end of word.
		if ($left == ''  &&  $right == '') {
			$times = '';
		} else if ($left == '') {
			$times = "convert (substr('$right', spandex.value - char_length (PW.text) + " . strlen ($right) . ", 1), signed)";
			$times = "(CASE WHEN char_length (PW.text) - spandex.value < " . (strlen ($right) + 1) . " THEN $times ELSE $default END)";
		} else if ($right == '') {
			$times = "convert (substr('$left', spandex.value, 1), signed)";
			if ($default > 0) {
				$times = "(CASE WHEN spandex.value <= " . strlen($left) . " THEN $times ELSE $default END)";
			}
		} else {
			$times = "(CASE WHEN spandex.value <= " . strlen ($left) . " THEN convert (substr('$left', spandex.value, 1), signed) " .
							"WHEN char_length (PW.text) - spandex.value < " . (strlen ($right) + 1) . " THEN " .
								 "convert (substr('$right', spandex.value - char_length (PW.text) + " . strlen ($right) . ", 1), signed) " .
							"ELSE $default END)";
		}
		if ($times != '') {
			$times = '* ' . $times;
		}
		// Now use the spandex table (which contains numbers 1-100) to add up the weights of all the letters in the word.
		$wttype = $_GET["wttype$this->num"];
		$sql = "AND (SELECT sum(weights.weight $times) FROM weights INNER JOIN spandex " .
			"WHERE weights.name = '$wttype' AND weights.letter = substr(PW.text, spandex.value, 1) ".
			"AND spandex.value <= char_length(PW.text)) $compare ";

		return $this->parseWhere ($sql);
	}

	public static function wizard () {
		return true;
	}

	public static function getWizardValue () {
		return "newValue = document.getElementById('wnweightleft').value;
		if (!document.getElementById('wrwtskip').checked) {
			if (document.getElementById('wrwtone').checked) {
				newValue += '+';
			} else {
				endthing = document.getElementById('wnweightright').value;
				if (document.getElementById('wrwtend').checked) {
					newValue += '-' + endthing;
				} else {
					newValue += '+' + endthing;
				}
			}
		}
		if (document.getElementById('wtrrell').checked) {
			newValue += '<';
		} else if (document.getElementById('wtrrele').checked) {
			newValue += '=';
		} else if (document.getElementById('wtrrelg').checked) {
			newValue += '>';
		}
		newValue += document.getElementById('wtconst').value;";
	}

	public static function getWizardOpenCode () {
		return "		wizInsert (newSpan ('wtweightleft', 'Weight multipliers of letters at beginning of word (e.g., 1133 for third and fourth to be multiplied by three): '));
				wizInsert (newInput ('wnweightleft', 'number', 'R'));
				wizInsert (newBreak ('wizbr1'));
				wizInsert (newSpan ('wtwtintro', 'What about the remaining characters in the word?'));
				wizInsert (newBreak ('wizbr2'));
				wizInsert (newRadio ('wrwtskip', 'wrwtrest', 'C', 'wizRadioClicked()'));
				wizInsert (newSpan ('wtwtskip', ' skip '));
				wizInsert (newRadio ('wrwtone', 'wrwtrest', '', 'wizRadioClicked()'));
				wizInsert (newSpan ('wtwtone', ' use base weights '));
				wizInsert (newRadio ('wrwtend', 'wrwtrest', '', 'wizRadioClicked()'));
				wizInsert (newSpan ('wtwtend', ' skip until specified characters at end '));
				wizInsert (newBreak ('wizbr3'));
				wizInsert (newRadio ('wrwtmidend', 'wrwtrest', '', 'wizRadioClicked()'));
				wizInsert (newSpan ('wtmidend', ' use base weights until specified characters at end '));
				wizInsert (newBreak ('wizbr4'));
				wizInsert (newSpan ('wtweightright', 'Weight multipliers of letters at end of word: '));
				wizInsert (newInput ('wnweightright', 'number', 'R'));
				wizInsert (newSpan ('wttrel', '<BR>Should the total weight be...'));
				wizInsert (newBreak ('wizbr5'));
				wizInsert (newRadio ('wtrrell', 'wtrel', '', ''));
				wizInsert (newSpan ('wttrell', ' less than '));
				wizInsert (newRadio ('wtrrele', 'wtrel', 'C', ''));
				wizInsert (newSpan ('wttrele', ' equal to '));
				wizInsert (newRadio ('wtrrelg', 'wtrel', '', ''));
				wizInsert (newSpan ('wttrelg', ' greater than '));
				wizInsert (newSpan ('wttconst', '<BR>Constant value for comparison: '));
				wizInsert (newInput ('wtconst', 'number', 'R'));
				wizRadioClicked();\n";
	} // end getWizardOpenCode

	public static function getMoreCode () {
	return "	// When a radio button is selected, the right side multipliers are enabled only if the selection
		// is compatible with that.
		function wizRadioClicked() {
			var allowEnd = (document.getElementById('wrwtend').checked || document.getElementById('wrwtmidend').checked);
			document.getElementById('wnweightright').disabled = !allowEnd;
			document.getElementById('wtweightright').style = 'color:' + (allowEnd ? 'black' : 'gray');
		}

		// remove subsidiary radio buttons for Weights, if present
		function noWeightSub (thisOption) {
			if (document.forms['search']['rscrabble' + thisOption] !== undefined) {
				removeChildren (thisOption, 'twtob rscrabble tscrabble ralpha talpha');
			}
		}\n";
}

	public static function getLabel () {
		return 'weight';
	}

	public static function getValidateConstraintCode () {
		return "// Left multipliers, optional plus or minus, right multipliers, operator, comparison value
			if (!(/^[0-9]*([-+][0-9]*)?[<=>][0-9]+$/.test(thisValue))) {
				return 'Invalid weight specification ' + thisOption;
			}";
		}

	public static function getButtonCode () {
		$ret ['add'] = "
				// If Weights, make subsidiary buttons available
				if (theForm['rscrabble' + thisOption] === undefined) {
					var here = theForm['rcharmatch' + thisOption];
					var myParent = here.parentNode;

					myParent.insertBefore (newSpan ('twtob' + thisOption, ' ['), here);
					myParent.insertBefore (newRadio ('rscrabble' + thisOption, 'wttype' + thisOption, 'C', 'SCR', ''), here);
					myParent.insertBefore (newSpan ('tscrabble' + thisOption, ' Scrabble&reg; '), here);

					myParent.insertBefore (newRadio ('ralpha' + thisOption, 'wttype' + thisOption, '', 'ALF', ''), here);
					myParent.insertBefore (newSpan ('talpha' + thisOption, ' alphabet] '), here);
				}";
			$ret ['del'] = "		noWeightSub (thisOption);";
			return $ret;
	}

  public static function getHint () {
		return "This option will look at the weight of each letter, which can either be its value in Scrabble (e.g., H=4) or its position
			in the alphabet (e.g., H=8), possibly with a multiplier, added up over the whole word.  If the search field is left blank,
			a multiplier of 1 will be used for each letter.  If a series of digits is entered (e.g., 3112), the multipliers will be
			used for the corresponding letters--x3 for the first letter and x2 for the fourth letter.  These digits can be followed by
			a plus sign to use 1 for the remaining letters in the word or a minus sign to use 0 (skip).  Finally, a second set of
			digits can specify weights for letters at the end of the word (e.g., +31 to use a weight of 3 for the next-to-last letter).
			Some full examples on the word EXAMPLE: 3111 with Scrabble chosen will be 15: 3x1 + 8 + 1 + 3.  12+21 with alphabet chosen
			will be 112: 5 + 2x24 + 1 + 13 + 16 + 2x12 + 5.  After this specification, put either <, =, or > followed by a number.
			+>50 will give all words with total weight greater than 50.";
		}

} // end class consweights

class consregex extends constraint {
	private $regex;
	private $local;

	protected function init() {
		$this->regex = expandSpecial ($this->spec);
		if (substr ($this->regex, 0, 1) != '/') {
			$this->regex = '/' . $this->regex . '/';
		}
	}

	public function parse() {
		if (strpos ($this->regex, '(') !== false) { // MySQL doesn't support this
			$this->local = true;
			return "";
		} else {// good, we can do it on the database side
			$this->local = false;
			return $this->parseWhere (" AND PW.text " . $this->maybeNot() . " RLIKE '" . substr ($this->regex, 1, strlen ($this->regex) - 2) . "' ");
		}
	}

	public function localFilter($oneword, $entry, $entry_id) {
		if ($this->local) {
			$matched = preg_match ($this->regex, $oneword);
			if ($this->not) {
				$matched = !$matched;
			}
			return $matched;
		} else {
			return true; // done on the DB side; no filtering here
		}
	}

	public function rebuildForm($realNumber) {
		parent::rebuildForm($realNumber);
		Echo "radioClicked ($realNumber);\n"; // This will display the Scrabble and alphabetic radio buttons
		if ($_GET["wttype$this->num"] == 'ALF') {
			Echo "theForm['ralpha$realNumber'].checked = true;\n";
		} // else it is Scrabble, which is set by default.
	}

	public function position() {
		if ($this->not) {
			return array();
		} else {
			return patternPosition ($this->regex);
		}
	}

	public function required() {
		if ($this->not) {
			return '';
		} else {
			return patternRequired ($this->regex);
		}
	}

	public function fourPattern () {
		if ($this->not) {
			return '';
		} else {
			return groupToWildcard ($this->spec);
		}
	}

	public static function getLabel () {
		return 'regular expression';
	}

	public static function getValidateConstraintCode () {
		return "// We'll let you put *anything* in a regular expression";
	}

	public static function getHint () {
		return "Enter a <A target=\"_blank\" HREF=\"https://regexone.com/\">regular expression</A> which the word must match.";
	}

} // end class consregex

class conscharmatch extends constraint {
	public function parse() {
		// Extract from string like 5=-2+^8: {5, =, -2, +^8}
		preg_match ('/^(-?[1-9][0-9]*)([<=>])(-?[1-9][0-9]*)([+\-]\^[1-9][0-9]*)?$/', $this->spec, $matches);
		// Get SQL expression for two sides
		for ($side = 1; $side <= 3; $side = $side + 2) {
				$expr = $matches [$side];
				if ($expr < 0) {
				if ($expr == -1) {
						$expr = '';
				} else {
						$expr = $expr + 1;
				}
				$expr = "char_length (PW.text) " . $expr;
				}
				$expra [$side] = "substring(PW.text, $expr, 1)";
			if (count ($matches) > 4) { // If we're going to do arithmetic, we need the ASCII values
				$expra [$side] = "ASCII (" . $expra [$side] . ")";
			}
		}
		// Determine relationship from what was typed and possible NOT
		$rel = $matches[2];
		if ($this->not) {
			$rel = str_replace (array ('<', '=', '>'), array ('>=', '!=', '<='), $rel);
		}
		$sql = " AND $expra[1] $rel $expra[3]";
		// Adjustment on value of right side
		if (count ($matches) > 4) {
			$sql = $sql . str_replace ('^', '', $matches [4]);
		}

		return $this->parseWhere ($sql);
	}

	public static function wizard () {
		return true;
	}

	public static function getWizardValue () {
		return "if (document.getElementById('wrmatchless').checked) {
						newValue = '<';
					} else if (document.getElementById('wrmatchequal').checked) {
						newValue = '=';
					} else if (document.getElementById('wrmatchgreater').checked) {
						newValue = '>';
					}
					newValue = document.getElementById('wnmatchleft').value + newValue + document.getElementById('wnmatchright').value;
					offset = document.getElementById('wnoffset').value;
					if (offset > 0) {
						newValue += '+^' + offset;
					} else if (offset < 0) {
						newValue += '-^' + -offset;
					}\n";
	}

	public static function getWizardOpenCode () {
	return "		wizInsert (newSpan ('wtmatchleft', 'Character position to start with (positive to count from beginning or negative to count from end): '));
	wizInsert (newInput ('wnmatchleft', 'number', 'R'));
	wizInsert (newBreak ('wizbr1'));
  wizInsert (newRadio ('wrmatchless', 'wrmatchrel', '', ''));
	wizInsert (newSpan ('wtless', ' < less than '));
	wizInsert (newRadio ('wrmatchequal', 'wrmatchrel', 'C', ''));
	wizInsert (newSpan ('wtequal', ' equals '));
	wizInsert (newRadio ('wrmatchgreater', 'wrmatchrel', '', ''));
	wizInsert (newSpan ('wtgreater', ' > greater than '));
	wizInsert (newBreak ('wizbr2'));
	wizInsert (newSpan ('wtmatchright', 'Character position to compare against: '));
	wizInsert (newInput ('wnmatchright', 'number', 'R'));
	wizInsert (newBreak ('wizbr3'));
	wizInsert (newSpan ('wtoffset', 'Offset (optional); for example, 2 if you want F to match D or -2 for R to match T: '));
	wizInsert (newInput ('wnoffset', 'number', ''));\n";
	} // end getWizardOpenCode

	public static function getLabel () {
		return 'letter match';
	}

	public static function getValidateConstraintCode () {
		return "// First position, operator, second position, optional ^offset
		if (!/^-?[1-9][0-9]*[<=>]-?[1-9][0-9]*([+\-]\^[1-9][0-9]*)?$/i.test(thisValue)) {
			return 'Invalid letter match specification ' + thisOption;
		}";
	}

	public static function getHint () {
		return "The option allows you to specify that certain characters within the word must match or have another relationship.
			The simplest case is something like 3=8, which means that the third and eighth letters are the same.  A more complicated
			example is 3>-3+^5 which says that the third letter has to be more than five places later in the alphabet (+^5) than the
			third letter from the end (-3).";
	  }
} // end class conscharmatch

class conscrypto extends constraint {
	public function parse() {
		$spec = $this->spec;
		if ($spec == '*') {
			// If we want all letters different, check that the length of the letter bank
			// is the same as the length of the word.  (I tried doing a SQL RLIKE with (.).*\1, but MySQL
			// doesn't support that.)
			if ($this->not) {
				$rel = "<";
			} else {
				$rel = "=";
			}
			$sql = " AND char_length(PW.bank) $rel char_length(PW.text)";
		} else {
			if ($this->not) {
				$sql = $sql . " AND NOT (1 = 1 ";
			}
			// Loop through pairs of letters and generate appropriate SQL
			for ($first = 0; $first < strlen ($spec); $first++) {
				$fchar = substr ($spec, $first, 1);
				if (strpos (substr ($spec, 0, $first), $fchar) !== false) { // If we have already compared to an identical letter
					continue;
				}
				for ($second = $first + 1; $second < strlen ($spec); $second++) {
					$schar = substr ($spec, $second, 1);
					if ($fchar == $schar) {
						$relation = "=";
					} else {
						$relation = "<>";
					}
					$sql = $sql . " AND substring(PW.text, " . ($first + 1) . ", 1) $relation substring(PW.text, " . ($second + 1) . ", 1)";
				}
			}
			if ($this->not) {
				$sql = $sql . " ) ";
			}
		}
		return $this->parseWhere ($sql);
	} // end parse

	public function setlengths(&$consmin, &$consmax) {
			// Word length has to match cryptogram pattern length
		if ($this->spec <> '*') {
			$consmin = strlen ($this->spec);
			$consmax = $consmin;
		}
	}

	public static function getLabel () {
		return 'cryptogram pattern';
	}

	public static function getValidateConstraintCode () {
		return " if (thisValue == '*') {
			return '';
		}
		if (/[^a-z]/i.test(thisValue)) {
			return 'Invalid cryptogram pattern ' + thisOption;
		}
		if ((maxlen > 0  &&  maxlen != thisValue.length) || (minlen > 0  &&  minlen != thisValue.length)) {
			return 'Cryptogram length is inconsistent with requested word length';
		}";
	}

public static function getHint () {
	return "The option allows you to specify a pattern of matching and nonmatching letters: a word which might be a solution in a
		cryptogram for the other word.  For example, ELLISVILLE would match REENGINEER and ABCABC would match words such as
		ATLATL, BONBON, and TSETSE.  Use * to specify that the word has no matching letters.";
	}
} // end class conscrypto

class conscustomsql extends constraint {
	public function parse() {
		return " AND $this->spec ";
	}

	public static function getLabel () {
		return 'custom SQL';
	}

	public static function getValidateConstraintCode () {
		return "// We'll let you put anything in SQL, at least for now";
	}

	public static function getHint () {
		return "Enter a constraint to appear in the WHERE clause, most likely referencing PW.text (the candidate word, letters only,
			such as INTHEYEAR for <u>In the Year 2525</u> and/or PW.bank (the list of letters, such as AEHINRTY).";
		}
} // end class customsql
?>
