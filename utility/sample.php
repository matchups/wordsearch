<HTML>
<HEAD>
</HEAD>
<BODY>
<?php
// include "/usr/home/adf/credentials.php";
foreach (array ('John', 'marry', 'the West End') as $word) {
  $caps = preg_match ('/[A-Z]/', $word);
  echo "$word $caps\n";
}
?>
</BODY>
