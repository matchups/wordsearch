<?php
include "utilitydev.php"; // Might be nice to get $type one, but not sure we can do it in this context
$debug = cssInit ();
$conn = openConnection (false);
$userid = getUserFromIP ();

try {
  echo suggestStyleFromCorpus ('word', $userid, false);
  $display = 'none';
}
catch (Exception $e) {
  $display = 'inline';
  if ($debug) {echo $sql . '<P>' . $e->getMessage();}
}
echo '.csswarning {display: ' . $display . '; color: red}';
?>
