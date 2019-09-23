<?php
class corpus {
	protected $corpus;
	protected $urlPattern;
	protected $name;
  protected $flags;
	protected $flagCorpus;
	protected $owner;

	public function __construct ($corpus) {
		$this->corpus = $corpus;
		$conn = openConnection (false);

		// get name, URL
		$row = SQLQuery ($conn, "SELECT name, url, owner, like_id FROM corpus WHERE id = $corpus ORDER by name")->fetch(PDO::FETCH_ASSOC);
		$this->urlpattern = $row['url'];
		$this->name = $row['name'];
		$this->owner = $row['owner'];

		// get flags
		if ($row['like_id'] > 0) {
			$this->flagCorpus = $row['like_id'];
		} else {
			$this->flagCorpus = $corpus;
		}
		$flagCorpus = $this->flagCorpus;
		$result = SQLQuery ($conn, "SELECT letter, description FROM corpus_flag WHERE corpus_id = $flagCorpus");
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$this->flags [$row['letter']]=$row['description'];
		}
	} // end constructor

	public static function factory ($corpus) {
		$name = corpus::className ($corpus);
		return new $name($corpus);
	}

	private static function className ($corpus) {
		// Alas, PHP does not support ranges in switches
		if ($corpus == 1  ||  $corpus == 87) {
			$name = "Dev";
		} else if ($corpus == 2) {
			$name = "Wikipedia";
		} else if ($corpus == 3) {
			$name = "Wiktionary";
		} else if ($corpus > 87) {
			$result = SQLQuery (openConnection (false), "SELECT like_id FROM corpus WHERE id = $corpus");
			$row = $result->fetch(PDO::FETCH_ASSOC);
			if ($row ['like_id']) {
				return corpus::className ($row ['like_id']);
			}
			$name = 'User';
		} // Wiktionary TBD
		return "corpus$name";
	}

	public function answerLink ($entry) {
		if ($this->urlpattern != '') {
			return "<A target='_blank' HREF='" . $this->getURL ($entry) . "'>$entry</A>";
		} else {
			return $entry;
		}
	}

	protected function getURL ($entry) {
	  return str_replace ('@', urlencode ($entry), $this->urlpattern);
	}

  public function getCorpusNum () {
		return $this->corpus;
	}

	public function allowed () {
		if ($this->owner == '') {
			return true;
		}
		try {
			$conn = openConnection (false);
			$result = $conn->query("SELECT user_id FROM session WHERE session_key = '{$_GET['sessionkey']}'");
			if ($result->rowCount() > 0) { // make sure it is an active session
				$userid = $result->fetch(PDO::FETCH_ASSOC)['user_id'];
				if ($userid == $this->owner) {
					return true;
				}
				$corpus = $this->corpus;
				return $conn->query("SELECT 1 FROM corpus_share WHERE user_id = $userid AND corpus_id = $corpus AND display = 'S'")->rowCount() > 0;
			}
			comment ("No rows!");
		}
		catch (PDOException $e) {
			comment ("Can't figure allowed: " . $e->getMessage);
		}
		return false;
	}

	public function phrases () {
		return false;
	}

  public function form () {
		$checklist = '';
		$corpus = $this->corpus;
		echo "<label>$this->name: <input name=corpus$corpus id=corpus$corpus type=checkbox checked /></label>\n";
		$checklist = $checklist . ' corpus' . $corpus;
		foreach ($this->flags as $flag => $flagname) {
			$xname = "c{$corpus}flag$flag";
			echo "   &nbsp; &nbsp; <label>$flagname okay? <input name=$xname id=$xname type=checkbox /></label>\n";
			$checklist = $checklist . ' ' . $xname;
		}
		return $checklist . $this -> formExtra();
	}

  protected function formExtra () {
		return '';
	}

	public function builder (&$consObjects) {
		// Allows corpus to set up filtering, either via SQL or client processing
		// Base implementation calls a function on the corpus object for each row
		$count = $_GET["count$this->corpus"] ?? 0;
		for ($num = 1; $num <= $count; $num++) {
			$key = "{$this->corpus}_$num";
			if (isset ($_GET["query$key"])  &&
					($ccobject = $this -> buildOne ($_GET["query$key"], $_GET["radio$key"], getCheckbox ("not$key"), $num)) !== null) {
				$consObjects [$key] = $ccobject;
			}
		}
	}

	protected function buildOne ($spec, $radio, $not, $num) {
		errorMessage ("Must override corpus.buildOne ($spec, $radio, $num)");
	}

	protected function formAddRepeat () {
		$corpus = $this->corpus;
		$key = "count$corpus";
		$name = $this->name;
		Echo "&nbsp;&nbsp;<button type='button' id='add$corpus' onclick='addOption$corpus();return false;'>More</button><BR>
			<script>
			function addOption$corpus() {;
			// add a new constraint when user presses that button
			var theForm = document.getElementById('search');
			var corpusOptionNumber = ++(theForm['$key'].value);
			var here = theForm['add$corpus'];
			var myParent = here.parentNode;

			myParent.insertBefore (newSpan ('label{$corpus}_' + corpusOptionNumber, '&nbsp;&nbsp;$name#' + corpusOptionNumber + ': (not)'), here);
			myParent.insertBefore (newInput ('not{$corpus}_' + corpusOptionNumber, 'checkbox', ''), here);
			myParent.insertBefore (newInput ('query{$corpus}_' + corpusOptionNumber, 'text', 'R'), here);\n";

    $clickedCode = $this -> clickedCode ();

		$first = true;
		foreach ($this->optionButtonList() as $id => $description) {
			echo "var newOption = newRadio ('r{$corpus}_$id' + corpusOptionNumber, 'radio{$corpus}_' + corpusOptionNumber, '', '$id', '');\n";
			if (($lead = substr ($description, 0, 1)) == '!') {
				echo "newOption.disabled = true;\n";
				$description = substr ($description, 1);
			}
			if ($clickedCode) {
				echo "newOption.setAttribute('onclick','radioClicked$corpus(' + corpusOptionNumber + ')')\n";
			}
			if ($first) {
				echo "newOption.checked = true;\n";
				$first = false;
			}
			echo "myParent.insertBefore (newOption, here);\n";

			echo "newOption = newSpan ('t{$corpus}_$id' + corpusOptionNumber, ' $description ');\n";
			if ($lead == '!') {
				echo "newOption.class = 'disabled';\n";
			}
			echo "myParent.insertBefore (newOption, here);\n";
		}

		// Button to remove constraint
		Echo "		myParent.insertBefore (newButton ('delcons{$corpus}_' + corpusOptionNumber, 'Remove', 'removeConstraint$corpus(' + corpusOptionNumber + ')'), here);
			myParent.insertBefore (newBreak ('br{$corpus}_' + corpusOptionNumber), here);
			} // End addOption$corpus\n";

    if ($clickedCode) {
			echo "function radioClicked$corpus (thisOption) {
				var theForm = document.forms['search'];
				{$clickedCode['add']}
				} // end radioClicked$corpus

				function noSub$corpus (thisOption) {
				{$clickedCode['del']}
				} // end noSub$corpus\n";
		}

		echo "function removeConstraint$corpus (corpusOptionNumber) {\n";
		$fieldlist = "br{$corpus}_ delcons{$corpus}_ label{$corpus}_ not{$corpus}_ query{$corpus}_";
		foreach ($this->optionButtonList() as $id => $description) {
			$fieldlist = $fieldlist . " r{$corpus}_$id t{$corpus}_$id";
		}
		echo "removeChildrenCorpus (corpusOptionNumber, '$fieldlist');
			if (corpusOptionNumber == document.forms['search']['$key'].value) {
			  document.forms['search']['$key'].value--;
			}\n";
		if ($clickedCode) {
			echo "noSub$corpus (corpusOptionNumber);\n";
		}
		echo "} // end removeConstraint$corpus
		</script>
		<input type=hidden id='$key' name='$key' value='0' />\n";
	}

	function optionButtonList () {
		return array ();
	}

	public function requireWhole () {
		// Do we need to restrict to whole entries because we need to look at the linked pages?
		return ($_GET["count$this->corpus"] ?? 0) > 0;
	}

	function clickedCode () {
		return '';
	}

	public function getValidateCorpusCode () {
		return '';
	}

	public function getSpecialEntry (&$table) {
		if ($this->flagCorpus <> $this->corpus) {
			$table = $this->getEntryTable();
			$int = "WE{$this->corpus}";
			return " LEFT OUTER JOIN word_entry $int ON $int.word_id = PW.id " .
				"LEFT OUTER JOIN entry $table ON $table.id = $int.entry_id AND $table.corpus_id = " . $this->flagCorpus;
		}
		return "";
	}

	public function getEntryTable () {
		return ($this->flagCorpus <> $this->corpus) ? "ECP{$this->corpus}" : 'entry';
	}

} // end base corpus class

