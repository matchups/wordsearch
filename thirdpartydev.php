<?php
class thirdParty {
  protected $pattern;
  protected $anyOrder;
  protected $letterBank;

  function __construct () {
    $this -> pattern = $_GET['pattern'];
    $this -> anyOrder = getCheckbox ('anyorder');
    $this -> letterBank = getCheckbox ('repeat');
  }

  public static function list () {
    return array ('qat', 'qhex', 'nutrimatic', 'ias', 'dmitri');
  }

  public function allowed () {
    return $GLOBALS['level'];
  }

  public function name () {
    throw new Exception ("Must override name()");
  }

  public function link () {
    throw new Exception ("Must override link()");
  }

  public function enabled () {
    return false;
  }
} // end class thirdParty

class qat extends thirdParty {
  public function link () {
    $pattern = str_replace ('?', '.', $this->pattern);
    if ($this->anyOrder) {
      $pattern = "/$pattern";
    }
    if (($usermin = $_GET['minlen'])  ||  ($usermax = $_GET['maxlen'])) {
      $pattern = "$usermin-$usermax:$pattern";
    }
    $pattern = urlencode ($pattern);
    return "https://www.quinapalus.com/cgi-bin/qat?pat=$pattern&ent=Search&dict=0";
  }

  function name () {
    return 'Qat';
  }

  function enabled () {
    return !$this->letterBank;
  }
}

class qhex extends thirdParty {
  function link () {
    $pattern = patternToRegex ($this->pattern, 'U');
    if ($this->letterBank) {
      $pattern = "bank:$pattern";
    }
    else if ($this->anyOrder) {
      $pattern = "anagram:$pattern";
    }
    return "https://tools.qhex.org?wordplay=" . urlencode ($pattern);
  }

  function name () {
    return 'Qhex';
  }

  function enabled () {
    return true;
  }
 }

class nutrimatic extends thirdParty {
  function link () {
    $rules = $this->pattern;
    if ($this->anyOrder) {
      $rules = "<$rules>";
    }
    $rules = patternToRegex ($rules, 'U');
    foreach ($GLOBALS['consObjects'] as $consObject) {
      if (($regex = $consObject->getRegex())  &&  strpos ($regex, '\\') === false) {
        $rules .= "&$regex";
      }
    }
    $rules = urlencode ($rules);
    return "https://nutrimatic.org?q=$rules&go=Go";
  }

  function name () {
    return 'Nutrimatic';
  }

  function enabled () {
    return !$this->letterBank;
  }
 }

class ias extends thirdParty {
  function enabled () {
    return preg_match ('/^[a-z]*$/', $this->pattern) && $this->anyOrder && !$this->letterBank;
  }

  function link () {
    return "https://new.wordsmith.org/anagram/anagram.cgi?anagram={$this->pattern}";
  }

  function name () {
    return 'Internet Anagram Server';
  }
 }

class dmitri extends thirdParty {
  function allowed () {
    return isset (array (3=>1, 16=>1, 17=>1, 18=>1, 19=>1)[$GLOBALS['userid']]);
  }

  function link () {
    $pattern = str_replace ('?', '.', $this->pattern);
    if ($this->letterBank) {
      $verb = 'has letter bank';
    } else if ($this->anyOrder) {
      $verb = 'anagrams to';
    } else {
      $verb = 'has form';
    }
    $query = "A $verb $pattern";
    if (($usermin = $_GET['minlen'])  ||  ($usermax = $_GET['maxlen'])) {
      // pending A has length N-M
    }
    $query = urlencode ($query);

    // pending subwords
    // pending letter match
    return "http://stufffromhell.com/dmitri/index2.cgi?equation=$query";
  }

  function name () {
    return 'Dmitri';
  }

  function enabled () {
    return true;
  }
 }
?>
