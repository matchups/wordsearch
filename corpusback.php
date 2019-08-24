<?php
class corpus {
	protected $corpus;
	protected $urlpattern;
	protected $name;
  protected $flags;

	public function __construct ($corpus) {
		$this->corpus = $corpus;
		if (isset ($GLOBALS['conn'])) {
			$conn = $GLOBALS['conn'];
		} else {
			$conn = openConnection (false);
		}

		// get name, URL
		$row = $conn->query("SELECT name, url FROM corpus WHERE id = $corpus")->fetch(PDO::FETCH_ASSOC);
		$this->urlpattern = $row['url'];
		$this->name = $row['name'];

		// get flags
		$result = $conn->query("SELECT letter, description FROM corpus_flag WHERE corpus_id = $corpus");
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$this->flags [$row['letter']]=$row['description'];
		}
	} // end constructor

	public static function factory ($corpus) {
		// Alas, PHP does not support ranges in switches
		if ($corpus == 1) {
			$name = "WikiFeatured";
		} else if ($corpus == 2) {
			$name = "Wikipedia";
		} else if ($corpus == 87) {
			$name = "Dev";
		} else if ($corpus > 100) {
			$name = "User";
		} // Wiktionary TBD
		$name = "corpus$name";
		return new $name($corpus);
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
		return true;
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
		echo "<br>";
		return $checklist;
	}

	public function builder (&$consObjects) {
		// Allows corpus to set up filtering, either via SQL or client processing
		// Base implementation calls a function on the corpus object for each row
		if (isset ($_GET["count$this->corpus"])) {
			$count = $_GET["count$this->corpus"];
			for ($num = 1; $num <= $count; $num++) {
				$key = "{$this->corpus}_$num";
				if (($ccobject = $this -> buildOne ($_GET["query$key"], $_GET["radio$key"], getCheckbox ("not$key"), $num)) !== null) {
					$consObjects [$key] = $ccobject;
				}
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
		echo "&nbsp;&nbsp;<button type='button' id='add$corpus' onclick='addOption$corpus();return false;'>More</button><BR>\n";
		echo "<script>\n";
		echo "function addOption$corpus() {";
		echo "// add a new constraint when user presses that button\n";
		echo "var theForm = document.getElementById('search');\n";
		echo "var corpusOptionNumber = ++(theForm['$key'].value);\n";
		echo "var here = theForm['add$corpus'];\n";
		echo "var myParent = here.parentNode;\n";
		echo "\n";
		echo "// Label for constraint\n";
		echo "var newOption = document.createElement('span');\n";
		echo "newOption.id = 'label{$corpus}_' + corpusOptionNumber;\n";
		echo "newOption.innerHTML = '&nbsp;&nbsp;$name#' + corpusOptionNumber + ': (not)';\n";
		echo "myParent.insertBefore (newOption, here);\n";

		echo "// Checkbox for NOT\n";
		echo "newOption = document.createElement('input');\n";
		echo "newOption.name = 'not{$corpus}_' + corpusOptionNumber;\n";
		echo "newOption.type = 'checkbox';\n";
		echo "newOption.id = 'not{$corpus}_' + corpusOptionNumber;\n";
		echo "myParent.insertBefore (newOption, here);\n";

		echo "newOption = document.createElement('input');\n";
		echo "newOption.name = 'query{$corpus}_' + corpusOptionNumber;\n";
		echo "newOption.type = 'text';\n";
		echo "newOption.required = true;\n";
		echo "newOption.id = 'query{$corpus}_' + corpusOptionNumber;\n";
		echo "myParent.insertBefore (newOption, here);\n";

		$first = true;
		foreach ($this->optionButtonList() as $id => $description) {
			echo "var newOption = document.createElement('input');\n";
			if (($lead = substr ($description, 0, 1)) == '!') {
				echo "newOption.disabled = true;\n";
				$description = substr ($description, 1);
			}
			echo "newOption.type = 'radio';\n";
			echo "newOption.name = 'radio{$corpus}_' + corpusOptionNumber;\n";
			echo "newOption.value = '$id';\n";
			echo "newOption.id = 'r{$corpus}_$id' + corpusOptionNumber;\n";
			if ($first) {
				echo "newOption.checked = true;\n";
				$first = false;
			}
			// echo "newOption.setAttribute('onclick','radioClicked(' + optionNumber + ')');\n";
			echo "myParent.insertBefore (newOption, here);\n";

			echo "newOption = document.createElement('span');\n";
			if ($lead == '!') {
				echo "newOption.class = 'disabled';\n";
			}
			echo "newOption.id = 't{$corpus}_$id' + corpusOptionNumber;\n";
			echo "newOption.innerHTML = ' $description ';\n";
			echo "myParent.insertBefore (newOption, here);\n";
		}

		echo "// Button to remove constraint\n";
		echo "newOption = document.createElement ('button');\n";
		echo "newOption.type = 'button';\n";
		echo "newOption.id = 'delcons{$corpus}_' + corpusOptionNumber;\n";
		echo "newOption.innerHTML = 'Remove';\n";
		echo "newOption.setAttribute('onclick','removeConstraint$corpus(' + corpusOptionNumber + ')');\n";
		echo "myParent.insertBefore (newOption, here);\n";

		echo "newOption = document.createElement ('br');\n";
		echo "newOption.id = 'br{$corpus}_' + corpusOptionNumber;\n";
		echo "myParent.insertBefore (newOption, here);\n";

		echo "} // End addOption$corpus\n";

		echo "function removeConstraint$corpus (corpusOptionNumber) {\n";
		echo "var container = document.getElementById('source');\n";
		foreach ($this->optionButtonList() as $id => $description) {
			echo "container.removeChild(document.getElementById('r{$corpus}_$id' + corpusOptionNumber));\n";
			echo "container.removeChild(document.getElementById('t{$corpus}_$id' + corpusOptionNumber));\n";
		}
		foreach (array ('br', 'delcons', 'label', 'not', 'query') as $id) {
			echo "container.removeChild(document.getElementById('$id{$corpus}_' + corpusOptionNumber));\n";
		}
		echo "if (corpusOptionNumber == theForm['$key'].value) {\n";
		echo "theForm['$key'].value--;\n";
		echo "}\n";
		echo "} // end removeConstraint$corpus\n";

		echo "</script>\n";
		echo "<input type=hidden id='$key' name='$key' value='0' />";
	}

	protected function optionButtonList () {
		return array ();
	}

	public function requireWhole () {
		// Do we need to restrict to whole entries because we need to look at the linked pages?
		if (isset ($_GET["count$this->corpus"])) {
			if ($_GET["count$this->corpus"] > 0) {
				return true; // By default, if there are any special considerations, the answer is yes
			}
		}
		return false;
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
		echo "addOption$corpusNum();\n";
		if ($this->not) {
			Echo "theForm['not{$corpusNum}_$realNumber'].checked = true;\n";
		}
		Echo "theForm['query{$corpusNum}_$realNumber'].value = '" . addslashes ($this->spec) . "';\n";
		// Set the radio button corresponding to the selected option
		Echo "theForm['r{$corpusNum}_" . $_GET["radio{$corpusNum}_$this->num"] . "$realNumber'].checked = true;\n";
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

	function form () {
		$checklist = parent::form();
		$this->formAddRepeat ();
		return $checklist;
	}

	function optionButtonList () {
		return array ('pattern' => 'pattern', 'regex' => 'regular expression', 'category' => '!category', 'size' => 'size');
	}

	protected function buildOne ($spec, $radio, $not, $num) {
		$classname = "ccWikipedia$radio";
		return new $classname ($spec, $num, $not, $this);
	}
} // end Wikipedia

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

	function localFilter($oneword, $entry) {
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

	function localFilter($oneword, $entry) {
		$ret = (preg_match ($this->regex, $this->getText($entry), $matches)) XOR ($this -> not);
		if (count($matches) > 1) {
			comment ("matched " . implode ('/', $matches));
		}
		return $ret;
	}
}

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

	function localFilter($oneword, $entry) {
		foreach ($this->getAPI ($entry, 'query', 'titles', 'info')['query']['pages'] as $page) { // should be only one, but we don't know the ID
			$size = $page['length'];
		}
		$ret = (($size > $this->size) XOR ($this->relation == '<') XOR ($this->not));
		return $ret;
	}
}

class corpusWikiFeatured extends corpusWikipedia {
	function allowed () {
		return $GLOBALS['level']==3;
	}

	function formAddRepeat () { // override and kill
	}
} // end WikiFeatured

class corpusDev extends corpus {
	public function allowed () {
		return $GLOBALS['level']==3;
	}
} // end Dev

class corpusUser extends corpus {
	// allowed will check user & shared stuff
} // end corpusUser
?>
