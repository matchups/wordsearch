<?php
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
?>
