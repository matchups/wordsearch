<?php
if (isset ($_GET['sessionkey'])) {
    $session = $_GET['sessionkey'];
    // Connect briefly in write mode to update the session
    include "/usr/home/adf/credentials_w.php";
    $connw = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $connw->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connw->exec ("UPDATE session SET status = 'F' WHERE session_key = '$session'");
    unset ($connw);
}

// Whether they came in with a good session or not, they don't have one now, so prompt for signon
header("Location: http://www.8wheels.org/wordsearch/index.html?loggedoutof=$session");
?>