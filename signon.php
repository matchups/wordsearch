<?php
include "/usr/home/adf/credentials.php";
try {
  // Connect and run query
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if (isset ($_GET['guest'])) {
  	$userid = 0;
  	$level = 0;
  } else {
  	$username = $_GET['username'];
  	$pwhash = substr (hash ('sha512', $_GET['password_sha']), 0, 56);
  	$sql = "SELECT id, level, realname FROM user WHERE username='$username' AND password_SHA='$pwhash'";
  	$result = $conn->query($sql);
  	if ($result->rowCount() > 0) {
  	    $row = $result->fetch(PDO::FETCH_ASSOC);
  	    $userid = $row['id'];
  	    $level = $row['level'];
  	    $realname = $row['realname'];
  	} else {
  	  header("Location: http://www.8wheels.org/wordsearch/signon_bad.html"); // Redirect browser
  	  exit ();
  	}
  }

  $ip = $_SERVER['REMOTE_ADDR'];
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
   $ip = $ip . '|' . $_SERVER['HTTP_CLIENT_IP'];
  }
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	   $ip = $ip . '|' . $_SERVER['HTTP_X_FORWARDED_FOR'];
  }

  // Handle unlikely situation of so many addresses that they overwhelm the available space
  if (strlen ($ip) > 255) {
     $ip = substr ($ip, 0, 254) . '+';
  }
  $sessionkey = $username . time();
  do {
     $sessionkey = substr (shrinkHex (hash ('sha256', $sessionkey)), 0, 12);
  } while ($conn->query("SELECT id FROM session WHERE session_key = '$sessionkey'")->rowCount() > 0); // Make sure session key is unique
  $sql = "INSERT session (session_key, user_id, status, started, last_active, ip_address)
    VALUES ('$sessionkey', '$userid', 'A', UTC_TIMESTAMP(), UTC_TIMESTAMP(), '$ip')";

  // Reconnect in write mode to create the session
  include "/usr/home/adf/credentials_w.php";
  $connw = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $connw->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $connw->exec ($sql);
  unset ($connw); // Minimize exposure of write connection
  header("Location: http://www.8wheels.org/wordsearch/index.php?sessionkey=$sessionkey&level=$level"); // Pass session info to search page
  exit();
}

catch(PDOException $e)
{
    echo "SQL failed: " . $e->getMessage();
}

// Shrink a random hex string into a shorter random string
function shrinkHex ($original) {
  $allowed = '1234567890!$*()-_.>,<abcdefghojklmnopqrstuvwxyz';
  $count = strlen ($allowed);
  $max = 1;
  $value = 0;
  $ret = '';
  while (strlen ($original) > 0) {
  	$value = $value * 16 + hexdec (substr ($original, 0, 1));
  	$max = $max * 16;
  	$original = substr ($original, 1);
  	if ($max > $count) {
	    $ret = $ret . substr ($allowed, $value % $count, 1);
	    $max = ($max - 1) / $count + 1;
	    $value = $value / $count;
  	}
  }
  $ret = $ret . substr ($allowed, $value, 1);
  return $ret;
}
?>
</BODY>
