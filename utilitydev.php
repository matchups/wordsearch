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

// Do a SQL query and remember the code so we can display it with error messages
function SQLQuery ($conn, $sql) {
	$GLOBALS['lastSQL'] = $sql;
	if (isset ($_GET['debug'])) {comment ($sql);}
	return $conn->query($sql);
}

// Display a string if we haven't done so previously
function echoUnique ($text) {
	$hash = hash('ripemd160', $text);
	if (!isset ($GLOBALS['cache'][$hash])) {
		echo $text;
		$GLOBALS['cache'][$hash] = true;
	}
}

// Check for a valid session before creating *any* output
// Returns an error code or nil on success
function securityCheck (&$level, &$userid, &$sessionid) {
	$type = $_GET['type'];
	$code = '1';
	if (isset ($_GET['sessionkey'])  &&  isset ($_GET['level'])) { // make sure session info is passed to us
		$session = $_GET['sessionkey'];
		try {
			$conn = openConnection (false);
			$getLevel = $_GET ['level'];
			if ($getLevel > 0) {
				$start = "user.level";
				$middle = "INNER JOIN user ON user.id = session.user_id";
				$end = '';
			} else {
				$start = '0 AS level';
				$middle = '';
				$end = " AND session.user_id = 0";
			}
			$sql = "SELECT $start, ip_address, session.id, user_id FROM session $middle WHERE session_key = '$session' AND status = 'A' $end";
			$result = $conn->query($sql);
			if ($result->rowCount() > 0) { // make sure it is an active session
				$row = $result->fetch(PDO::FETCH_ASSOC);
				$level = $row['level'];
				$userid = $row['user_id'];
				$sessionid = $row['id'];
				if ($level == $getLevel) {
					if ($level < 2 && $type > '') {
						$code = '4'; // basic not authorized for anything other than base
					} else if ($level < 3 && $type == 'dev') {
						$code = '5'; // only pro authorized for dev
					} else if ($level == 0 && isset($_GET['query2'])) {
						$code = '6'; // guest can't use additional criteria
					} else if (explode ('|', $row['ip_address'])[0] == $_SERVER['REMOTE_ADDR']) {
						$code = '';
					} else {
						$code = '7';
					}
				} else {
					$code = '2'; // URL lies about the user's level
				}
			} else {
				$code = '3';
			}
		}
		catch(PDOException $e) {
			$code = $e->getCode ();
		}
	}
	if (!$code) {
		openConnection (true)->exec ("UPDATE session SET last_active = UTC_TIMESTAMP() WHERE session_key = '$session'");
	}
	return $code;
}

function timeDiff ($begin, $end) {
	$beginArray = explode (' ', $begin);
	$endArray = explode (' ', $end);
	return intval ((($endArray[1] - $beginArray[1]) + ($endArray[0] - $beginArray[0])) * 1000 + 0.5);
}
?>
