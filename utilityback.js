optionNumber = 1;
currentOption = 1;

function addOption (pro) {
	// add a new constraint when user presses that button
	var theForm = document.getElementById("search");
	optionNumber++;
	var here = theForm["count"];
	here.value = optionNumber;
	var myParent = here.parentNode;

	// Label for constraint
	var newOption = document.createElement("span");
	newOption.id = "label" + optionNumber;
	newOption.innerHTML = "#" + optionNumber + ": (not)";
	myParent.insertBefore (newOption, here);

	// Checkbox for NOT
	newOption = document.createElement("input");
	newOption.name = "not" + optionNumber;
	newOption.type = "checkbox";
	newOption.id = "not" + optionNumber;
	myParent.insertBefore (newOption, here);

	newOption = document.createElement("input");
	newOption.name = "query" + optionNumber;
	newOption.type = "text";
	newOption.required = true;
	newOption.id = "query" + optionNumber;
	myParent.insertBefore (newOption, here);

	// Radio buttons for constraint types
	addRadio ("pattern", "pattern");
	addRadio ("regex", "regular expression");
	addRadio ("subword", "subword");
	addRadio ("weights", "weight");
	addRadio ("charmatch", "letter match");
	addRadio ("crypto", "cryptogram");
	if (pro > 0) {
		addRadio ("customsql", "custom SQL");
	}
	theForm["rpattern" + optionNumber].checked = true;

	// Button to remove constraint
	newOption = document.createElement ("button");
	newOption.type = "button";
	newOption.id = "delcons" + optionNumber;
	newOption.innerHTML = "Remove";
	newOption.setAttribute('onclick','removeConstraint(' + optionNumber + ')');
	myParent.insertBefore (newOption, here);

	newOption = document.createElement("span");
	newOption.id = "butspace" + optionNumber;
	newOption.innerHTML = "&nbsp;&nbsp;";
	myParent.insertBefore (newOption, here);

	// Button to open wizard (for some types)
	newOption = document.createElement ("button");
	newOption.type = "button";
	newOption.id = "wizard" + optionNumber;
	newOption.innerHTML = "Wizard";
	newOption.setAttribute('onclick','openWizard(' + optionNumber + ')');
	myParent.insertBefore (newOption, here);

	newOption = document.createElement ("br");
	newOption.id = "br" + optionNumber;
	myParent.insertBefore (newOption, here);

	// run side effects of selecting the first radio button
	radioClicked(optionNumber);
}

function addRadio (radioValue, labelText) {
	var here = document.getElementById("count");
	var myParent = here.parentNode;

	var newOption = document.createElement("input");
	newOption.type = "radio";
	newOption.name = "radio" + optionNumber;
	newOption.value = radioValue;
	newOption.id = "r" + radioValue + optionNumber;
	newOption.setAttribute('onclick','radioClicked(' + optionNumber + ')');
	myParent.insertBefore (newOption, here);

	newOption = document.createElement("span");
	newOption.id = "t" + radioValue + optionNumber;
	newOption.innerHTML = " " + labelText + " ";
	myParent.insertBefore (newOption, here);
}

function badGroups (pattern) {
	// return true if pattern is not valid
	var inside = false;
	var pos;
	// check that groups are properly formed
	for (pos = 0; pos < pattern.length; pos++) {
		var onechar = pattern.substring (pos, pos + 1);
		if (onechar == "[") {
			if (inside) {return true;}
			inside = true;
		} else if (onechar == "]") {
			if (!inside) {return true;}
			inside = false;
		} else if (onechar == "!") {
			if (pattern.substring (pos - 1, pos) != "[") {return true;}
		} else if (onechar == "-") {
			if (!/[a-z]-[a-z]/i.test(pattern.substring (pos-1, pos+2))) {return true;}
			if (pattern.substring (pos-1, pos) >= pattern.substring (pos+1, pos+2)) {return true;}
		}
	}
	if (inside) {return true;} // no closing ]
	return false; // no problem found
}

