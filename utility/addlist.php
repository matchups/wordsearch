<HTML>
<HEAD>
<?php
include 'addone.php';

function deleteCorpus ($conn, $corpus) {
    foreach (array ('word_six', 'word_position', 'word_pair') as $table) {
	$conn->exec ("DELETE FROM $table WHERE word_id IN (SELECT word_entry.id FROM word_entry " .
		    "INNER JOIN entry ON word_entry.entry_id = entry.id " .
		    "WHERE word_entry.word_id = $table.word_id AND entry.corpus_id='$corpus') AND " .
		"NOT EXISTS (SELECT * FROM word_entry " .
		    "INNER JOIN entry ON word_entry.entry_id = entry.id " .
		    "WHERE word_entry.word_id = $table.word_id AND entry.corpus_id<>'$corpus')");
    }

    $conn->exec ("DELETE FROM word_entry WHERE entry_id IN (SELECT id FROM entry WHERE corpus_id='$corpus')");

    $conn->exec ("DELETE FROM entry WHERE corpus_id='$corpus'");
}
?>
</HEAD>
<BODY>
<?php

try {
    $corpus = $_GET['corpus'];
    $conn = openConn ($corpus);
    if (isset ($_GET['filename'])) {
   	$filename = $_GET['filename'];
	if (isset ($_GET['delete'])) {
   	    if ($_GET['delete'] == 'Y') {
		deleteCorpus ($conn, $corpus);
	    }
	}
	$filetext = file_get_contents($filename);
	$oneword = "";
	$bad = "";
	$upperascii =	str_split (str_replace (' ', '',
			".. ........S. 1.Z....... ....s.1.zy .......... a......... ......o... ..AAAAAA2C" .
			"EEEEIIIIDN OOOOOxOUUU UY34aaaaaa 2ceeeeiiii onooooo.ou uuuy3y"));
	foreach ($upperascii as &$char) {
		$char = str_replace (array ('.', '1', '2', '3', '4'), array ('', 'oe', 'ae', 'th', 'ss'), $char);
	} 
	$twobyte = ' [1,1]a [1,3]A [1,7]c [1,16]D [1,17]D [1,25]E [1,66]L [1,76]O [1,77]o [1,89]r [1,91]s [1,97]s [1,107]u [1,126]z [1,161]o ' .
		"[2,187]' " .
		'[30,161]a [30,163]a [30,165]a [30,169]a [30,191]e [30,197]e [30,199]e [30,205]o [30,209]o [30,227]o [30,229]u [30,233]u ' .
		'[32,19]- ' .
		'[34,18]- [34,239]... ';
	for ($here = 2; $here < strlen ($filetext); $here += 2) {
	    $chara = substr ($filetext, $here + 1, 1);
	    $charb = substr ($filetext, $here, 1);
	    $orda = ord ($chara);
	    $ordb = ord ($charb);
	    if ($orda > 0) {
		$unicode = "[$orda,$ordb]";
		if (($index = strpos ($twobyte, $unicode)) > 0) {
		    // Get characters after matching text up to, but not including, space
		    $oneword = $oneword . explode (' ', substr ($twobyte, $index + strlen ($unicode)))[0];
		} else {
		    $bad = $bad . $unicode;
		}
	    } else if ($ordb == 13) {
		// skip <ret>
	    } else if ($ordb > 127) {
		$oneword = $oneword . $upperascii [$ordb - 128];
	    } else if ($ordb == 10) {
		if ($bad > '') {
		    echo "<BR>Unable to find <font color=red>$bad</font> in $oneword!<BR>";
		    $bad = "";
		} else {
   	 	    newEntry ($conn, $oneword, $corpus);
		}
		$oneword = "";
	    } else {
		$oneword = $oneword . $charb;
	    }
        }
    } else {
   	Echo "No file name";
    }
}

catch(PDOException $e)
    {
    echo "SQL failed: " . $e->getMessage();
    }
?>
<P>
</form>
</BODY>