class corpusConstraint extends constraint {
  protected $corpusObject;

	public function __construct ($spec, $num, $not, $corpusObject) {
		$this->corpusObject = $corpusObject;
		parent::__construct ($spec, $num, $not);
	}

	function parentID () {
		return 'C' . $this->corpusObject->getCorpusNum();
	}

	function rebuildForm($realNumber) {
		$corpusNum = $this->corpusObject->getCorpusNum();
		echo "addOption$corpusNum();";
		if ($this->not) {
			echo "theForm['not{$corpusNum}_$realNumber'].checked = true;\n";
		}
		echo "theForm['query{$corpusNum}_$realNumber'].value = '" . addslashes ($this->spec) . "';\n";
		// Set the radio button corresponding to the selected option
		echo "theForm['r{$corpusNum}_" . $_GET["radio{$corpusNum}_$this->num"] . "$realNumber'].checked = true;\n";
	}
} // end class corpusConstraint

class corpusWikipedia extends corpus {
	function getURL ($entry) {
		return parent::getURL ($this->titleFix ($entry));
	}

	function titleFix ($entry) {
		return str_replace (array (' ', '"'), array ('_', ''), $entry);  // Space to underscore and remove quotes, per Wikipedia's private rules
	}

	function phrases () {
		return true;
	}

