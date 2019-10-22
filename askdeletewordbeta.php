<?php
$type = $_GET['type']; // beta, dev, etc.
include "utility" . $type . ".php";
echo "<HTML>
<HEAD>
" . scriptStyleRefs (true, false, false) . "
	<TITLE>
	Delete Word from List
	</TITLE>
</HEAD>
<BODY>
	<H2>Delete Word from List</H2>\n";
try {
	$conn = OpenConnection (false);
	if ($code = securityCheck ($level, $userid, $sessionid)) {
		throw new Exception ("Unable to access page; code $code");
	}
	echo "<form name='delete' method='get' onsubmit='return validateForm()' action='dodeleteword$type.php'><BR>
		Word list to modify<BR>
		<select name='list' id=list onchange='listChange()'>\n";
	$result = SQLQuery($conn, "SELECT id, name FROM corpus WHERE owner = $userid ORDER BY name");
	$more = '<BR>Word to delete: ';
	$first = true;
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$corpus = $row['id'];
		echo "<option value=$corpus>{$row['name']}</option>\n";
		// $more = $more . "<span id=wordspan$corpus style='display: none'><input type='text' name=word$corpus id=word$corpus class=word$corpus /></span>
		$style = $first ? 'inline' : 'none';
		$more = $more . "
				<div class='typeahead__container'>
	        <div class='typeahead__field'>
            <div class='typeahead__query'>
							<input type='text' name=word{$corpus}[query] id=word$corpus class=word$corpus style='display: $style' />
						</div>
					</div>
				</div>
		<script>
		$.typeahead({
		    dynamic: true,
				input: '.word$corpus',
		    delay: 500,
		    source: {
		      ajax: {
		        url: 'wordsuggest{$_GET['type']}.php',
		        data: {
		           query: '{{query}}',
							 corpus: '$corpus'
		       },
		       path: 'data'
		      }
		    }
		});
		\n";
		if ($first) {
			$more = $more . "currentCorpus = $corpus;\n";
			$first = false;
		}
		$more = $more . "</script>\n";
	}
	echo "</select>
		<input type=hidden name=sessionkey value='{$_GET['sessionkey']}'>
		<input type=hidden name=level value='$level'>
		<input type=hidden name=type value='$type'>
		<input type=hidden name=listname id=listname>
		$more<BR>
		<input type='submit'>
		</form>\n";
}
catch(Exception $e) {
	echo "<font color=red>" . $e->getMessage() . "</font>";
} // end main code block

echo "
<script>
function validateForm () {
	var ctrl = document.getElementById('list');
	var listname = ctrl.options[ctrl.selectedIndex].text;
	document.getElementById('listname').value = listname;
	return true;
}

function listChange () {
	var corpus = document.getElementById('list').value;
	document.getElementById('word' + currentCorpus).style.display = 'none';
	document.getElementById('word' + corpus).style.display = 'inline';
	currentCorpus = corpus;
}
</script>
</BODY>";
// End of main script
?>
