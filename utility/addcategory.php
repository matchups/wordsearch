<HTML>
<HEAD>
<TITLE>Categories: Wikipedia API read</TITLE>
</HEAD>
<BODY>
<H1>Categories</H1>
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
$baseUrl='https://en.wikipedia.org/w/api.php?action=query&list=allcategories&acprop=size&format=json&acmin=1';

if (isset ($_GET["from"])) {
  $continue['accontinue'] = $_GET["from"];
} else {
  $continueUrl = '';
}

$counter = 0;
try {
  $conn = openConn ($corpus);
  for ($looper = 0; $looper < $limit; $looper++) {
    if (isset ($continue)) {
      $continueUrl = "&accontinue=" . urlencode ($continue['accontinue']);
    }
    $ret = fetchUrl($baseUrl . $continueUrl);
    $json = json_decode($ret, true);
    foreach ($json["query"]["allcategories"] as $page) {
      echo '<BR><B>' . ++$counter . ' ' . ($title = $page['*']);
      echo '</B>';
      newCategory ($conn, $title, getCategories ($conn, "Category:$title"), $corpus);
      echo '<BR>';
    }
    if (isset ($json["continue"])) {
      $continue = $json["continue"];
      $more = true;
      $from = urlencode ($continue['accontinue']);
      echo "<P><A HREF='addcategory.php?limit=$limit&from=$from&corpus=$corpus'>Continue<A>";
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

function newCategory ($conn, $title, $catlist, $corpus) {
  $stmt = $conn->prepare('SELECT id FROM category WHERE title = ? AND corpus_id = ?');
  $stmt->execute(array ($title, $corpus));
  if ($conn->errorCode() <> "fake") {
    if (($result = $stmt->fetch (PDO::FETCH_ASSOC)) !== false) {
      echo " Already {$result['id']}";
      return;
    }
  }

  $title = str_replace ("'", "\\'", $title);
  $entry_id = sqlInsert ($conn, "INSERT category (title, corpus_id) VALUES ('$title', $corpus)");
  foreach ($catlist as $parentCat) {
    $parentCat = str_replace ("'", "\\'", $parentCat);
    sqlInsert ($conn, "INSERT catparent (cat_id, parentcat) VALUES ('$entry_id', '$parentCat')");
  }
}
