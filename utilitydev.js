optionNumber = 1;
currentOption = 1;

function addRadio (radioValue, labelText) {
	var here = document.getElementById("count");
	var myParent = here.parentNode;

	myParent.insertBefore (newRadio ('r' + radioValue + optionNumber, 'radio' + optionNumber, '', radioValue, 'radioClicked(' + optionNumber + ')'), here);
	myParent.insertBefore (newSpan ('t' + radioValue + optionNumber, ' ' + labelText + ' '), here);
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
	var letterBank = false;
	var theForm = document.forms["search"];
	var textColor;
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
	if (!letterBank) {
		theForm["repeat"].checked = false;
	}
	document.getElementById("trepeat").style = "color:" + (letterBank ? 'black' : 'gray'); // for some reason, theForm["trepeat"] doesn't work here
}

function removeConstraint(thisOption)
// remove the chosen constraint
{
	// noWeightSub (thisOption);
	removeConsMore (thisOption); // generated code
	removeChildren (thisOption, "label not query delcons butspace wizard br");

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
	removeChildrenGeneric ('search', thisOption, nameList);
}

function removeChildrenCorpus (corpusOptionNumber, nameList) {
	removeChildrenGeneric ('source', corpusOptionNumber, nameList);
}

function removeChildrenGeneric (containerField, thisOption, nameList) {
	var container = document.getElementById(containerField);
	var oneChild;
	nameList.split (" ").forEach(function (baseName) {
		if ((oneChild = document.getElementById(baseName + thisOption)) !== null) {
			container.removeChild(oneChild);
		}
	});
}

// Zap the form back to its original state
function resetForm() {
	var theForm = document.forms["search"];
	// clear simple fields
	theForm["pattern"].value = "";
	theForm["anyorder"].checked = false;
	theForm["repeat"].checked = false;
	theForm["whole"].checked = false;
	theForm["single"].checked = true;
	theForm["phrase"].checked = true;
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

	// Reset stuff relating to word lists
	theForm['morecbx'].value.substring (1).split(' ').forEach(function (fieldName) {
		document.getElementById(fieldName).checked=(fieldName.substring (0, 6)=='corpus');
	});

	resetCorporaMore(); // generated dynamically
}

// Make sure the form is okay before we submit it for processing.
function validateForm() {
	// Pull out some values from the form
	var theForm = document.forms["search"];
	var minlen = theForm["minlen"].value;
	var maxlen = theForm["maxlen"].value;
	var single = theForm["single"].checked;
	var phrase = theForm["phrase"].checked;
	var thisOption;
	var thisItem;
	var thisPattern;

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
	var ret;
	for (thisOption = 2; thisOption <= optionNumber; thisOption++) {
		if (ret = validateConstraint (thisOption)) {
			alert (ret);
			return false;
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
	var anycorpus = 0;
	var anysubs = 0;
	var item;
	var count;
	for (elnum = 0; elnum < theForm.elements.length; elnum++) {
		item = theForm.elements[elnum];
		if (item.id.substring (0, 6) == 'corpus') {
			var countField = "count" + item.id.substring (6);
			if (theForm[countField] === undefined) {
				count = 0;
			} else {
				count = theForm[countField].value;
			}
			if (item.checked) {
		  	anycorpus++;
				anysubs += count;
			} else if (count > 0) {
				alert ("Can't have additional criteria on an unchecked source");
				return false;
			}
		}
	}
	if (anycorpus > 1  &&  anysubs > 0) {
		alert ("Can't select multiple sources when additional criteria are specified for one");
		return false;
	}
	if (anycorpus == 0) {
		alert ("Must choose at least one source");
		// Don't bother setting focus--no visual feedback anyhow
		return false;
	}
}

// Various shortcuts to create certain types of HTML form controls
function newSpan (id, text) {
	var newOption = document.createElement('span');
	newOption.id = id;
	newOption.innerHTML = text;
	return newOption;
}

function newBreak (id) {
	var newOption = document.createElement ('br');
	newOption.id = id;
	return newOption;
}

function newInput (nameid, type, flags) {
	var newOption = document.createElement('input');
	newOption.type = type;
	newOption.id = newOption.name = nameid;
	newOption.required = /R/.test(flags);
	return newOption;
}

function newRadio (id, name, flags, value, onclick) {
	var newOption = document.createElement('input');
	newOption.type = 'radio';
	newOption.name = name;
	newOption.id = id;
	newOption.value = value;
	newOption.checked = /C/.test(flags);
	newOption.setAttribute('onclick', onclick);
	return newOption;
}

function newButton (id, text, onclick) {
	var newOption = document.createElement ('button');
	newOption.type = 'button';
	newOption.id = id;
	newOption.innerHTML = text;
	newOption.setAttribute('onclick', onclick);
	return newOption;
}


function wizInsert (newOption) {
	var here = document.getElementById('wizfields');
	here.parentNode.insertBefore (newOption, here);
	if (fieldlist > '') {
		fieldlist += ' ';
	}
	fieldlist += newOption.id;
}