	function formExtra () {
		$this->formAddRepeat ();
		$this->formCatLookup ();
		return '';
	}

	function optionButtonList () {
		return array ('pattern' => 'pattern', 'regex' => 'regular expression', 'category' => 'category', 'size' => 'size');
	}

	protected function buildOne ($spec, $radio, $not, $num) {
		$classname = "ccWikipedia$radio";
		return new $classname ($spec, $num, $not, $this);
	}

	function clickedCode () {
		$corpus = $this->corpus;
		$options = array ('contains'=>'contains', 'desc'=>'include descendants', 'neither'=>'neither');
		$code = "
		notbox = document.getElementById('not{$corpus}_' + thisOption);
		if (theForm['r{$corpus}_category' + thisOption].checked) {
			if (theForm['rc{$corpus}_contains' + thisOption] === undefined) {
				var here = theForm['r{$corpus}_size' + thisOption];
				var myParent = here.parentNode;

				myParent.insertBefore (newSpan ('t{$corpus}_ob' + thisOption, ' ['), here);\n";

    $first = true;
		foreach ($options as $key => $text) {
			$code = $code . "	newOption = newRadio ('rc{$corpus}_$key' + thisOption, 'wc{$corpus}_type' + thisOption, '', '$key', '')\n";
			if ($first) {
				$code = $code . " newOption.checked = true;\n";
				$first = false;
			}
			$code = $code . " myParent.insertBefore (newOption, here);

			myParent.insertBefore (newSpan ('tc{$corpus}_$key' + thisOption,  ' $text '), here);\n";
		} // end foreach (hard to tell with all that quoted stuff!)

		$code = $code . "				myParent.insertBefore (newButton ('catlook{$corpus}_' + thisOption, 'Lookup',
			'categoryLookup(' + thisOption + ', $corpus)'), here);
					myParent.insertBefore (newSpan ('tclsp{$corpus}_' + thisOption, ']&nbsp;&nbsp;'), here);

					if (notbox.checked) {
						notbox.checked = false;
						alert ('The \'not\' checkbox cannot be used in conjunction with category searches.');
					}
					notbox.disabled = true;
				}
			} else {
				notbox.disabled = false;
				noSub{$corpus} (thisOption);
			}";
		$ret ['add'] = $code;

		$fieldlist = "t{$corpus}_ob catlook{$corpus}_ tclsp{$corpus}_";
		foreach ($options as $key => $text) {
			$fieldlist = $fieldlist . " rc{$corpus}_$key tc{$corpus}_$key";
		}
		$code = "removeChildrenCorpus (thisOption, '$fieldlist');";
		$ret ['del'] = $code;
		return $ret;
	} // end $clickedCode

  // Use wizard to allow user to look up category
	protected function formCatLookup() {
		$corpus = $this -> corpus;
		$flagCorpus = $this -> flagCorpus;
		echoUnique ("\n<script>
			function categoryLookup (thisOption, thisCorpus) {
				categoryOption = thisOption; // global
				currentCorpus = thisCorpus; // global
				document.getElementById('catlookup').style.display = 'block';
				document.getElementById('category' + currentCorpus).style.display = 'block';
			}
			function closeCatWizard (saveFlag) {
				if (saveFlag) {
					document.getElementById('query' + currentCorpus + '_' + categoryOption).value = document.getElementById('category' + currentCorpus).value;
				}
				// Hide wizard
				document.getElementById('catlookup').style.display = 'none';
				document.getElementById('category' + currentCorpus).style.display = 'none';
			}
			</script>\n");
		echo "<script>
			$(document).ready(function() {
				$('input.category$corpus').typeahead({
					name: 'category$corpus',
					remote: 'catsuggest{$_GET['type']}.php?query=%QUERY&corpus=$flagCorpus'
				});
			})
			</script>\n";
	}
} // end Wikipedia