// Side effects when the user changes the form
function mainChange ()
{
	var theForm = document.forms["search"];
	if (theForm["repeat"] !== undefined) {
		var letterBank = false;
		// If Any Order is checked and there are no repeated letters or wildcards, allow them to specify that
		// letter banks are okay; that is "allow repeats" is enabled.
		if (theForm["anyorder"].checked) {
			var pattern = document.forms["search"]["pattern"].value.toLowerCase();
			// expand groups and ranges
			while ((here = pattern.indexOf ("-")) > 0) {
				var startChar = pattern.substring (here - 1, here);
				var endChar = pattern.substring (here + 1, here + 2);
				var aCode = 'a'.charCodeAt(0);
				var middleChars = "abcdefghijklmnopqrstuvwxyz".substring (startChar.charCodeAt(0)-aCode+1, endChar.charCodeAt(0)-aCode-1);
				pattern = pattern.substring (0, here) + middleChars + pattern.substring (here + 1);
			}
			pattern = pattern.replace ("@", "aeiou").replace("#", "bcdfghjklmnpqrstvwxyz").replace("&","ivxlcdm");
			// check for any duplicate letter
			letterBank = !/(.).*\1/i.test(pattern) &&
				pattern.indexOf ("*") < 0 && pattern.indexOf ("?") < 0; // and no wildcard
		}

		// Now that we know, update the GUI
		theForm["repeat"].disabled = !letterBank;
		if (letterBank) {
			textColor = "black";
		} else {
			theForm["repeat"].checked = false;
			textColor = "gray";
		}
		document.getElementById("trepeat").style = "color:" + textColor; // for some reason, theForm["trepeat"] doesn't work here
	}
}

// remove subsidiary radio buttons for Weights, if present
function noWeightSub (thisOption) {
	if (document.forms["search"]["rscrabble" + thisOption] !== undefined) {
		removeChildren (thisOption, "twtob rscrabble tscrabble ralpha talpha");
	}
}

