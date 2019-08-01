<!-- Sketch out wizards, which CSS will make invisible until user asks for one of them -->
<div id="wizard" class="wizard">
  <div class="wizard-content">
	<form name="popwiz" id="popwiz">
		<input type="hidden" name=wizfields id=wizfields />
		<input type="hidden" name=wizoption id=wizoption />
	</form>
	<button type="button" id="popclose" onclick="closeWizard(true);">OK</button>
	<button type="button" id="popcancel" onclick="closeWizard(false);">Cancel</button>
  </div>
</div>

<div id="catlookup" class="wizard">
  <div class="wizard-content">
	<form name="catwiz" id="catwiz">
		<input type="text" name=category id=category class=category />
	</form>
  <P><P>
	<button type="button" id="catclose" onclick="closeCatWizard(true);">OK</button>
	<button type="button" id="catcancel" onclick="closeCatWizard(false);">Cancel</button>
  </div>
</div>

<?php
$advanced = true;
$sessionkey = $_GET['sessionkey'];
$level = $_GET['level'];
if ($level == 0) {
	$advanced = false;
} else {
	if (isset($_GET['simple'])) {
		if ($_GET[simple] == 'on') {
			$advanced = false;
			echo "<A id=advanced>Advanced search</A>\n";
		}
	}
}
if ($advanced) {
	Echo "<H3>Control</H3>";
	Echo "<A id=basic>Basic search</A>\n";
}
Echo "<BR>\n";
Echo "<form name='search' id='search' onsubmit='return validateForm()' method='get'>\n";
Echo "<input type=hidden id='simple' name='simple' value='" . ($advanced ? 'off' : 'on') . "' />\n";
if ($advanced) {
	Echo "<input type='submit' value='Submit' id='yyy'/>\n";
}
?>
<span id=expspan><input type=checkbox id='explain' name=explain /> Explain</span>

<H3>Pattern</H3>
<label>Basic Pattern: </label>
   <input type=text name=pattern placeholder="PRE?O*" onchange="mainChange();" category
   required=true pattern="[-a-zA-Z\*\?[\]@#&]*"> <!-- Allow letters, wildcards *?, specials @#&, and groups [-] -->
   <br>
<label>Any order?</label>
   <input name=anyorder type=checkbox onchange="mainChange();"/>
<?php
if ($advanced) {
	echo "<span id=trepeat style='disabled'>Repeat letters? <input name=repeat type=checkbox disabled=true /></span>\n";
}
echo '<input type=hidden id="count" name="count" value="1" /><BR>';
if ($advanced) {
	echo '<button type="button" id="addbut" onclick="addOption(' . ($level / 3) . ');return false;">Add Constraint</button>' . "\n";
}
echo "<input type=hidden id='sessionkey' name='sessionkey' value='$sessionkey' />";
echo "<input type=hidden id='level' name='level' value='$level' />";
?>
<H3>Filters</H3>
<label>Minimum length: <input type=number name=minlen min=3
   step=1 /></label><br>
<label>Maximum length: <input type=number name=maxlen min=3
   step=1 /></label><br>
<?php
if ($advanced) {
	echo "<span id=twhole>Whole entry only? <input name=whole type=checkbox /></span><br>\n";
}
?>
<label>Single words? <input name=single type=checkbox
   checked /></label><br>
<label>Phrases? <input name=phrase type=checkbox checked /></label><br>
<span id=hint></span>

<div id="source">
<H3>Source</H3>
<?php
try {
  $conn = openConnection (false);
  $checklist = '';
  $result = $conn->query("SELECT id FROM corpus ORDER BY corpus.id");
  while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $corpusObject = corpus::factory ($corpus = $row['id']);
    if ($corpusObject->allowed()) {
      $checklist = $checklist . $corpusObject->form();
      $corpusObjects[$corpus] = $corpusObject;
    }
  }
  echo "<input type=hidden name=morecbx value='$checklist' />\n";
}
catch (PDOException $e) {
  errorMessage ("SQL failed identifying sources: " . $e->getMessage());
}
unset ($conn);
echo "<script>\n";
echo "function resetCorporaMore () {\n";
echo "var count;\n";
foreach ($corpusObjects as $corpusObject) {
  $corpus = $corpusObject->getCorpusNum();
  $key = "count$corpus";
  echo "if (theForm['$key'] !== undefined) {
    for (count = theForm['$key'].value; count > 0; --count) {
      removeConstraint$corpus(count);
    }
    theForm['$key'].value=0;
  }\n";
}
echo "} // end resetCorporaMore\n";
echo "</script>\n";
?>
<span class='disabled'>
<label>Wiktionary (English): <input name=wiktionary type=checkbox
   /></label><br>
