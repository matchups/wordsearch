<?php
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

echo "</script>\n";
?>