class corpusWiktionary extends corpusWikipedia {
	public function formExtra () {
		$checklist = '';
		foreach (array ('', 'un') as $mid) {
			$xname = "c{$this->corpus}{$mid}cap";
			echo "   &nbsp; &nbsp; <label>{$mid}capitalized <input name=$xname id={$xname}_dc type=checkbox checked=yes /></label>\n";
			$checklist = "$checklist {$xname}_dc";
		}
		return parent :: formExtra() . $checklist;
	}

	public function builder (&$consObjects) {
		parent::builder ($consObjects);
		$corpus = $this->corpus;
		$cap = getCheckbox ("c{$corpus}cap");
		$uncap = getCheckbox ("c{$corpus}uncap");
		if (!$cap || !$uncap) {
			$_GET["c{$corpus}flag@cap"] = $cap ? 'C' : 'U';
		}
	}

	public function moreSQL ($table, $type, $value) {
		// $type always 'cap' for now
		if ($this->corpus == $this->flagCorpus) {
			$corpusFilter = "<> {$this->corpus}";
		} else {
			$corpusFilter = "IS NULL";
		}
		$not = ($value == 'C' ? '' : 'NOT');
		return (" AND ($table.corpus_id $corpusFilter OR $table.flags $not LIKE '%C%') ");
	}

	public function getValidateCorpusCode () {
		$corpus = $this->corpus;
		return "if (!theForm['c{$corpus}cap_dc'].checked && !theForm['c{$corpus}uncap_dc'].checked) {
		 return 'Must select either Capitalized or Uncapitalized for Wiktionary';
	 }";
	}
} // end Wiktionary

class ccWikipediaText extends corpusConstraint {
	// Intermediate class; never instantiated directly
	function parse () {
		return ''; // Nothing in SQL; we'll check it locally
	}

