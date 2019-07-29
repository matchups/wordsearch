<HTML>
<HEAD>
<TITLE>Articles: Wikipedia API read</TITLE>
</HEAD>
<BODY>
<H1>Articles</H1>
<?php
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
$baseurl='https://en.wikipedia.org/w/api.php?action=query&generator=allpages&prop=info&format=json';
$url = $baseurl;
if (isset ($_GET["from"])) {
  $continue['continue'] = "gapcontinue||";
  $continue['gapcontinue'] = $_GET["from"];
} else {
  $continueURL = '';
}

$counter = 0;
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
      newEntry ($conn, $page['title'], $flags, $corpus);
      echo '<BR>';
    }
    if (isset ($json["continue"])) {
      $continue = $json["continue"];
      $more = true;
      $from = urlencode ($continue['gapcontinue']);
      echo "<P><A HREF='addwikipedia.php?limit=$limit&from=$from&corpus=$corpus'>Continue<A>";
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
