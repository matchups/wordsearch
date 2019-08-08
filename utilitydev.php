<?php
// Insert a comment into the output page.
function comment ($text) {
	if ($GLOBALS ['level'] < 3) {
		// For regular users, don't provide full plaintext comment as a security measure,
		// but create a digest of the comment so I can tell if I'm on the right track when trying to reproduce an issue.
		$text = substr ($text, 0, 3) . '.' . strlen ($text) . '.' . substr (hash ('sha512', $text), 0, 12);
	}
	$text = str_replace (array ('<!--', '-->'), array ('<! --', '-- >'), $text); // avoid prematurely closing comments in funny cases
	Echo "<!-- $text -->";
	}

function errorMessage ($text) {
  echo "<span class='error'>Sorry, unable to process your request.  Please <A HREF='mailto:error@alfwords.com'>let us know</A>.</span>\n";
  comment ($text);
}

function openConnection ($write) {
	if ($write) {
		$suffix = '_w';
	} else if (isset ($GLOBALS['conn'])) {
		// use existing one, if available
		return $GLOBALS['conn'];
	} else {
		$suffix = '';
	}
  include "/usr/home/adf/credentials$suffix.php";
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $conn;
}

function fetchUrl($url) {
	// use cache if available
	if ($url == $GLOBALS['cache']['url']) {
		return $GLOBALS['cache']['body'];
	}
	// Do magic CURL stuff that I got from the web
  $handle = curl_init();

  curl_setopt($handle, CURLOPT_URL, $url);
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

  // update cache in the event of a second request
	$GLOBALS['cache']['url'] = $url;
	$GLOBALS['cache']['body'] = $body;

  return $body;
}

// Display a string if we haven't done so previously
function echoUnique ($text) {
	$hash = hash('ripemd160', $text);
	if (!isset ($GLOBALS['cache'][$hash])) {
		echo $text;
		$GLOBALS['cache'][$hash] = true;
	}
}
?>
