<?php
try {
  $conn = openConnection (false);
  $result = $conn->query("SELECT id FROM corpus ORDER BY corpus.id");
  while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $corpusObject = corpus::factory ($corpus = $row['id']);
    if ($corpusObject->allowed()) {
      $corpusObjects[$corpus] = $corpusObject;
    }
  }
}
catch (PDOException $e) {
  errorMessage ("SQL failed identifying sources: " . $e->getMessage());
}
unset ($conn);
?>
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

<?php
// Set up lookups for any corpus which supports categories
echo "<div id='catlookup' class='wizard'>
  <div class='wizard-content'>
	<form name='catwiz' id='catwiz'>\n";
foreach ($corpusObjects as $corpusObject) {
  if (isset ($corpusObject->optionButtonList ()['category'])) {
    $corpus = $corpusObject->getCorpusNum();
    echo "    <input type='text' name=category$corpus id=category$corpus class=category$corpus style='display: none'/>\n";
  }
}
echo "</form>
  <font color=white><P>.<P>.</font><!-- Invisible spacing so that buttons are not hidden-->
	<button type='button' id='catclose' onclick='closeCatWizard(true);'>OK</button>
	<button type='button' id='catcancel' onclick='closeCatWizard(false);'>Cancel</button>
  </div>
</div>\n";
$advanced = true;
$sessionkey = $_GET['sessionkey'];
$level = $_GET['level'];
if ($level == 0) {
	$advanced = false;
} else {
	if (($_GET[simple] ?? '') == 'on') {
		$advanced = false;
		echo "<A id=advanced>Advanced search</A>\n";
	}
}
if ($advanced) {
	Echo "<H3>Control</H3>";
	Echo "<A id=basic HREF='http://alfwords.com'>Basic search</A>\n";
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
	echo '<button type="button" id="addbut" onclick="addOption();return false;">Add Constraint</button>' . "\n";
}
echo "<input type=hidden id='sessionkey' name='sessionkey' value='$sessionkey' />";
echo "<input type=hidden id='level' name='level' value='$level' />";

echo "<H3>Filters</H3>
<label>Minimum length: <input type=number name=minlen min=3
   step=1 /></label><br>
<label>Maximum length: <input type=number name=maxlen min=3
   step=1 /></label><br>\n";

if ($advanced) {
	echo "<span id=twhole>Whole entry only? <input name=whole type=checkbox /></span><br>\n";
}
echo "<label>Single words? <input name=single type=checkbox
   checked /></label><br>
<label>Phrases? <input name=phrase type=checkbox checked /></label><br>
<span id=hint></span>

<div id='source'>
<H3>Source</H3>\n";
$checklist = '';
foreach ($corpusObjects as $corpusObject) {
  $checklist = $checklist . $corpusObject->form();
}
echo "<input type=hidden name=morecbx value='$checklist' />\n";

echo "<script>
function resetCorporaMore () {
var count;\n";
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
echo "function addOption () {
	// add a new constraint when user presses that button
	var theForm = document.getElementById('search');
	optionNumber++;
	var here = theForm['count'];
	here.value = optionNumber;
	var myParent = here.parentNode;

	myParent.insertBefore (newSpan ('label' + optionNumber, '#' + optionNumber + ': (not)'), here); // Label for constraint
  myParent.insertBefore (newInput ('not' + optionNumber, 'checkbox', ''), here);
  myParent.insertBefore (newInput ('query' + optionNumber, 'text', 'R'), here);\n";

	// Radio buttons for constraint types
  $counter = 0;
  foreach (constraint::list () as $classname) {
    echo "addRadio ('" . substr ($classname, 4) . "', '" . $classname::getLabel() . "');\n";
    if ($counter++ == 0) {
      echo "theForm['r" . substr ($classname, 4) . "' + optionNumber].checked = true;\n";
    }
	}

echo "	myParent.insertBefore (newButton ('delcons' + optionNumber, 'Remove', 'removeConstraint(' + optionNumber + ')'), here);
	myParent.insertBefore (newSpan ('butspace' + optionNumber, '&nbsp;&nbsp;'), here);
  myParent.insertBefore (newButton ('wizard' + optionNumber, 'Wizard', 'openWizard(' + optionNumber + ')'), here);
	myParent.insertBefore (newBreak ('br' + optionNumber), here);

	// run side effects of selecting the first radio button
	radioClicked(optionNumber);
}

// Side effects when one of the main radio buttons is selected
function radioClicked (thisOption) {
	currentOption = thisOption;
	var theForm = document.forms['search'];
	var hint;
  var newOption;
	var wizard;\n";
  $delcode = '';
  foreach (constraint::list () as $classname) {
    $suffix = substr ($classname, 4);
    if ($buttonCode = $classname::getButtonCode ()) {
      echo "if (theForm['r$suffix' + thisOption].checked) {
        {$buttonCode['add']}
      } else {
        {$buttonCode['del']}
      }\n";
      $delcode = "$delcode {$buttonCode['del']}\n";
    }
    $fieldlist = "{$fieldlist} r$suffix t$suffix";
    echo "if (theForm['r$suffix' + thisOption].checked) {
      hint = '" . str_replace ("\n", "", $classname::getHint()) . "';
      wizard = " . ($classname::wizard() ? 'true' : 'false'). ";
    }\n";
  }
  $fieldlist = substr ($fieldlist, 1); // remove initial space

	echo "theForm['wizard' + thisOption].disabled = !wizard;

	// Get rid of old hint and display new one.
	if (wizard) {
		hint += '  You can also press the Wizard button to open a form which will help you enter the required pieces of the specification.';
	}
	theForm.removeChild(document.getElementById('hint'));
	var here = document.getElementById('delcons' + thisOption);
	var myParent = here.parentNode;

	newOption = newSpan ('hint', '<br>' + hint + '<br>');
	newOption.className = 'hint'; // see CSS
	myParent.insertBefore (newOption, here);
}

function removeConsMore (thisOption) {
  $delcode
  removeChildren (thisOption, '$fieldlist');
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
  $security = "sessionkey={$_GET['sessionkey']}&level=$level&type=$type";
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
