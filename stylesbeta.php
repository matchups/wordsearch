<?php
if ($_GET['test']) {
  $debug = true;
} else {
  //Set the content-type header and charset.
  header("Content-Type: text/css; charset=utf-8");
  $debug = false;
}

$type = $_GET['type'];
include "utility$type.php";

echo '.h3 {color: green; text-decoration: underline;}' . "\n";
echo '.a {text-decoration: none;  color:green;}';
echo '.debug {color: blue;}' . "\n";
echo '.specs {text-decoration: underline;}' . "\n";
?>