// Side effects when one of the main radio buttons is selected
function radioClicked (thisOption) {
	currentOption = thisOption;
	var theForm = document.forms["search"];
	// If Weights, make subsidiary buttons available
	if (theForm["rweights" + thisOption].checked) {
		if (theForm["rscrabble" + thisOption] === undefined) {
			here = theForm["rcharmatch" + thisOption];
			myParent = here.parentNode;

			newOption = document.createElement("span");
			newOption.id = "twtob" + thisOption;
			newOption.innerHTML = " [";
			myParent.insertBefore (newOption, here);

			newOption = document.createElement("input");
			newOption.type = "radio";
			newOption.name = "wttype" + thisOption;
			newOption.value = "SCR";
			newOption.id = "rscrabble" + thisOption;
			newOption.checked = true;
			myParent.insertBefore (newOption, here);

			newOption = document.createElement("span");
			newOption.id = "tscrabble" + thisOption;
			newOption.innerHTML = " Scrabble&reg; ";
			myParent.insertBefore (newOption, here);

			newOption = document.createElement("input");
			newOption.type = "radio";
			newOption.name = "wttype" + thisOption;
			newOption.value = "ALF";
			newOption.id = "ralpha" + thisOption;
			myParent.insertBefore (newOption, here);

			newOption = document.createElement("span");
			newOption.id = "talpha" + thisOption;
			newOption.innerHTML = " alphabet] ";
			myParent.insertBefore (newOption, here);

		}
		hint = "This option will look at the weight of each letter, which can either be its value in Scrabble (e.g., H=4) or its position " +
				"in the alphabet (e.g., H=8), possibly with a multiplier, added up over the whole word.  If the search field is left blank, " +
				"a multiplier of 1 will be used for each letter.  If a series of digits is entered (e.g., 3112), the multipliers will be " +
				"used for the corresponding letters--x3 for the first letter and x2 for the fourth letter.  These digits can be followed by " +
				"a plus sign to use 1 for the remaining letters in the word or a minus sign to use 0 (skip).  Finally, a second set of " +
				"digits can specify weights for letters at the end of the word (e.g., +31 to use a weight of 3 for the next-to-last letter). " +
				"Some full examples on the word EXAMPLE: 3111 with Scrabble chosen will be 15: 3x1 + 8 + 1 + 3.  12+21 with alphabet chosen " +
				"will be 112: 5 + 2x24 + 1 + 13 + 16 + 2x12 + 5.  After this specification, put either <, =, or > followed by a number. " +
				"+>50 will give all words with total weight greater than 50.";
		wizard = true;
	} else { // otherwise, hide them
		noWeightSub (thisOption);
		if (theForm["rcharmatch" + thisOption].checked) {
			hint = "The option allows you to specify that certain characters within the word must match or have another relationship.  " +
				"The simplest case is something like 3=8, which means that the third and eighth letters are the same.  A more complicated " +
				"example is 3>-3+^5 which says that the third letter has to be more than five places later in the alphabet (+^5) than the " +
				"third letter from the end (-3).";
			wizard = true;
		} else if (theForm["rregex" + thisOption].checked) {
			hint = "regex hint";
				wizard = false;
			hint = "Enter a <A target='_blank' HREF='https://regexone.com/'>regular expression</A> which the word must match.";
		} else if (theForm["rpattern" + thisOption].checked) {
			hint = "Enter a simple pattern, as with the main search box.";
				wizard = false;
		} else if (theForm["rsubword" + thisOption].checked) {
			hint = "This option allows you to require that a second, related word also exists.  Enter a series of letters and numbers.  Each " +
				"letter represents itself; a number represents a position in the original word.  For example, with the pattern D123, if the " +
				"original word is ISCHEMIA, the program will check for the existence of DISC.  Numbers can be negative, to count from the end " +
				"of the word; two digits, in which case they must be separated by commas; or ranges, such as 3:-1 to indicate the all but the " +
				"first two letters of the word.  Out-of-order ranges indicate that the letter sequence will be reversed; again with ISCHEMIA, " +
				"the pattern 8:5D will represent AIMED.";
				wizard = false;
		} else if (theForm["rcrypto" + thisOption].checked) {
			hint = "The option allows you to specify a pattern of matching and nonmatching letters: a word which might be a solution in a " +
				"cryptogram for the other word.  For example, ELLISVILLE would match REENGINEER and ABCABC would match words such as " +
				"ATLATL, BONBON, and TSETSE.  Use * to specify that the word has no matching letters.";
				wizard = false;
		} else if (theForm["rcustomsql" + thisOption].checked) {
			hint = "Enter a constraint to appear in the WHERE clause, most likely referencing PW.text (the candidate word, letters only, " +
				"such as INTHEYEAR for <u>In the Year 2525</u> and/or PW.bank (the list of letters, such as AEHINRTY).";
				wizard = false;
		}
	}

	theForm["wizard" + thisOption].disabled = !wizard;

	// Get rid of old hint and display new one.
	if (wizard) {
		hint += "  You can also press the Wizard button to open a form which will help you enter the required pieces of the specification.";
	}

	theForm.removeChild(document.getElementById("hint"));
	here = document.getElementById("delcons" + thisOption);
	myParent = here.parentNode;

	newOption = document.createElement("span");
	newOption.id = "hint";
	newOption.innerHTML = "<br>" + hint + "<br>";
	newOption.className = "hint"; // see CSS
	myParent.insertBefore (newOption, here);
}

function removeConstraint(thisOption)
// remove the chosen constraint
{
	noWeightSub (thisOption);
	removeChildren (thisOption, "label not query rpattern rregex rsubword rweights rcharmatch rcrypto " +
				"tpattern tregex tsubword tweights tcharmatch tcrypto delcons butspace wizard br");

	// if it is the last one being removed, decrement the count
	var here = theForm["count"];
	if (here.value == thisOption) {
		optionNumber--;
		here.value = optionNumber;
	}

	// if it is the one with a hint displayed, blank out the hint (don't delete it, because code elsewhere assumes it exists)
	if (thisOption == currentOption) {
		document.getElementById("hint").innerHTML = "";
	}

	return false;
}

// remove a list of children associated with a specific numbered constraint
function removeChildren (thisOption, nameList) {
	theForm = document.getElementById("search");
	nameList.split (" ").forEach(function (baseName) {
			theForm.removeChild(document.getElementById(baseName + thisOption));
	});
}

