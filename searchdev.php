<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";

// Need to check for a valid session before creating *any* output
$valid = false;
$code = '1';
if (isset ($_GET['sessionkey'])  &&  isset ($_GET['level'])) { // make sure session info is passed to us
	$session = $_GET['sessionkey'];
	try {
		$conn = openConnection (false);
		$getLevel = $_GET ['level'];
		if ($getLevel > 0) {
			$start = "user.level";
			$middle = "INNER JOIN user ON user.id = session.user_id";
			$end = '';
		} else {
			$start = '0 AS level';
			$middle = '';
			$end = " AND session.user_id = 0";
		}
		$sql = "SELECT $start, ip_address FROM session $middle WHERE session_key = '$session' AND status = 'A' $end";
		$result = $conn->query($sql);
		if ($result->rowCount() > 0) { // make sure it is an active session
			$row = $result->fetch(PDO::FETCH_ASSOC);
			$level = $row['level'];
			if ($level == $getLevel) {
				if ($level < 2 && $type > '') {
					$code = '4'; // basic not authorized for anything other than base
				} else if ($level < 3 && $type == 'dev') {
					$code = '5'; // only pro authorized for dev
				} else if ($level == 0 && isset($_GET['query2'])) {
					$code = '6'; // guest can't use additional criteria
				} else if (explode ('|', $row['ip_address'])[0] == $_SERVER['REMOTE_ADDR']) {
					$valid = true;
				} else {
					$code = '7';
				}
			} else {
				$code = '2'; // URL lies about the user's level
			}
		} else {
			$code = '3';
		}
	}
	catch(PDOException $e) {
		$code = $e->getCode ();
	}
}

if (!$valid) {
	header("Location: http://www.8wheels.org/wordsearch/index.html?code=$code"); // No valid session, so ask user to sign on
		// and provide a general indication of the error type for our use
	exit();
}

echo "<HTML>
<HEAD>
<script src='//code.jquery.com/jquery-2.1.4.min.js'></script>
	<script src='//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
	<script src='//netsh.pp.ua/upwork-demo/1/js/typeahead.js'></script>
	<script src='utility$type.js'></script>";
include "cons$type.php";
include "corpus$type.php";

$pattern = $_GET['pattern'];
$version = $_GET['version'];
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>
<link rel='stylesheet' href='styles.css'>
<TITLE>
$pattern - Word Search $type $version
</TITLE>
</HEAD>\n";

$time['top'] = microtime();
include "results" . $type . ".php";
include "parse" . $type . ".php";
// Initialize cache (used in fetchUrl)
$cache['url'] = '';
$cache['body'] = '';

try {
	echo '<BODY onload="reloadQuery();">';
	echo "<H2>Word Search $type $version Results: <span class='specs'>$pattern";

	// Connect briefly in write mode to update the session
	openConnection (true)->exec ("UPDATE session SET last_active = UTC_TIMESTAMP() WHERE session_key = '$session'");

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
				$rows = getWidth ($sql);
			}
			if (($level < 2 && $rows > 10000) || $rows > 100000) {
				$result = "Your query may take too long to run.  Please add more letters.";
			}
		}
	}
	catch(Exception $e) {
  	$result = $e->getMessage();
	}

	echo "</span></H2>";
	$time['beforequery'] = microtime();
	if ($result == '') {
		$result = $conn->query($sql);
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
		if (preg_match ("/^time\^(.*)$/", $ret, $matches)) {
			$url = preg_replace ('/&from=.*$/', '', $_SERVER['REQUEST_URI']) . "&from={$matches[1]}";
			$url = substr ($url, 12); // remove /wordsearch/
			echo "<P>Request timed out.  Select <A HREF=http://www.alfwords.com/$url>more</A> to see additional results.<BR>";
		}
		$time['end'] = microtime();
		foreach ($time as $key => $value) {
			if ($key <> 'top') {
		 		comment ("$prevkey-$key=" . timeDiff ($previous, $value));
			}
			$previous = $value;
			$prevkey = $key;
		}
	}
}
catch(PDOException $e) {
	errorMessage ("SQL failed: $sql... " . $e->getMessage());
} // end main code block

// Some stuff outside try/catch block so the rest of the page won't suffer.
echo "<P>";

// Display form to allow user to edit and resubmit query
include "form$type.php";
preserveInfo ($type, $version);
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
		$advanced = true;
		if (isset($_GET['simple'])) {
			if ($_GET['simple'] == 'on') {
				$advanced = false;
			}
		}
		if ($advanced) {
			$fieldlist = $fieldlist . ' repeat whole' . $_GET["morecbx"];
		}
	}

	if ($_GET['type'] == 'dev') {
		$fieldlist = $fieldlist . ' explain';
	}

	foreach (explode (' ', $fieldlist) as $name) {
		if ($_GET[$name] == 'on') {
			$checked = 'true';
		} else {
			$checked = 'false';
		}
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
	echo "mainChange();\n";
	echo "}\n";
	echo "</script>\n";
}

// Force an index on bank if possible and no other index is being used
function refineQuery ($sql, &$rows) {
	if (strpos ($sql, 'PW.bank') !== false) {
		$result = $GLOBALS['conn']->query("EXPLAIN $sql")->fetch(PDO::FETCH_ASSOC);
		$key = $result['key']; // key used by SQL for outer loop
		if (isset ($key)) {
			$rows = $result['rows'];
		} else {
			$sql = str_replace ('words PW', 'words PW FORCE INDEX (wbankidx)', $sql);
		}
	}
	return $sql;
}

function getWidth ($sql) {
	return ($GLOBALS['conn']->query("EXPLAIN $sql")->fetch(PDO::FETCH_ASSOC))['rows'];
}

function timeDiff ($begin, $end) {
	$beginArray = explode (' ', $begin);
	$endArray = explode (' ', $end);
	return intval ((($endArray[1] - $beginArray[1]) + ($endArray[0] - $beginArray[0])) * 1000 + 0.5);
}
?>
