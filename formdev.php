<?php
try {
  unset ($corpusObjects);
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
    //     <input type='text' name=category$corpus id=category{$corpus}old class=category$corpus style='display: none'/>
    echo "<div class='typeahead__container'>
            <div class='typeahead__field'>
              <div class='typeahead__query'>
                <input class='js-typeahead-category' name=category$corpus[query] id=category$corpus type='search'
                    placeholder='Search' autocomplete='off' style='display: none'/>
              </div>
            </div>
          </div>";
  }
}
echo "</form>
	<button type='button' id='catclose' onclick='closeCatWizard(true);'>OK</button>
	<button type='button' id='catcancel' onclick='closeCatWizard(false);'>Cancel</button>
  </div>
</div>\n";
$advanced = true;
$sessionkey = $_GET['sessionkey'];
$level = $_GET['level'];
$type = $_GET['type'];
$version = $_GET['version'];
if ($level == 0) {
	$advanced = false;
} else {
	if (($_GET[simple] ?? '') == 'on') {
		$advanced = false;
	}
}
Echo "<BR>\n";

Echo "<form name='search' id='search' action='search$type.php' onsubmit='return validateForm()' method='get'>\n";
Echo "<input type=hidden id='simple' name='simple' value='" . ($advanced ? 'off' : 'on') . "' />\n";
if ($advanced) {
	Echo "<input type='submit' value='Submit' id='yyy'/>\n";
}
if ($type == 'dev') {
  echo "<span id=expspan><input type=checkbox id='explain' name=explain /> Explain</span>\n";
}
?>

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
if ($level > 0) {
  $sourceDisplay = '';
} else {
  $sourceDisplay = "style='display: none'";
}
echo "<label>Single words? <input name=single type=checkbox
   checked /></label><br>
<label>Phrases? <input name=phrase type=checkbox checked /></label><br>
<span id=hint></span>

<div id='source $sourceDisplay'>
<H3>Source</H3>\n";
$checklist = '';
foreach ($corpusObjects as $corpusObject) {
  $checklist = $checklist . $corpusObject->form();
  echo "<BR>\n";
}
echo "<input type=hidden name=morecbx value='$checklist' />\n";

echo "<H3>Output</H3>
Show letters in " . inputCheckbox ('letteralpha'). "alphabetical order
" . inputCheckbox ('letterabank'). "without duplication
" . inputCheckbox ('letteralinks'). "with links
<BR>Link option: ";
foreach (array ('suppress', 'source', 'Google', 'Bing', 'Yahoo', 'nGram viewer', 'IMDB', 'custom') as $linkOption) {
  $name = "lo" . strtolower (str_replace (' ', '', $linkOption));
  if (getCheckbox ($name)) {
    $checked = true;
  } else if ($linkOption == 'source'  &&  !getCheckbox ('losuppress')) {
    $checked = true;
  } else {
    $checked = false;
  }
  $more = $checked ? 'checked ' : '' . "onclick='loClick(\"$name\");'";
  echo "<input type=radio name=linkOption id=$name $more>&nbsp$linkOption&nbsp&nbsp ";
  if ($linkOption == 'custom') {
    echo "<input type=text placeholder='http://somewhere.com&title=@' name=customLink id=customLink style='display: none' size=50>";
  }
}
// Start Javascript
echo "<script>
function loClick (linkOption) {
  document.getElementById('customLink').style.display = (linkOption == 'locustom') ? 'inline' : 'none';
}

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
echo "} // end resetCorporaMore

function saveQuery() {
  var url='http:asksavequery$type.php?';
  var elnum, item, name, value;
  for (elnum = 0; elnum < document.getElementById('search').elements.length; elnum++) {
		item = theForm.elements[elnum];
		name = item.name;
    value = item.value;
    if (name && value) {
      url += name + '=' + encodeURIComponent(value) + '&';
    }
	}
  window.open(url, '_blank');
}
  </script>
  </div>
  <!-- Put the type (Beta, Dev, Back, or nil) in the form so subsequent scripts can access it -->
  <input type=hidden id='type' name='type' value='$type' />
  <input type=hidden id='version' name='version' value='$version' />
  <input type='submit' value='Submit' id='xxx'/>
  </form>

  <P>
  <button type='button' id='reset' onclick='resetForm();return false;'>Reset</button>\n";
  $sessionEncoded = urlencode ($sessionkey);

  // Navigation bar