// Zap the form back to its original state
function resetForm() {
	theForm = document.forms["search"];
	// clear simple fields
	theForm["pattern"].value = "";
	theForm["anyorder"].checked = false;
	theForm["repeat"].checked = false;
	theForm["whole"].checked = false;
	theForm["single"].checked = true;
	theForm["phrase"].checked = true;
	theForm["wikipedia"].checked = true;
	theForm["gettysburg"].checked = true;
	theForm["minlen"].value = "";
	theForm["maxlen"].value = "";

	// Remove all other constraints.  Start at the end (LIFO), though it may not be strictly necessary
	for (thisOption = theForm["count"].value; thisOption > 1; thisOption--) {
		if (theForm["query" + thisOption] !== undefined) {
			removeConstraint (thisOption);
		}
	}
	// Make sure we know there are no extra constraints.
	theForm["count"].value = "1";
	optionNumber = 1;
}

// Make sure the form is okay before we submit it for processing.
function validateForm() {
	// Pull out some values from the form
	var theForm = document.forms["search"];
	var minlen = theForm["minlen"].value;
	var maxlen = theForm["maxlen"].value;
	var single = theForm["single"].checked;
	var phrase = theForm["phrase"].checked;

	// Check if the primary pattern has mismatched brackets or similar issues
	var pattern = theForm["pattern"].value;
	if (badGroups (pattern)) {
		alert("Invalid letter group in main pattern");
		return false;
	}

	if (theForm["anyorder"].checked  &&  /[\[@#&].*[\[@#&]/.test(pattern)) {
		alert ("Only one character group is allowed when 'any order' is specified.");
		return false;
	}

	// Loop through additional constraints and validate them in a way suitable for the chosen type
	for (thisOption = 2; thisOption <= optionNumber; thisOption++) {
		thisitem = theForm["query" + thisOption];
		thisitem.focus();
		thispattern = thisitem.value;
		// Same validation as with the main pattern
		if (theForm["rpattern" + thisOption].checked) {
			if (!/^[a-z?*@#&\[\-\]]+$/i.test (thispattern)) {
				alert("Invalid character in pattern " + thisOption);
				return false;
			}
			if (badGroups (thispattern)) {
				alert("Invalid letter group in pattern " + thisOption);
				return false;
			}
		} else if (theForm["rsubword" + thisOption].checked) {
			if (/[^-a-z0-9:,]/i.test(thispattern)) {
				alert("Invalid character in subword specification " + thisOption);
				return false;
			}
		} else if (theForm["rweights" + thisOption].checked) {
			// Left multipliers, optional plus or minus, right multipliers, operator, comparison value
			if (!(/^[0-9]*([-+][0-9]*)?[<=>][0-9]+$/.test(thispattern))) {
				alert("Invalid weight specification " + thisOption);
				return false;
			}
		} else if (theForm["rcharmatch" + thisOption].checked) {
			// First position, operator, second position, optional ^offset
			if (!/^-?[1-9][0-9]*[<=>]-?[1-9][0-9]*([+\-]\^[1-9][0-9]*)?$/i.test(thispattern)) {
				alert("Invalid letter match specification " + thisOption);
				return false;
			}
		} else if (theForm["rcrypto" + thisOption].checked) {
			if (thispattern == "*") {
				continue;
			}
			if (/[^a-z]/i.test(thispattern)) {
				alert("Invalid cryptogram pattern " + thisOption);
				return false;
			}
			if ((maxlen > 0  &&  maxlen != thispattern.length) || (minlen > 0  &&  minlen != thispattern.length)) {
				alert("Cryptogram length is inconsistent with requested word length");
				return false;
			}
		}
	}

	if ((maxlen > 0) && (+maxlen < +minlen)) {
		alert("Minimum is greater than maximum");
		theForm["maxlen"].focus();
		return false;
	}

	if (!single && !phrase) {
		alert ("Must choose Single words and/or Phrases");
		return false;
	}
  // Are any corpus items checked?
	anycorpus = false;
	for (elnum = 0; elnum < theForm.elements.length; elnum++) {
		item = theForm.elements[elnum];
		if (item.id.substring (0, 6) == 'corpus') {
			if (item.checked) {
		  	anycorpus = true;
				break;
			}
		}
	}
	if (!anycorpus) {
		alert ("Must choose at least one source");
		// Don't bother setting focus--no visual feedback anyhow
		return false;
	}
}
