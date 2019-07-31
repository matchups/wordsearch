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
  echo "if (theForm['$key'] !== undefined) {\n";
  echo "for (count = theForm['$key'].value; count > 0; --count) {\n";
  echo "removeConstraint$corpus(count);\n";
  echo "}\n";
  echo "theForm['$key'].value=0;\n";
  echo "}\n";
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
echo "onclick=\"window.location.href = 'http:logout.php?sessionkey=$sessionkey'\" />";
?>
<script>
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