  protected function getAPI ($entry, $action, $keyname, $prop) {
		$entry = urlencode ($this->corpusObject->titleFix ($entry));
		$url = "https://en.wikipedia.org/w/api.php?action=$action&$keyname=$entry&prop=$prop&format=json";
		$ret = fetchUrl($url);
		return json_decode ($ret, true);
  }

  protected function getText ($entry) {
		$text = $this->getAPI ($entry, 'parse', 'page', 'wikitext')['parse']['wikitext']['*'];
		$text = preg_replace_callback ('/\[\[(.+?)(\|.+?)?\]\]/', function ($matches)
			// match [[interal | external]], where the |external is optional
			{
				if ($matches[2] > '') {
					return substr ($matches[2], 1); // remove leading | delimiter and return replacement text
				} else {
					return $matches[1]; // return title
				}
			},
				$text);
		$text = preg_replace ('/<ref>.+?<\/ref>/', '', $text);
		$text = preg_replace ('/<.+?>/', '', $text);
		$text = preg_replace ('/\[.+? (.+?)\]/', '$1', $text);
		return strtolower ($text);
  }
} // end class ccWikipediaText

class ccWikipediapattern extends ccWikipediaText {
	protected $regex;

	function explainSub () {
		return "Wikipedia Text on $this->spec";
	}

  function init () {
		$this -> regex = patternToRegex (expandSpecial ($this->spec), 'PU');
	}

	function localFilter($oneword, $entry, $entry_id) {
		return (preg_match ($this->regex, $this->getText($entry))) XOR ($this -> not);
	}
}

class ccWikipediaregex extends ccWikipediaText {
	protected $regex;

	function explainSub () {
		return "Wikipedia Regular Expression on $this->spec";
	}

	function init () {
		$this -> regex = expandSpecial ($this->spec);
	}

	function localFilter($oneword, $entry, $entry_id) {
		$ret = (preg_match ($this->regex, $this->getText($entry), $matches)) XOR ($this -> not);
		if (count($matches) > 1) {
			comment ("matched " . implode ('/', $matches));
		}
		return $ret;
	}
}

class ccWikipediacategory extends ccWikipediaText {
	protected $categoryID;
	protected $style; // D to do database filtering as part of initial query; A to use API afterward
	protected $range;
	protected $maxDepth = 5;

	function explainSub () {
		$a = $b = '';
		if ($this->range == 'contains') {
			$a = 'contains ';
		} else if ($this->range = 'desc') {
			$b = ' and descendants';
		}
		return "Wikipedia Category $a$this->spec$b";
	}

	function init () {
		$corpus = $this->corpusObject->getCorpusNum();
		$this -> range = $_GET ["wc{$corpus}_type{$this->num}"];
		if ($this->range != 'contains') {
			$conn = openConnection (false);
			$specFix = str_replace ("'", "\\'", $this -> spec);
			$row = SQLQuery ($conn, "SELECT id FROM category WHERE title = '$specFix' and corpus_id = $corpus")->fetch(PDO::FETCH_ASSOC);
			if ($row === false) {
				throw new Exception("Unable to find category {$this->spec}");
	    }
			$this -> categoryID = $row['id'];
		}
	}

