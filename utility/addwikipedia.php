<HTML>
<HEAD>
<?php
$classname = $_GET["classname"];
$helper = new $classname;
echo "
<TITLE>Articles: " . $helper->getTitle() . " API read</TITLE>
</HEAD>
<BODY>
<H1>Articles</H1>\n";

include 'addmain.php';
// How many tries?
if (isset ($_GET["limit"])) {
  $limit = $_GET["limit"];
} else {
  $limit = 100;
}

// Which corpus
if (isset ($_GET["corpus"])) {
  $corpus = $_GET["corpus"];
} else {
  $corpus = 'x0';
}

// Where do we start?
$baseurl='https://en.' . $helper->getDomain() . '/w/api.php?action=query&generator=allpages&prop=info&format=json';
$url = $baseurl;
if (isset ($_GET["from"])) {
  $continue['continue'] = "gapcontinue||";
  $continue['gapcontinue'] = $_GET["from"];
} else {
  $continueURL = '';
}

$counter = 0;
$more = true; // default if we fail
try {
  $conn = openConn ($corpus);
  for ($looper = 0; $looper < $limit; $looper++) {
    if (isset ($continue)) {
      $continueURL = "&continue=" . urlencode ($continue['continue']) . "&gapcontinue=" . urlencode ($continue['gapcontinue']);
    }
    $url = $baseurl . $continueURL;
    // echo "<BR>@@ About to access $url<BR>";
    $ret = fetchUrl($url);
    $json = json_decode($ret, true);
    foreach ($json["query"]["pages"] as $page) {
      echo '<BR><B>' . ++$counter . ' ' . $page['title'];
      $flags = '';
      if (isset ($page['redirect'])) {
        echo ' <i>redirect</i>';
        $flags = 'R';
      }
      echo '</B>';
      $ret = newEntry ($conn, $page['title'], $flags, $corpus, $helper);
      echo '<BR>';
    }
    if (isset ($json["continue"])) {
      $continue = $json["continue"];
      $more = true;
      $from = urlencode ($continue['gapcontinue']);
      echo "<P><A HREF='addwikipedia.php?limit=$limit&classname=$classname&from=$from&corpus=$corpus'>Continue<A>";
    } else {
      $more = false;
      break;
    }
  } // end for
} catch (Exception $e) {
    echo 'Creating entry failed: ' . $e->getMessage();
}
if (!$more) {
  echo "<H2>End of Wiki!</H2>";
}

function fetchUrl($uri) {
    $handle = curl_init();

    curl_setopt($handle, CURLOPT_URL, $uri);
    curl_setopt($handle, CURLOPT_POST, false);
    curl_setopt($handle, CURLOPT_BINARYTRANSFER, false);
    curl_setopt($handle, CURLOPT_HEADER, true);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($handle);
    $hlength  = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $body     = substr($response, $hlength);

    // If HTTP response is not 200, throw exception
    if ($httpCode != 200) {
        throw new Exception($httpCode);
    }

    return $body;
}

class loadHelper {
  public function getTitle () {
  	return "No class";
  }

  public function getDomain () {
  	return "www.aprilfools.com";
  }

  public function postProcess ($conn, $ret) {
  }

  public function category ($category, &$ret) {
  }
} // end class loadHelper

class wikipedia extends loadHelper {
  public function getTitle () {
  	return "Wikipedia";
  }
  public function getDomain () {
  	return "wikipedia.org";
  }
} // end class wikipedia

class wiktionary extends loadHelper {
  public function getTitle () {
  	return "Wiktionary";
  }

  public function getDomain () {
  	return "wiktionary.org";
  }

  public function postProcess ($conn, $ret) {
    if (!isset ($ret['english'])) {
      $conn->exec ("UPDATE entry SET flags = 'F' WHERE id = {$ret['id']} AND flags != 'R'");
    }
  }

  public function category ($category, &$ret) {
    if (substr ($category, 0, 7) == "English") {
      $ret ['english'] = true;
    }
  }
} // end class wiktionary
