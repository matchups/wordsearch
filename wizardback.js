// Close the wizard.  If saveFlag, use the values to populate this constraint's query field.
function closeWizard (saveFlag) {
	thisOption = document.getElementById("wizoption").value;
	if (saveFlag) {
		// logic depends on type
		if (document.getElementById("rcharmatch" + thisOption).checked) {
			if (document.getElementById("wrmatchless").checked) {
				newValue = "<";
			} else if (document.getElementById("wrmatchequal").checked) {
				newValue = "=";
			} else if (document.getElementById("wrmatchgreater").checked) {
				newValue = ">";
			}
			newValue = document.getElementById("wnmatchleft").value + newValue + document.getElementById("wnmatchright").value;
			offset = document.getElementById("wnoffset").value;
			if (offset < 0 || offset > 0) {
				if (/^[0-9]+$/.test (offset)) {
					offset = "+" + offset;
				}
			newValue += "^" + offset;
			}
		} else if (document.getElementById("rweights" + thisOption).checked) {
			newValue = document.getElementById("wnweightleft").value;
			if (!document.getElementById("wrwtskip").checked) {
				if (document.getElementById("wrwtone").checked) {
					newValue += "+";
				} else {
					endthing = document.getElementById("wnweightright").value;
					if (document.getElementById("wrwtend").checked) {
						newValue += "-" + endthing;
					} else {
						newValue += "+" + endthing;
					}
				}
			}
			if (document.getElementById("wtrrell").checked) {
				newValue += "<";
			} else if (document.getElementById("wtrrele").checked) {
				newValue += "=";
			} else if (document.getElementById("wtrrelg").checked) {
				newValue += ">";
			}
			newValue += document.getElementById("wtconst").value;
		}
		document.getElementById("query" + thisOption).value = newValue;
	}

	// delete wizard fields
	wizForm = document.forms["popwiz"];
	document.getElementById("wizfields").value.split(" ").forEach(function (fieldName) {
			wizForm.removeChild(document.getElementById(fieldName));
	});

	// finally, hide wizard
	modal.style.display = "none";
}

function wizInsert (newOption) {
	var here = document.getElementById("wizfields");
	here.parentNode.insertBefore (newOption, here);
	if (fieldlist > "") {
		fieldlist += " ";
	}
	fieldlist += newOption.id;
}

// Open the wizard and populate its form
function openWizard (thisOption) {
	document.getElementById("wizoption").value = thisOption; // So the wizard knows where to put the answer when he closes

	if (theForm["rweights" + thisOption].checked) {
		fieldlist = "";
		// Set up wizard for Weights
		newOption = document.createElement("span");
		newOption.id = "wtweightleft";
		newOption.innerHTML = "Weight multipliers of letters at beginning of word (e.g., 1133 for third and fourth to be multiplied by three): ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "number";
		newOption.name = "wnweightleft";
		newOption.id = "wnweightleft";
		newOption.required = true;
		wizInsert (newOption);

		newOption = document.createElement ("br");
		newOption.id = "wizbr1";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtwtintro";
		newOption.innerHTML = "What about the remaining characters in the word?";
		wizInsert (newOption);

		newOption = document.createElement ("br");
		newOption.id = "wizbr2";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wrwtrest";
		newOption.id = "wrwtskip";
		newOption.checked = true;
		newOption.setAttribute('onclick','wizRadioClicked()');
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtwtskip";
		newOption.innerHTML = " skip ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wrwtrest";
		newOption.id = "wrwtone";
		newOption.setAttribute('onclick','wizRadioClicked()');
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtwtone";
		newOption.innerHTML = " use base weights ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wrwtrest";
		newOption.id = "wrwtend";
		newOption.setAttribute('onclick','wizRadioClicked()');
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtwtend";
		newOption.innerHTML = " skip until specified characters at end ";
		wizInsert (newOption);

		newOption = document.createElement ("br");
		newOption.id = "wizbr3";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wrwtrest";
		newOption.id = "wrwtmidend";
		newOption.setAttribute('onclick','wizRadioClicked()');
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtmidend";
		newOption.innerHTML = " use base weights until specified characters at end ";
		wizInsert (newOption);

		newOption = document.createElement ("br");
		newOption.id = "wizbr4";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtweightright";
		newOption.innerHTML = "Weight multipliers of letters at end of word: ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "number";
		newOption.name = "wnweightright";
		newOption.id = "wnweightright";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wttrel";
		newOption.innerHTML = "<BR>Should the total weight be...";
		wizInsert (newOption);

		newOption = document.createElement ("br");
		newOption.id = "wizbr5";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wtrel";
		newOption.id = "wtrrell";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wttrell";
		newOption.innerHTML = " less than ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wtrel";
		newOption.id = "wtrrele";
		newOption.checked = true;
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wttrele";
		newOption.innerHTML = " equal to ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wtrel";
		newOption.id = "wtrrelg";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wttrelg";
		newOption.innerHTML = " greater than ";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wttconst";
		newOption.innerHTML = "<BR>Constant value for comparison: ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "number";
		newOption.name = "wtconst";
		newOption.id = "wtconst";
		newOption.required = true;
		wizInsert (newOption);

		wizRadioClicked();
	} else if (theForm["rcharmatch" + thisOption].checked) {
		// Set up wizard for character matching
		fieldlist = "";
		newOption = document.createElement("span");
		newOption.id = "wtmatchleft";
		newOption.innerHTML = "Character position to start with (positive to count from beginning or negative to count from end): ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "number";
		newOption.name = "wnmatchleft";
		newOption.id = "wnmatchleft";
		wizInsert (newOption);

		newOption = document.createElement ("br");
		newOption.id = "wizbr1";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wrmatchrel";
		newOption.id = "wrmatchless";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtless";
		newOption.innerHTML = " < less than ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wrmatchrel";
		newOption.id = "wrmatchequal";
		newOption.checked = true;
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtequal";
		newOption.innerHTML = " = equals ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "radio";
		newOption.name = "wrmatchrel";
		newOption.id = "wrmatchgreater";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtgreater";
		newOption.innerHTML = " > greater than ";
		wizInsert (newOption);

		newOption = document.createElement ("br");
		newOption.id = "wizbr3";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtmatchright";
		newOption.innerHTML = "Character position to compare against: ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "number";
		newOption.name = "wnmatchright";
		newOption.id = "wnmatchright";
		wizInsert (newOption);

		newOption = document.createElement ("br");
		newOption.id = "wizbr2";
		wizInsert (newOption);

		newOption = document.createElement("span");
		newOption.id = "wtoffset";
		newOption.innerHTML = "Offset (optional); for example, 2 if you want F to match D or -2 for R to match T: ";
		wizInsert (newOption);

		newOption = document.createElement("input");
		newOption.type = "number";
		newOption.name = "wnoffset";
		newOption.id = "wnoffset";
		wizInsert (newOption);
	}
	document.getElementById("wizfields").value = fieldlist;

	// Tell browser to display the wizard in foreground
	document.getElementById("wizard").style.display = "block";
	return false;
}

// When a radio button is selected for Weights, the right side multipliers are enabled only if the selection
// is compatible with that.
function wizRadioClicked() {
		allowEnd = (document.getElementById("wrwtend").checked || document.getElementById("wrwtmidend").checked);
		document.getElementById("wnweightright").disabled = !allowEnd;
		if (allowEnd) {
			textColor = "black";
		} else {
			textColor = "gray";
		}
		document.getElementById("wtweightright").style = "color:" + textColor;
}