<label>Wiktionary (foreign): <input name=foreign disabled=true type=checkbox /></label><br>
</span>
</div>
<!-- Put the type (Beta, Dev, Back, or nil) in the form so subsequent scripts can access it -->
<input type=hidden id='type' name='type' />
<input type=hidden id='version' name='version' /><!-- Filled in by index.php -->
<input type="submit" value="Submit" id="xxx"/>

</form>

<form id=help action="help.html">
<input type="submit" value="Help" />
</form>

<P>
<button type="button" id="reset" onclick="resetForm();return false;">Reset</button>
<input id="btntest" type="button" value="Log Out"
<?php
echo "onclick=\"window.location.href = 'http:logout.php?sessionkey=$sessionkey'\" />\n";

// Wizard stuff
Echo "<script>
// Close the wizard.  If saveFlag, use the values to populate this constraint's query field.
function closeWizard (saveFlag) {
	var thisOption = document.getElementById('wizoption').value;
	var newValue;
	var offset;
	var endthing;

	if (saveFlag) {
		// logic depends on type\n";
foreach (constraint::list () as $classname) {
	if ($classname::wizard()) {
		echo "if (document.getElementById('r" . substr ($classname, 4). "' + thisOption).checked) {\n";
		echo $classname::getWizardValue () . "\n";
		echo "}\n";
	}
}
echo "
		document.getElementById('query' + thisOption).value = newValue;
	}

	// delete wizard fields
	var wizForm = document.forms['popwiz'];
	document.getElementById('wizfields').value.split(' ').forEach(function (fieldName) {
			wizForm.removeChild(document.getElementById(fieldName));
	});

	// finally, hide wizard
	modal.style.display = 'none';
}

// Open the wizard and populate its form
function openWizard (thisOption) {
	document.getElementById('wizoption').value = thisOption; // So the wizard knows where to put the answer when he closes

  var newOption;
	fieldlist = '';
	theForm = document.getElementById('search');\n";
	foreach (constraint::list () as $classname) {
		if ($classname::wizard()) {
			echo "if (document.getElementById('r" . substr ($classname, 4). "' + thisOption).checked) {\n";
			echo $classname::getWizardOpenCode () . "\n";
			echo "}\n";
		}
	}

	echo "	document.getElementById('wizfields').value = fieldlist;

	// Tell browser to display the wizard in foreground
	document.getElementById('wizard').style.display = 'block';
	return false;
}\n";

// Is there any other code that this constraint wants to dump into the page?
foreach (constraint::list () as $classname) {
	if ($classname::wizard()) {
		echo $classname::getMoreCode () . "\n";
	}
}

// Dynamically create some Javascript functions
echo "function addOption (pro) {
  //!! Change Most of these to use Newxxx functions
	//!! Can probably get rid of pro because constraint list does it right
	// add a new constraint when user presses that button
	var theForm = document.getElementById('search');
	optionNumber++;
	var here = theForm['count'];
	here.value = optionNumber;
	var myParent = here.parentNode;

	// Label for constraint
	var newOption = document.createElement('span');
	newOption.id = 'label' + optionNumber;
	newOption.innerHTML = '#' + optionNumber + ': (not)';
	myParent.insertBefore (newOption, here);

	// Checkbox for NOT
	newOption = document.createElement('input');
	newOption.name = 'not' + optionNumber;
	newOption.type = 'checkbox';
	newOption.id = 'not' + optionNumber;
	myParent.insertBefore (newOption, here);

	newOption = document.createElement('input');
	newOption.name = 'query' + optionNumber;
	newOption.type = 'text';
	newOption.required = true;
	newOption.id = 'query' + optionNumber;
	myParent.insertBefore (newOption, here);\n";

	// Radio buttons for constraint types
  $counter = 0;
  foreach (constraint::list () as $classname) {
    echo "addRadio ('" . substr ($classname, 4) . "', '" . $classname::getLabel() . "');\n";
    if ($counter++ == 0) {
      echo "theForm['r" . substr ($classname, 4) . "' + optionNumber].checked = true;\n";
    }
	}