echo "<div class='sidenav'>
<button class='dropdown-btn' id='help-dd'>Help
  <span id=help-arrow>&#9662;</span>
</button>
<div class='dropdown-container' style='display: none'>
  <a href='help$type.html' target='_blank' id=help>Queries</a>
  <a href='helpmanage$type.html' target='_blank' id=helpmanage>Query and list management</a>
  <span class=disabledmenu>Accounts</span>
  <a href='mailto:info@alfwords.com'>Contact</a>
</div>\n";
if ($level > 0) {
  echo "<button class='dropdown-btn' id='account-dd' disabled=yes>Account
  <span id=account-arrow><font color=black>&#9662;</font></span>
  </button>
  <div class='dropdown-container' style='display: none'>
    <span class=disabledmenu>Change password</span>
    <span class=disabledmenu>Change personal information</span>
    <span class=disabledmenu>Renew</span>
    <span class=disabledmenu>Cancel</span>
  </div>\n";
  if ($level > 1) {
    echo "<button class='dropdown-btn' id='list-dd'>Lists
    <span id=list-arrow>&#9662;</span>
      </button>
      <div class='dropdown-container' style='display: none'>
      <a href='http:asksaveresults$type.php?sessionkey=$sessionEncoded&level=$level&type=$type&source=upload' target='_blank'>Upload file</a>\n";
    if (isset ($GLOBALS['ret']['save'])) {
       $sessionEncoded = urlencode ($_GET['sessionkey']);
       echo "<A HREF='http://www.alfwords.com/asksaveresults$type.php?sessionkey=$sessionEncoded&type=$type&level=$level&source=results'
         target='_blank'>Save Results</A>\n";
     }
    if ((SQLQuery($conn, "SELECT 1 FROM corpus WHERE owner = {$GLOBALS['userid']}"))->rowCount() > 0) {
      echo "<a href='http:asksharelist$type.php?sessionkey=$sessionEncoded&level=$level&type=$type' target='_blank'>Share</a>
        <a href='http:askdeletelist$type.php?sessionkey=$sessionEncoded&level=$level&type=$type' target='_blank'>Delete</a>
        <a href='http:askrenamelist$type.php?sessionkey=$sessionEncoded&level=$level&type=$type' target='_blank'>Rename</a>
        <a href='http:askdeleteword$type.php?sessionkey=$sessionEncoded&level=$level&type=$type' target='_blank'>Delete word</a>
        <a href='http:asklistproperties$type.php?sessionkey=$sessionEncoded&level=$level&type=$type' target='_blank'>Properties</a>\n";
    } else {
      echo "<span class=disabledmenu>Share</span>
        <span class=disabledmenu>Delete</span>
        <span class=disabledmenu>Rename</span>
        <span class=disabledmenu>Delete word</span>
        <span class=disabledmenu>Properties</span>\n";
    }
    $userid = $GLOBALS['userid'];
    navLink ('Access Shared', "http:askaccesssharedlist$type.php?sessionkey=$sessionEncoded&level=$level&type=$type",
        (SQLQuery($conn, "SELECT 1 FROM corpus_share WHERE user_id = $userid"))->rowCount() > 0);
    echo "</div>
      <button class='dropdown-btn' id='query-dd'>Queries
      <span id=query-arrow>&#9662;</span>
      </button>
      <div class='dropdown-container' style='display: none'>\n";
    $current = $conn->query("SELECT count(1) AS current FROM query WHERE owner = $userid")->fetch(PDO::FETCH_ASSOC)['current'];
    $limit = ($level == 3) ? 20 : 5;
    if ($current < $limit) {
      echo "  <a onclick='saveQuery();'>Save</a>\n";
    } else {
      echo "  <span class=disabledmenu>Can't save: at limit of $limit</span>\n";
    }

    $shared = $conn->query("SELECT count(1) AS shared FROM query_share WHERE user_id = $userid")->fetch(PDO::FETCH_ASSOC)['shared'];
    navLink ('Load', "http:askloadquery$type.php?sessionkey=$sessionEncoded&level=$level&type=$type", $current + $shared);
    if ($current) {
      echo "<a href='http:asksharequery$type.php?sessionkey=$sessionEncoded&level=$level&type=$type' target='_blank'>Share</a>
      <a href='http:askdeletequery$type.php?sessionkey=$sessionEncoded&level=$level&type=$type' target='_blank'>Delete</a>
      <a href='http:askrenamequery$type.php?sessionkey=$sessionEncoded&level=$level&type=$type' target='_blank'>Rename</a>\n";
    }
    else {
      echo "    <span class=disabledmenu>Share</span>
          <span class=disabledmenu>Delete</span>
          <span class=disabledmenu>Rename</span>\n";
    }
  echo "   </div>\n";
  }
  echo "<button class='dropdown-btn' id='nav-dd'>Navigation
      <span id=nav-arrow>&#9662;</span>
    </button>
    <div class='dropdown-container' style='display: none'>\n";
  $security = "sessionkey={$_GET['sessionkey']}&level=$level&type=$type";
  if ($advanced) {
  	echo "<A id=basic href='index$type.php?simple=on&$security'>Basic search</A>\n";
  } else if ($level> 0) {
    echo "<A id=advanced href='index$type.php?$security'>Advanced search</A>\n";
  }
  $security = "?sessionkey=$sessionkey&level=$level";
  // Links to other versions of the project, based on permissions
  foreach (array (array ('id' => 'back', 'minlev' => 2, 'name' => 'Previous'),
      array ('id' => '', 'minlev' => 0, 'name' => 'Current'),
      array ('id' => 'beta', 'minlev' => 2, 'name' => 'Beta'),
      array ('id' => 'dev', 'minlev' => 3, 'name' => 'Development')) as $typeinfo) {
    if ($level >= $typeinfo['minlev']) {
      navLink ($typeinfo['name'], "index{$typeinfo['id']}.php$security&type={$typeinfo['id']}", $type != $typeinfo ['id']);
    }
  }
  if ($level > 0) {
    include "thirdparty$type.php";
    foreach (thirdParty::list() as $thirdParty) {
      $helper = new $thirdParty ();
      if ($helper->allowed()) {
        navLink ($helper->name(), $helper->link(), $helper->enabled());
      }
    }
  }

  echo "<a href='http:logout.php?sessionkey=$sessionEncoded'>Log out</a>
    </div>
  </div>\n";
}