	function parse () {
		$this -> style = 'D'; // by default, do things with database-side filtering
		if ($this->range == 'contains') {
			$suffix = $this->corpusObject -> getCorpusNum() . "_{$this->num}";
			$table = $this->corpusObject->getEntryTable();
			$more ['pre'] = " INNER JOIN entry_cat EC$suffix ON EC$suffix.entry_id = $table.id
			 		INNER JOIN category C$suffix ON C$suffix.id = EC$suffix.cat_id";
			$spec = str_replace ("'", "\'", $this -> spec);
			$more ['where'] = " AND C$suffix.title LIKE '%$spec%'";
			return $more;
		} else {
			$conn = openConnection (false);
			$catList [$buildCount = 0] = $this -> categoryID;
			$catDepth [$buildCount] = 0;
			$searchCount = -1;
			while (++$searchCount <= $buildCount  &&  $this->range == 'desc') {
				$sql = "select C.id from category M
						inner join catparent J on J.parentcat = M.title
						inner join category C on C.id = J.cat_id where M.id = {$catList[$searchCount]}";
				$result = SQLQuery ($conn, $sql);
				if (($depth = ($catDepth[$searchCount] + 1)) > 4) {
					continue; // categories get silly beyond this point
				}
				while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
					$cat = $row['id'];
					if (!isset ($catIndex [$cat])) {
						$catList[++$buildCount] = $cat;
						$catDepth[$buildCount] = $depth;
						$catIndex [$cat] = true;
					}
				}
				if ($buildCount > 20) {
					$this -> style = 'A'; // Too many, do it on the client
					return '';
				}
			}
			if ($this -> style == 'D') {
				if (count ($catList) == 1) {
					$catRequire = "= $catList[0]";
				} else {
					$catRequire = 'IN (' . implode (',', $catList) . ')';
				}
				$table = "CAT{$this -> categoryID}";
				return array ('pre' => " INNER JOIN entry_cat $table ON $table.entry_id = entry.id AND $table.cat_id $catRequire ");
			}
		} // end not 'contains'
	} // end arse

	function localFilter($oneword, $entry, $entry_id) {
		if ($this -> style == 'D') {return true;} // already dealt with on database side
		$searchCount = 1;
		$corpus = $this->corpusObject->getCorpusNum();
		$conn = openConnection (false);
		$sql = "SELECT entry_cat.cat_id FROM entry_cat INNER JOIN category ON category.id = entry_cat.cat_id
		 		WHERE entry_cat.entry_id = $entry_id AND category.corpus_id = $corpus";
		$result = SQLQuery ($conn, $sql);
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			// Make a list to seed the tree walk
			$catID = $row ["cat_id"];
			if (!isset ($catIndex [$catID])) {
				$catList [$buildCount++] = array ('id' => $catID, 'depth' => 0);
				$catIndex [$catID] = true;
			}
		}
		// Walk the tree of ancestors from the entry
		while ($searchCount < $buildCount) {
			$oneCat = $catList [$searchCount]['id'];
			$depth = $catList [$searchCount]['depth'];
			if ($oneCat == $this -> categoryID) {
				return true;
			}
			if (++$depth < 6) {
				$sql = "SELECT category.id FROM catparent
						INNER JOIN category ON category.title = catparent.parentcat
						WHERE catparent.cat_id = $oneCat AND corpus_id = $corpus";
				$result = SQLQuery ($conn, $sql);
				while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
					$catID = $row ['id'];
					if (!isset ($catIndex [$catID])) {
						$catList [$buildCount++] = array ('id' => $catID, 'depth' => $depth);
						$catIndex [$catID] = true;
					}
				}
			}
			$searchCount++;
		}
		return false; // all done searching and didn't find it
	} // end localFilter

	function rebuildForm($realNumber) {
		parent::rebuildForm($realNumber);
		$corpus = $this->corpusObject->getCorpusNum();
		Echo "radioClicked$corpus ($realNumber);\n"; // This will display the secondary radio buttons
		$range = $_GET["wc{$corpus}_type{$this->num}"];
		Echo "theForm['rc{$corpus}_$range$realNumber'].checked = true;\n";
  }
} // end class ccWikipediacategory

class ccWikipediasize extends ccWikipediaText {
  protected $relation;
	protected $size;

	function explainSub () {
		return "Wikipedia Size $this->spec";
	}

	function init () {
		$this->relation = substr ($this->spec, 0, 1);
		$this->size = substr ($this->spec, 1);
	}

	function localFilter($oneword, $entry, $entry_id) {
		foreach ($this->getAPI ($entry, 'query', 'titles', 'info')['query']['pages'] as $page) { // should be only one, but we don't know the ID
			$size = $page['length'];
		}
		$ret = (($size > $this->size) XOR ($this->relation == '<') XOR ($this->not));
		return $ret;
	}
}

class corpusDev extends corpus {
	public function allowed () {
		return false;
	}
} // end Dev

class corpusUser extends corpus {
} // end corpusUser
?>
