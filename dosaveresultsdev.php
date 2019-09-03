<?php
if (isset ($_POST['type'])) {
	$_GET = $_POST;
}
if ($upload = ($_GET['source'] == 'upload')) {
	$sourcedesc = 'Upload';
} else {
	$sourcedesc = 'Save Results as';
}
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";
include 'addmain.php';

echo "<HTML>
<HEAD>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link rel='stylesheet' href='styles.css'>
	<TITLE>
	$sourcedesc Word List
	Save Results
	</TITLE>
</HEAD>
<BODY>
	<H2>$sourcedesc Word List</H2>\n";

try {
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to save results; code $code");
	}

  $conn = openConnection (true);
	if (($listname = $_GET['listname'] ?? '') == '') {
		throw new Exception ("List name is required");
	}
	$stmt = $conn->prepare("SELECT id FROM corpus WHERE name = ? AND owner = ?");
	$stmt->execute (array ($listname, $userid));
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$corpusid = $row['id'];
	} else {
		$corpusid = '';
	}

	$connw = openConnection (true);
	switch ($savetype = $_GET['savetype'] ?? 'missing') {
		case 'new':
			if ($corpusid) {
				throw new Exception ("List $listname already exists.");
			}
			$corpusid = sqlInsert ($conn, "INSERT corpus (name, owner) VALUES ('$listname', $userid)");
			break;
		case 'over':
			deleteAll ($connw, $corpusid);
		case 'add':
			if (!$corpusid) {
				throw new Exception ("Can't find existing list $listname.");
			}
			break;
		default:
			throw New Exception ("Invalid save type: $savetype");
	}

	// slurp words
	if ($upload) {
		$fileInfo = $_FILES["uploadfile"];
		$target_dir = "uploads/";
		$targetFile = "$target_dir$sessionid.txt";

		// Check file size
		$maxsize = 1024; // will depend on level and what you already have
		if ($fileInfo["size"] > ($maxsize * 1024)) {
	    throw new Exception ("Maximum file size is {$maxsize}K");
		}

		// Allow only text files
		if (strtolower (pathinfo (basename ($fileInfo["name"]), PATHINFO_EXTENSION)) != "txt") {
			echo "name={$fileInfo["name"]}  basename=" . basename ($fileInfo["name"]) . "  pathinfo=" .
					pathinfo (basename ($fileInfo["name"]), PATHINFO_EXTENSION);
	    throw new Exception ("Only .txt files may be uploaded");
		}

		// slurp it
		if (!move_uploaded_file($fileInfo["tmp_name"], $targetFile)) {
		  throw new Exception ("Error uploading file");
		}
		comment ("Uploaded {$fileInfo["name"]} --> $targetFile");
		foreach (explode (chr(10), str_replace (chr(13), '', strtolower (file_get_contents($targetFile)))) as $word) {
			if (preg_match ('/[a-z]{3}/', $word)) {
				$entries [$word] = '';
			}
		}
	} else { // upload --> results
		$sql = "SELECT entry, corpus_id FROM session_words WHERE session_id = '$sessionid'";
		$result = $conn->query($sql);
		if ($result->rowCount() > 0) { // make sure it is an active session
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$entries [$row['entry']] = '';
			}
		} else {
			throw new Exception ("No saved words for list");
		}
	} // end results

	// add words
	$helper = new loadHelper ();
	$counter = 0;
	foreach ($entries as $entry => $dummy) {
		if (isset (newEntry ($connw, $entry, '', $corpusid, $helper)['id'])) {
			$counter++;
		}
	}
echo "$counter new entries added to list $listname<P>\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block
unset ($connw);

echo '</BODY>';
// End of main script
?>