echo "	// Button to remove constraint
	newOption = document.createElement ('button');
	newOption.type = 'button';
	newOption.id = 'delcons' + optionNumber;
	newOption.innerHTML = 'Remove';
	newOption.setAttribute('onclick','removeConstraint(' + optionNumber + ')');
	myParent.insertBefore (newOption, here);

	newOption = document.createElement('span');
	newOption.id = 'butspace' + optionNumber;
	newOption.innerHTML = '&nbsp;&nbsp;';
	myParent.insertBefore (newOption, here);

	// Button to open wizard (for some types)
	newOption = document.createElement ('button');
	newOption.type = 'button';
	newOption.id = 'wizard' + optionNumber;
	newOption.innerHTML = 'Wizard';
	newOption.setAttribute('onclick','openWizard(' + optionNumber + ')');
	myParent.insertBefore (newOption, here);

	newOption = document.createElement ('br');
	newOption.id = 'br' + optionNumber;
	myParent.insertBefore (newOption, here);

	// run side effects of selecting the first radio button
	radioClicked(optionNumber);
}

// Side effects when one of the main radio buttons is selected
function radioClicked (thisOption) {
	currentOption = thisOption;
	var theForm = document.forms['search'];
	var hint;
	var wizard;
	// If Weights, make subsidiary buttons available\n";
  foreach (constraint::list () as $classname) {
    if ($buttonCode = $classname::getButtonCode ()) {
      echo "if (theForm['r" . substr ($classname, 4) . "' + thisOption].checked) {
        {$buttonCode['add']}
      } else {
        {$buttonCode['del']}
      }\n";
    }
  }

	echo "if (theForm['rweights' + thisOption].checked) {
		hint = 'This option will look at the weight of each letter, which can either be its value in Scrabble (e.g., H=4) or its position ' +
				'in the alphabet (e.g., H=8), possibly with a multiplier, added up over the whole word.  If the search field is left blank, ' +
				'a multiplier of 1 will be used for each letter.  If a series of digits is entered (e.g., 3112), the multipliers will be ' +
				'used for the corresponding letters--x3 for the first letter and x2 for the fourth letter.  These digits can be followed by ' +
				'a plus sign to use 1 for the remaining letters in the word or a minus sign to use 0 (skip).  Finally, a second set of ' +
				'digits can specify weights for letters at the end of the word (e.g., +31 to use a weight of 3 for the next-to-last letter). ' +
				'Some full examples on the word EXAMPLE: 3111 with Scrabble chosen will be 15: 3x1 + 8 + 1 + 3.  12+21 with alphabet chosen ' +
				'will be 112: 5 + 2x24 + 1 + 13 + 16 + 2x12 + 5.  After this specification, put either <, =, or > followed by a number. ' +
				'+>50 will give all words with total weight greater than 50.';
		wizard = true;
	} else {
		if (theForm['rcharmatch' + thisOption].checked) {
			hint = 'The option allows you to specify that certain characters within the word must match or have another relationship.  ' +
				'The simplest case is something like 3=8, which means that the third and eighth letters are the same.  A more complicated ' +
				'example is 3>-3+^5 which says that the third letter has to be more than five places later in the alphabet (+^5) than the ' +
				'third letter from the end (-3).';
			wizard = true;
		} else if (theForm['rregex' + thisOption].checked) {
			hint = 'regex hint';
				wizard = false;
			hint = 'Enter a <A target=\"_blank\" HREF=\"https://regexone.com/\">regular expression</A> which the word must match.';
		} else if (theForm['rpattern' + thisOption].checked) {
			hint = 'Enter a simple pattern, as with the main search box.';
				wizard = false;
		} else if (theForm['rsubword' + thisOption].checked) {
			hint = 'This option allows you to require that a second, related word also exists.  Enter a series of letters and numbers.  Each ' +
				'letter represents itself; a number represents a position in the original word.  For example, with the pattern D123, if the ' +
				'original word is ISCHEMIA, the program will check for the existence of DISC.  Numbers can be negative, to count from the end ' +
				'of the word; two digits, in which case they must be separated by commas; or ranges, such as 3:-1 to indicate the all but the ' +
				'first two letters of the word.  Out-of-order ranges indicate that the letter sequence will be reversed; again with ISCHEMIA, ' +
				'the pattern 8:5D will represent AIMED.';
				wizard = false;
		} else if (theForm['rcrypto' + thisOption].checked) {
			hint = 'The option allows you to specify a pattern of matching and nonmatching letters: a word which might be a solution in a ' +
				'cryptogram for the other word.  For example, ELLISVILLE would match REENGINEER and ABCABC would match words such as ' +
				'ATLATL, BONBON, and TSETSE.  Use * to specify that the word has no matching letters.';
				wizard = false;
		} else if (theForm['rcustomsql' + thisOption].checked) {
			hint = 'Enter a constraint to appear in the WHERE clause, most likely referencing PW.text (the candidate word, letters only, ' +
				'such as INTHEYEAR for <u>In the Year 2525</u> and/or PW.bank (the list of letters, such as AEHINRTY).';
				wizard = false;
		}
	}
	theForm['wizard' + thisOption].disabled = !wizard;

	// Get rid of old hint and display new one.
	if (wizard) {
		hint += '  You can also press the Wizard button to open a form which will help you enter the required pieces of the specification.';
	}
	theForm.removeChild(document.getElementById('hint'));
	var here = document.getElementById('delcons' + thisOption);
	var myParent = here.parentNode;

	newOption = document.createElement('span');
	newOption.id = 'hint';
	newOption.innerHTML = '<br>' + hint + '<br>';
	newOption.className = 'hint'; // see CSS
	myParent.insertBefore (newOption, here);
}

