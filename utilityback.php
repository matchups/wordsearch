<?php
// Insert a comment into the output page.
function comment ($text) {
	if ($GLOBALS ['level'] < 3) {
		// For regular users, don't provide full plaintext comment as a security measure,
		// but create a digest of the comment so I can tell if I'm on the right track when trying to reproduce an issue.
		$text = substr ($text, 0, 3) . '.' . strlen ($text) . '.' . substr (hash ('sha512', $text), 0, 12);
	}
	Echo "<!-- $text -->";
	}

function answerLink ($value, $corpus, $urlpattern) {
  if ($urlpattern > '') {
    $linkvalue = $value;
    if ($corpus == 1  ||  $corpus == 2) { // hardcode for now
      $linkvalue = str_replace (array (' ', '"'), array ('_', ''), $linkvalue); // Space to underscore and remove quotes, per Wikipedia's private rules
    }
    return "<A target='_blank' HREF='" . str_replace ('@', urlencode ($linkvalue), $urlpattern) . "'>$value</A>";
  } else {
    return $value;
  }
}

function errorMessage ($text) {
  echo "<span class='error'>Sorry, unable to process your request.  Please <A HREF='mailto:error@alfwords.com'>let us know</A>.</span>\n";
  comment ($text);
}

function openConnection ($write) {
	if ($write) {
		$suffix = '_w';
	} else {
		$suffix = '';
	}
  include "/usr/home/adf/credentials$suffix.php";
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,	$password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $conn;
}
?>