// Wizard stuff
echo "<script>
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
	echo $classname::getMoreCode () . "\n";
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

deleteMode = false;
function removeConsMore (thisOption) {
  deleteMode = true; // Deletion code may need to know whether this is a hard delete (whole row) or a soft delete (from changing options)
  $delcode
  removeChildren (thisOption, '$fieldlist');
  deleteMode = false;
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
  echo "} // end validateConstraint

  function validateCorpus (thisCorpus) {\n";
    // Generate stuff from classes
    foreach ($corpusObjects as $thisCorpus => $corpusObject) {
      if ($code = $corpusObject->getValidateCorpusCode ()) {
        echo "if (thisCorpus == $thisCorpus) {
          $code
        }\n";
      }
		}
  echo "} // end validateCorpus\n";
// end main

function navLink ($name, $link, $enabled) {
  if ($enabled) {
    Echo "<A HREF='$link' target='_blank'>$name</A>\n";
  } else {
    Echo "<span class=disabledmenu>$name</span>\n";
  }
}

function inputCheckbox ($name) {
  $check = getCheckbox ($name) ? 'checked' : '';
  return "<input type=checkbox id=$name name=$name $check />";
}

?>
// This needs to be at the end, after controls have been created
var modal = document.getElementById('wizard');

// When the user clicks anywhere outside of the wizard, close it
window.onclick = function(event) {
  if (event.target == modal) {
		closeWizard ();
  }
}

dropInit ();
</script>