function validateConstraint (thisOption) {
		var thisItem = theForm['query' + thisOption];
		thisItem.focus();
		var thisValue = thisItem.value;\n";

    // Generate stuff from classes
    foreach (constraint::list () as $classname) {
      echo "if (theForm['r" . substr ($classname, 4) . "' + thisOption].checked) {\n";
        echo $classname::getValidateConstraintCode () . "
      }\n";
		}
  echo "} // end validateConstraint\n";
?>
// This needs to be at the end, after the wizard has been created
var modal = document.getElementById('wizard');

// When the user clicks anywhere outside of the wizard, close it
window.onclick = function(event) {
  if (event.target == modal) {
		closeWizard ();
  }
}
</script>

<?php
function preserveInfo ($type, $version) {
	// Put some things into form where the main script knows the value and the form doesn't.
	// We can't do it inline, because there's no good way to pass in the values except as a function call.
	echo "<script>\n";
	echo "document.getElementById('type').value = '$type';\n";
	echo "document.getElementById('version').value = '$version';\n";
	echo "document.getElementById('search').action = 'search$type.php';\n";
	echo "document.getElementById('help').action = 'help$type.html';\n";
  $level = $_GET['level'];
  $security = 'sessionkey=' . $_GET['sessionkey'] . "&level=$level";
  if ($GLOBALS['advanced']) {
     echo "document.getElementById('basic').href = 'index$type.php?simple=on&$security';\n";
	} else {
		if ($level > 0) {
      echo "document.getElementById('advanced').href = 'index$type.php?$security';\n";
		}
		echo "document.getElementById('source').style.display = 'none';\n";
	}
	if ($type <> 'dev') {
		echo "document.getElementById('expspan').style.display = 'none';\n";
	}
	echo "</script>\n";
}
?>
