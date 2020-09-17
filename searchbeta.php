<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

$conn = openConnection (false); // get into a global for subsequent use

if ($code = securityCheck ($level, $userid, $sessionid)) {
	header("Location: http://www.8wheels.org/wordsearch/index.html?code=$code"); // No valid session, so ask user to sign on
		// and provide a general indication of the error type for our use
	exit();
}

echo "<HTML>
<HEAD>\n";
echo scriptStyleRefs (true, true, true);
include "cons$type.php";
include "corpus$type.php";

$pattern = $_GET['pattern'];
$version = $_GET['version'];
echo "
<TITLE>
$pattern - Word Search $type $version
</TITLE>
</HEAD>\n";

$time['top'] = microtime();
$time['top.int'] = microtime(true);
include "results" . $type . ".php";
include "parse" . $type . ".php";
// Initialize cache (used in fetchUrl)
$cache['url'] = '';
$cache['body'] = '';

try {
	echo '<BODY onload="reloadQuery();">';
	echo "<H2>Word Search $type $version Results: <span class='specs'>$pattern";

  try {
		$sql = parseQuery ($pattern, $consObjects, $corpusObjects);

		// Optimize and run query
		$rows = 0;
		$sql = refineQuery ($sql, $rows);
		$explain = getCheckbox ('explain');
		$result = '';
		if ($explain) {
			$sql = 'EXPLAIN ' . $sql;
		} else if ($level < 3) {
			if ($rows == 0) {
				$rows = getCost ($sql);
			}
			if (($level < 2 && $rows > 80000) || $rows > 1000000) {
				$result = "Your query may take too long to run.  Please add more letters.";
			}
		}
	}
	catch(Exception $e) {
  	$result = $e->getMessage();
	}

	echo "</span></H2>";
	$time['beforequery'] = microtime();
	if (getCheckbox ('norun')) {
		$result = "Query loaded.";
		$explain = false;
	}
	if ($result == '') {
		$result = SQLQuery ($conn, $sql);
		comment ("Got " . $result->rowCount() . " rows");
	}
	$time['afterquery'] = microtime();

	if ($explain) {
		echo "<div class='code'>$sql</div><br>";
		showExplain ($result);
	} else {
		// Loop through words and display results
		comment ($sql);
		$ret = showResults ($result, $consObjects, $corpusObjects);
		comment ($ret);
		if ($ret['code'] == 'none') {
			echo "No matches found.<BR>";
		}
		if ($ret ['code'] == 'limit') {
			$url = preg_replace ('/&from=.*$/', '', $_SERVER['REQUEST_URI']) . "&from={$ret['restart']}";
			$url = substr ($url, 12); // remove /wordsearch/
			if ($ret ['subcode'] == 'T') {
				echo "<P>Request timed out.";
			} else {
				echo "<P>Page limit of {$_GET['pagelen']} reached.";
			}
			echo "  Select <A HREF=http://www.alfwords.com/$url>more</A> to see additional results.  ";
			if (isset ($ret['save'])) {
				echo "You will be able to save the results once all results have been displayed.";
			}
			echo "<BR>\n";
		}
		$time['end'] = microtime();
		foreach ($time as $key => $value) {
			if (strpos ($key, '.int')) {
				continue;
			}
			if ($key <> 'top') {
		 		comment ("$prevkey-$key=" . timeDiff ($previous, $value));
			}
			$previous = $value;
			$prevkey = $key;
		}
	}
}
catch(PDOException $e) {
	errorMessage ("SQL failed: {$GLOBALS['lastSQL']}... " . $e->getMessage());
} // end main code block
catch(Exception $e) {
	errorMessage ($e->getMessage());
} // end main code block

// Some stuff outside try/catch block so the rest of the page won't suffer.
echo "<P>";

// Display form to allow user to edit and resubmit query
include "form$type.php";
buildReloadQuery ($consObjects);

echo '</BODY>';
// End of main script

function buildReloadQuery ($consObjects) {
	echo "<script>\n";
	echo "// This script is run on load of the results page to modify the skeleton form to match the\n";
	echo "// original query.  It is built dynamically as part of the search process.\n";
	echo "function reloadQuery() {\n";
	echo "// Dynamically add subthings & populate fields\n";
	echo 'theForm = document.forms["search"];' . "\n";
	// If the checkbox is set in the query, set it in the form
	$fieldlist = 'anyorder single phrase';
	if ( $_GET['level'] > 0) {
		if ($advanced = (($_GET['simple'] ?? '') != 'on')) {
			$fieldlist = $fieldlist . ' repeat whole' . $_GET["morecbx"];
		}
	}

	if ($_GET['type'] == 'dev') {
		$fieldlist = $fieldlist . ' explain';
	}

	foreach (explode (' ', $fieldlist) as $name) {
		// use explode to get the first _ piece so that, foe example, c3cap_dc --> c3cap
		$checked = getCheckbox (explode ('_', $name)[0]) ? 'true' : 'false';
		echo "theForm['$name'].checked = $checked;\n";
	}

	// For fields where the user can type, copy the values across
	foreach (explode (' ', 'minlen maxlen pattern') as $name) {
		$value = $_GET[$name];
		echo "theForm['$name'].value = '$value';\n";
	}

	// Initialize these values; addOption will modify them to what they should be.
	echo "theForm['count'].value = 1;\n";
	echo "optionNumber=1;\n";
	// Loop through additional constraints and set those up
	$newCount['F']=1; // because we have a '2' offset for some reason
	foreach ($consObjects as $thisConsObj) {
		$parentID = $thisConsObj->parentID();
		if (!isset ($newCount[$parentID])) {
			$newCount[$parentID] = 0;
		}
		$thisConsObj->rebuildForm(++$newCount[$parentID]);
	}
	echo "mainChange();
	updateSortChoices();
	initializeHighlight();
	updateShowOutput();
	}
	</script>\n";
}

// Force an index on bank if possible and no other index is being used
function refineQuery ($sql, &$rows) {
	if (strpos ($sql, 'PW.bank') !== false) {
		$result = openConnection(false)->query("EXPLAIN $sql")->fetch(PDO::FETCH_ASSOC);
		if (isset ($result['key'])) {// key used by SQL for outer loop
			$rows = $result['rows'];
		} else {
			$sql = str_replace ('words PW', 'words PW FORCE INDEX (wbankidx)', $sql);
		}
	}

	return str_replace (array ("\n", "\t"), ' ', $sql);
}

function getCost ($sql) {
	return (openConnection(false)->query("EXPLAIN $sql")->fetch(PDO::FETCH_ASSOC))['rows'];
}
?>
