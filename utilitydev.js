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
	removeConsMore (thisOption); // generated code
	removeChildren (thisOption, "label not query delcons butspace wizard br details tdetails ");

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

	updateSortChoices ();
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
	var oneChild;
	nameList.split (" ").forEach(function (baseName) {
		if ((oneChild = document.getElementById(baseName + thisOption)) !== null) {
			oneChild.parentNode.removeChild(oneChild);
		}
	});
}

// Zap the form back to its original state
function resetForm() {
	var theForm = document.forms["search"];
	deleteUnusedOptions (''); // Will delete all of them, to be rebuilt later with the defaults
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
	var answer;
	theForm['morecbx'].value.substring (1).split(' ').forEach(function (fieldName) {
		if (fieldName.substring (0, 6)=='corpus') {
			answer = true;
		} else if (/_dc$/.test(fieldName)) { // DC = default checked
			answer = true;
		} else {
			answer = false;
		}
		document.getElementById(fieldName).checked=answer;
	});

	// Reset output stuff
	theForm['lettersonly'].checked = false;
	theForm['wordcaseN'].checked = true;
	theForm['losource'].checked = true;
	theForm['letteralpha'].checked = false;
	theForm['letterabank'].checked = false;
	theForm['letteralinks'].checked = false;
	theForm['letterauc'].checked = false;
	theForm['customlink'].value = '';
	theForm['usetable'].checked = false;
	theForm['rowmulti'].checked = false;
	theForm['pagelen'].value = '';
	theForm['sort1'].value = 'word';
	theForm['sort2'].value = 'word';
	theForm['desc1'].checked = false;
	theForm['desc2'].checked = false;
	theForm['fontN'].checked = true;
	theForm['fontname'].value = '';
	theForm['fontlettera'].checked = false;
	theForm['fontword'].checked = false;
	theForm['fontdetails'].checked = false;

	resetCorporaMore(); // generated dynamically
	updateSortChoices ();
} // end resetForm

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
	var corpus;
	for (elnum = 0; elnum < theForm.elements.length; elnum++) {
		item = theForm.elements[elnum];
		if (item.id.substring (0, 6) == 'corpus') {
			corpus = item.id.substring (6);
			var countField = "count" + corpus;
			if (theForm[countField] === undefined) {
				count = 0;
			} else {
				count = theForm[countField].value;
			}
			if (item.checked) {
		  	anycorpus++;
				anysubs += count;
				var ret = validateCorpus (corpus);
				if (ret > '') {
					alert (ret);
					return false;
				}
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

//* Loop through all dropdown buttons to toggle between hiding and showing its dropdown content - This allows the user to have multiple dropdowns without any conflict */
function dropInit () {
	var dropdown = document.getElementsByClassName("dropdown-btn");
	var i;

	for (i = 0; i < dropdown.length; i++) {
	  dropdown[i].addEventListener("click", function() {
	    this.classList.toggle("active");
			var arrow = document.getElementById (this.id.substring(0, this.id.length - 2) + 'arrow');
	    var dropdownContent = this.nextElementSibling;
	    if (dropdownContent.style.display === "block") {
	      dropdownContent.style.display = "none";
				arrow.innerHTML = '&#9662;';
	    } else {
	      dropdownContent.style.display = "block";
				arrow.innerHTML = '&#9652;';
	    }
	  });
	}
}

function loClick (linkOption) {
  document.getElementById('customlink').style.display = (linkOption == 'custom') ? 'inline' : 'none';
}

function updateSortChoices () {
  var optionList = 'word:word/L:length';
	var multipleOK = true;
  if (theForm['letteralpha'].checked) {
    optionList += '/A:alphabetized letters';
		multipleOK = false;
  }
  if (theForm['letterabank'].checked) {
    optionList += '/B:letters without duplication';
		multipleOK = false;
  }
  var thisOption;
  var here;
  for (thisOption = 2; thisOption <= theForm['count'].value; thisOption++) {
    here = theForm['details' + thisOption];
    if (here !== undefined  &&  here.checked) {
      optionList += '/cv' + thisOption + ':constraint #' + thisOption;
			multipleOK = false;
    }
  }
  optionList += '/' + theForm['morecbx'].value.substring (1).split(' ').map(function (fieldName) {
    var corpusOptions = '';
    var corpus;
    var thisOption;
		var optionCount;
		var here;
    if (fieldName.substring (0, 6)=='corpus') {
      if (theForm[fieldName].checked) {
        corpus = fieldName.substring (6);
				here = theForm['count' + corpus];
				optionCount = (here === undefined) ? 0 : here.value;
        for (thisOption = 1; thisOption <= optionCount; thisOption++) {
					if ((details = document.getElementById('details' + corpus + '_' + thisOption)) !== null  &&  details.checked) {
            corpusOptions += '/cv' + corpus + '_' + thisOption + ':' + document.getElementById('cn' + corpus).innerHTML + ' constraint #' + thisOption;
						multipleOK = false;
          }
        }
      }
    }
    return corpusOptions;
  }).join ('/'); // Ugly closure of anonymous map function before finishing statement
  for (sortField = 1; sortField < 3; sortField++) {
    if (sortField == 2) {
      optionList = '-:none/' + optionList;
    }
    sorter = theForm['sort' + sortField]; // global for access within forEach
    oldValue = sorter.value; // ditto
    for (thisOption = sorter.options.length - 1; thisOption >= 0; thisOption--) {
      sorter.remove (0);
    }
    optionList.split('/').forEach (function (optionInfo) {
      var colon;
      if ((colon = optionInfo.indexOf (':')) > 0) {
        var newOption = document.createElement('option');
        var newValue = optionInfo.substring (0, colon);
        newOption.value = newValue;
        newOption.text = optionInfo.substring (colon + 1);
        sorter.options.add(newOption);
        if (newValue == oldValue) {
          sorter.value = oldValue;
        }
      }
    })
  }
	theForm ['rowmulti'].disabled = !multipleOK;
	if (!multipleOK) {
		theForm ['rowmulti'].checked = false;
	}
	document.getElementById ('trowmulti').style = 'color:' + (multipleOK ? 'black' : 'gray');
	updateHighlightChoices ();
} // end updateSortChoices

function updateHighlightChoices () {
  var here;
  var optionList = '';
  var thisOption;
  for (thisOption = 2; thisOption <= theForm['count'].value; thisOption++) {
    if (theForm['details' + thisOption] !== undefined) {
      optionList += '/v' + thisOption + ':constraint #' + thisOption;
    }
  }
  optionList += '/' + theForm['morecbx'].value.substring (1).split(' ').map(function (fieldName) {
    var corpus;
    var corpusName;
    var corpusOptions = '';
    var thisOption, corpusOptionNumber;
		var optionCount;
		var here;
    var matches;
    if (theForm[fieldName].checked) {
      if (fieldName.substring (0, 6)=='corpus') {
        corpus = fieldName.substring (6);
        corpusName = document.getElementById('cn' + corpus).innerHTML;
        corpusOptions = '/cp' + corpus + ':' + corpusName;
        if ((here = theForm['count' + corpus]) !== undefined) {
          for (corpusOptionNumber = 1; corpusOptionNumber <= here.value; corpusOptionNumber++) {
            if (document.getElementById('label' + corpus + '_' + corpusOptionNumber) !== null) {
              corpusOptions += '/cv' + corpus + '_' + corpusOptionNumber + ':&nbsp;&nbsp;' + corpusName + ' constraint #' + corpusOptionNumber;
            }
          }
        }
      } else if (matches = fieldName.match (/^c([0-9])+flag(.)$/)) {
        corpusOptions += '/cf_' + matches[2] + ':' + document.getElementById('tflag' + matches[2] + matches[1]).innerHTML;
      }
    }
    return corpusOptions;
  }).join ('/'); // Ugly closure of anonymous map function before finishing statement

  var options = optionList.split('/');
  var here, there;
  var fieldNum, nextField;
  for (fieldNum = 0; fieldNum < options.length; fieldNum++) {
    if (fieldInfo = options[fieldNum]) {
      var colon = fieldInfo.search (':');
      var id = fieldInfo.substring (0, colon);
      var choiceID = 'dispdiv' + id;
      if (document.getElementById(choiceID) === null) {
        // Create and load content into Div element
        var newDiv = document.createElement('div');
        newDiv.id = choiceID;
        newDiv.appendChild (newSpan ('l' + choiceID, '&nbsp;' + fieldInfo.substring (colon + 1)));
        newDiv.appendChild (newInput (choiceID + 'not', 'checkbox', ''));
        newDiv.appendChild (newSpan (choiceID + 'tnot', 'Not&nbsp;'));
        newDiv.appendChild (newRadio (choiceID + 'p', 'r' + choiceID, 'C', 'P', ''));
        if (id.substring (0, 2) == 'cv') {
          label = 'filter';
        } else {
          label = 'none';
        }
        newDiv.appendChild (newSpan (choiceID + 'tn', label + '&nbsp;'));
        newDiv.appendChild (newRadio(choiceID + 'i', 'r' + choiceID, '', 'I', ''));
        newDiv.appendChild (newSpan (choiceID + 'ti', '<i>italics</i>&nbsp;'));
        newDiv.appendChild (newRadio (choiceID + 'b', 'r' + choiceID, '', 'B', ''));
        newDiv.appendChild (newSpan (choiceID + 'tb', '<b>bold</b>&nbsp;'));
        newDiv.appendChild (newRadio (choiceID + 'u', 'r' + choiceID, '', 'U', ''));
        newDiv.appendChild (newSpan (choiceID + 'tu', '<u>underline</u>&nbsp;'));
        newDiv.appendChild (newRadio (choiceID + 's', 'r' + choiceID, '', 'S', ''));
        newDiv.appendChild (newSpan (choiceID + 'ts', '<strike>struck out</strike>&nbsp;'));
        newDiv.appendChild (newRadio (choiceID + 'l', 'r' + choiceID, '', 'L', ''));
        newDiv.appendChild (newSpan (choiceID + 'tl', '<font size=+1>larger</font>&nbsp;'));
        newDiv.appendChild (newRadio (choiceID + 'cb', 'r' + choiceID, '', 'CB', ''));
        newDiv.appendChild (newSpan (choiceID + 'tcb', '<span style="background-color:yellow">color</span>&nbsp;'));
        newDiv.appendChild (newRadio (choiceID + 'cf', 'r' + choiceID, '', 'CF', ''));
        newDiv.appendChild (newSpan (choiceID + 'tcf', '<font color="blue">color</font>&nbsp;'));
        newDiv.appendChild (newInput (choiceID + 'x', 'text', ''));
        // find the right place to insert it
        here = document.getElementById('endoutput');
        for (nextField = fieldNum + 1; nextField < options.length; nextField++) {
        there = document.getElementById('dispdiv' + options[nextField].substring (0, options[nextField].search (':')));
          if (there !== null) {
            here = there;
            break;
          }
        }
      	myParent = here.parentNode.insertBefore (newDiv, here);
      } // end if not already there
    }
  }

  deleteUnusedOptions (optionList);

} // end updateHighlightChoices

function deleteUnusedOptions (optionList) {
	var container = document.getElementById('endoutput').parentNode;
	var fieldNum;
	var here;
  for (fieldNum = container.children.length - 1; fieldNum >= 0; fieldNum--) {
    here = container.children[fieldNum];
    if (here.id.substring (0, 7) == 'dispdiv'  &&  optionList.search ('/' + here.id.substring (7) + ':') < 0) {
      container.removeChild(here);
    }
  }
}

function updateShowOutput () {
  document.getElementById('output').style.display = document.getElementById('showoutput').checked ? 'inline' : 'none';
}
