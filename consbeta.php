<?php
include 'asciitize.php';
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

  public function localFilterArray ($row) {
		// Most classes will use the regular localFilter; this one has been added to provide access to other elements
		return $this->localFilter ($row['word'], $row['entry']);
	}


	public function localFilter($oneword, $entry) {
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
		echo " /* " . get_class($this) . ".rF($realNumber) */";
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
	$classes = array ("conspattern", "consregex", "conssubword", "consweights", "conscharmatch", "conscrypto", "consenum");
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
	protected $raw;

	protected function init () {
		$this->raw = getCheckbox ("craw" . $this->num);
	}

	public function parse() {
		// Convert to regular expression
		$spec = patternToRegex (expandSpecial ($this->spec), 'S');
		$column = ($this->raw ? 'entry.name' : 'PW.text');
		return $this->parseWhere (" AND $column " . $this->maybeNot() . " RLIKE '$spec' ");
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

	public static function getButtonCode () {
		$ret ['add'] = "insertRawSub ('rpattern', thisOption);";
		$ret ['del'] = "noRawSub (thisOption);";
		return $ret;
	}

	public function rebuildForm($realNumber) {
		parent::rebuildForm($realNumber);
		if ($this->raw) {
			Echo "theForm['craw$realNumber'].checked = true;\n";
		} // else it is Where, which is set by default.
	}

	public static function getMoreCode () {
	return " // remove subsidiary radio buttons for patterns, if present
		function noRawSub (thisOption) {
			var theForm = document.getElementById('search');
			// Make sure neither controlling radio button is selected.
			if ((!theForm['rpattern' + thisOption].checked && !theForm['rregex' + thisOption].checked) || deleteMode) {
				if (theForm['craw' + thisOption] !== undefined) {
					removeChildren (thisOption, 'rawob craw traw');
				}
			}
		}

		function insertRawSub (field, thisOption) {
			var theForm = document.getElementById('search');
			// If pattern, provide a checkbox for Raw search
			if (theForm['craw' + thisOption] === undefined) {
				var here = theForm[field + thisOption].nextSibling.nextSibling;
				var myParent = here.parentNode;

				myParent.insertBefore (newSpan ('rawob' + thisOption, ' ['), here);
				myParent.insertBefore (newInput ('craw' + thisOption, 'checkbox', ''), here);
				myParent.insertBefore (newSpan ('traw' + thisOption, ' raw search] '), here);
			}
		}";
	}
	public static function getValidateConstraintCode () {
		return "  // Same validation as with the main pattern
			if (!/^[a-z?*@#&\[\-\]]+$/i.test (thisValue)  &&  !theForm['craw' + thisOption].checked) {
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

class consregex extends constraint {
	private $regex;
	private $local;
	protected $raw;

	protected function init() {
		$this->regex = expandSpecial ($this->spec);
		if (substr ($this->regex, 0, 1) != '/') {
			$this->regex = '/' . $this->regex . '/';
		}
		$this->raw = getCheckbox ("craw" . $this->num);
	}

	public function parse() {
		if (strpos ($this->regex, '(') !== false) { // MySQL doesn't support this
			$this->local = true;
			return "";
		} else {// good, we can do it on the database side
			$this->local = false;
			$column = ($this->raw ? 'entry.name' : 'PW.text');
			return $this->parseWhere (" AND $column " . $this->maybeNot() . " RLIKE '" . substr ($this->regex, 1, strlen ($this->regex) - 2) . "' ");
		}
	}

	public function localFilter($oneword, $entry) {
		if ($this->local) {
			$matched = preg_match ($this->regex, $this->raw ? $entry : $oneword);
			if ($this->not) {
				$matched = !$matched;
			}
			return $matched;
		} else {
			return true; // done on the DB side; no filtering here
		}
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

	public static function getButtonCode () {
		$ret ['add'] = "insertRawSub ('rregex', thisOption);";
		$ret ['del'] = "noRawSub (thisOption);";
		return $ret;
	}

	public function rebuildForm($realNumber) {
		parent::rebuildForm($realNumber);
		if ($this->raw) {
			Echo "theForm['craw$realNumber'].checked = true;\n";
		} // else it is Where, which is set by default.
	}

	public static function getValidateConstraintCode () {
		return "//";
		/*
		return "if (/[abc]/.test (thisValue)) {
				return 'Quotation marks are not allowed in regular expressions.';
			}";
			*/
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
	protected $type;

  protected function init () {
			$this -> type = $_GET["cutype" . $this->num];
	}

	public function parse() {
		$spec = str_replace ('pw', 'PW', $this->spec); // restore case because MySQL treats aliases case-specifically
		if ($this->type == 'IJ') {
			return array ('pre' => $spec);
		} else {
			return $this->parseWhere(' AND ' . $spec);
		}
	}

	public static function getLabel () {
		return 'custom SQL';
	}

	public static function getValidateConstraintCode () {
		return "// We'll let you put anything in SQL, at least for now";
	}

	public static function getButtonCode () {
		$ret ['add'] = "
				// If Custom, make subsidiary buttons available
				if (theForm['rcustij' + thisOption] === undefined) {
					var here = theForm['rcustomsql' + thisOption].nextSibling.nextSibling; // For some reason, can't access tcustomsql directly
					var myParent = here.parentNode;

					myParent.insertBefore (newSpan ('tcustob' + thisOption, ' ['), here);
					myParent.insertBefore (newRadio ('rcustij' + thisOption, 'cutype' + thisOption, '', 'IJ', ''), here);
					myParent.insertBefore (newSpan ('tcustij' + thisOption, ' join '), here);

					myParent.insertBefore (newRadio ('rcustw' + thisOption, 'cutype' + thisOption, 'C', 'W', ''), here);
					myParent.insertBefore (newSpan ('tcustw' + thisOption, ' where] '), here);
				}";
			$ret ['del'] = "		noCustomSub (thisOption);";
			return $ret;
	}

	public static function getMoreCode () {
	return "
		// remove subsidiary radio buttons for Custom SQL, if present
		function noCustomSub (thisOption) {
			if (document.forms['search']['rcustij' + thisOption] !== undefined) {
				removeChildren (thisOption, 'tcustob rcustij tcustij rcustw tcustw');
			}
		}\n";
	}

	public function rebuildForm($realNumber) {
		parent::rebuildForm($realNumber);
		if ($this->type == 'IJ') {
			Echo "theForm['rcustij$realNumber'].checked = true;\n";
		} // else it is Where, which is set by default.
	}

	public static function getHint () {
		return "Enter a constraint to appear in the WHERE clause, most likely referencing PW.text (the candidate word, letters only,
			such as INTHEYEAR for <u>In the Year 2525</u> and/or PW.bank (the list of letters, such as AEHINRTY).";
		}

	public function explain () {
		// Show just the condition part
		$spec = $this -> spec;
		if ($_GET["cutype{$this->num}"] == 'IJ') {
			$spec = explode (' on ', $this->spec)[1];
		}
		return $spec;
	}
} // end class customsql

class consenum extends constraint {
  protected $counter;
	protected $pattern;

	public function parse() {
		$this -> pattern = preg_replace_callback ('/[0-9]+|./', function ($matches) {
			$match = $matches[0];
			switch ($match) {
				case ' ':
				case '\'':
				case '-';
				return $match;

				case '#':
				return '[0-9]';

				case '*':
				return '[a-z]+';

				case '?':
				return '[^-\' 0-9a-z]';

				default: // had better be some digits
				return "[a-z]" . '{' . $match . '}';
			}
		},
		$this -> spec);
		comment ($this->pattern = '/^' . $this->pattern . '$/i');
	}

	public function setlengths(&$consmin, &$consmax) {
		$this->counter = 0; // Need to use a class variable so it accessible within the anonymous callback function.
		$spec = $this->spec;
		preg_replace_callback ('/[0-9]+/', function ($matches) {
				$this->counter += $matches[0];
				return '';
			},
				$this->spec);
		if (strpos ($this->spec, '*') === false  &&  $consmax > $this->counter) {
			$consmax = $this->counter;
		}
		if ($this->counter > $consmin) {
			$consmin = $this->counter;
		}
	}

	public function localFilter($oneword, $entry) {
		return preg_match ($this->pattern, asciitize ($entry));
	}

	public static function getLabel () {
		return 'enumeration';
	}

	public static function getValidateConstraintCode () {
		return "if (!/^[-' #*?0-9]+$/i.test (thisValue)) {
			return 'Invalid character in enumeration ' + thisOption;
		}";
	}

	public static function getHint () {
		return "Enter the desired enumeration for the answer.  A simple example would be <font face=Courier>5 4</font>, to represent a five-letter word
			followed by a four-letter word.  You can also use
			<B>#</B> to represent any digit (e.g., <font face=Courier>#### 3 3 4</font> would match <u>1776 and All That</u>),
			<B>\\'</B> and <B>-</B> (quote and hyphen) to represent themselves (<font face=Courier>6 2 \\'##</font> would match <u>Spirit of \\'76</u>),
			<B>*</B> to represent a word (sequence of letters) of any length (<font face=Courier>6 * 9</font> would match <u>Ludvig van Beethoven</u> or <u>George Albert Boulenger,</u>
			or <B>?</B> to represent any punctuation mark other than quote and hyphen.";
			// A few lines up, first \ is to escape the second \ in the PHP string and the second one is to escape the apostrophe in the single-quoted generated code.
	}
}
// A couple of big ones get their own source files
$type = $_GET['type'];
include "consweights$type.php";
include "conssubword$type.php";
?>
